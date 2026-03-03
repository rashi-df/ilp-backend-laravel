<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Course::with('category', 'instructor');

        if ($request->category) {
            $query->whereHas('category', fn($q) => $q->where('uuid', $request->category));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->level) {
            $query->where('level', $request->level);
        }
        if ($request->search) {
            $query->where('title', 'ilike', "%{$request->search}%");
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $courses = $query->orderBy('created_at', 'desc')
                         ->skip(($page - 1) * $limit)
                         ->take($limit)
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $courses->map(fn($c) => $this->formatCourse($c)),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $course = Course::with([
            'category',
            'instructor',
            'modules.lessons',
        ])->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->formatCourse($course, true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'categoryUuid' => 'nullable|string|exists:categories,uuid',
            'instructorUuid' => 'nullable|string|exists:users,uuid',
            'thumbnail' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'duration' => 'sometimes|integer|min:0',
        ]);

        $categoryId = null;
        if ($request->categoryUuid) {
            $categoryId = \App\Models\Category::where('uuid', $request->categoryUuid)->value('id');
        }
        $instructorId = null;
        if ($request->instructorUuid) {
            $instructorId = \App\Models\User::where('uuid', $request->instructorUuid)->value('id');
        }

        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $categoryId,
            'instructor_id' => $instructorId,
            'thumbnail' => $request->thumbnail,
            'status' => $request->get('status', 'draft'),
            'level' => $request->get('level', 'beginner'),
            'duration' => $request->get('duration', 0),
        ]);

        $course->load('category', 'instructor');

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $this->formatCourse($course),
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $course = Course::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'categoryUuid' => 'nullable|string',
            'instructorUuid' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'duration' => 'sometimes|integer|min:0',
        ]);

        $data = $request->only(['title', 'description', 'thumbnail', 'status', 'level', 'duration']);

        if ($request->has('categoryUuid')) {
            $data['category_id'] = $request->categoryUuid
                ? \App\Models\Category::where('uuid', $request->categoryUuid)->value('id')
                : null;
        }
        if ($request->has('instructorUuid')) {
            $data['instructor_id'] = $request->instructorUuid
                ? \App\Models\User::where('uuid', $request->instructorUuid)->value('id')
                : null;
        }

        $course->update($data);
        $course->load('category', 'instructor');

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $this->formatCourse($course),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $course = Course::where('uuid', $uuid)->firstOrFail();

        // Cascade: delete modules and lessons
        foreach ($course->modules as $module) {
            $module->lessons()->delete();
            $module->delete();
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
        ]);
    }

    public function togglePublish(string $uuid): JsonResponse
    {
        $course = Course::where('uuid', $uuid)->firstOrFail();
        $newStatus = $course->status === 'published' ? 'draft' : 'published';
        $course->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Course {$newStatus}",
            'data' => ['status' => $newStatus],
        ]);
    }

    private function formatCourse(Course $course, bool $withModules = false): array
    {
        $data = [
            'uuid' => $course->uuid,
            'title' => $course->title,
            'description' => $course->description,
            'thumbnail' => $course->thumbnail,
            'status' => $course->status,
            'level' => $course->level,
            'duration' => $course->duration,
            'enrollmentCount' => $course->enrollment_count,
            'createdAt' => $course->created_at,
            'updatedAt' => $course->updated_at,
        ];

        if ($course->relationLoaded('category') && $course->category) {
            $data['category'] = [
                'uuid' => $course->category->uuid,
                'name' => $course->category->name,
                'icon' => $course->category->icon,
            ];
        }

        if ($course->relationLoaded('instructor') && $course->instructor) {
            $data['instructor'] = [
                'uuid' => $course->instructor->uuid,
                'name' => $course->instructor->name,
                'email' => $course->instructor->email,
                'avatar' => $course->instructor->avatar,
            ];
        }

        if ($withModules && $course->relationLoaded('modules')) {
            $data['modules'] = $course->modules->map(function ($module) {
                return [
                    'uuid' => $module->uuid,
                    'title' => $module->title,
                    'order' => $module->order,
                    'description' => $module->description,
                    'lessons' => $module->lessons->map(fn($l) => [
                        'uuid' => $l->uuid,
                        'title' => $l->title,
                        'order' => $l->order,
                        'type' => $l->type,
                        'duration' => $l->duration,
                    ]),
                ];
            });
        }

        return $data;
    }
}
