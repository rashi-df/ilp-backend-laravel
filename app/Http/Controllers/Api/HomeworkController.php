<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Homework::with('lesson', 'course')
            ->withCount('submissions');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('title', 'ilike', "%{$request->search}%");
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $homework = $query->orderBy('created_at', 'desc')
                          ->skip(($page - 1) * $limit)
                          ->take($limit)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $homework,
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
        $homework = Homework::with('lesson', 'course')
            ->withCount('submissions')
            ->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $homework]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'lessonUuid' => 'nullable|string|exists:lessons,uuid',
            'courseUuid' => 'nullable|string|exists:courses,uuid',
            'instructions' => 'required|string',
            'submissionType' => 'sometimes|in:text,file,audio,video',
            'dueDate' => 'nullable|date',
            'maxScore' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:draft,published',
        ]);

        $lessonId = $request->lessonUuid
            ? \App\Models\Lesson::where('uuid', $request->lessonUuid)->value('id')
            : null;
        $courseId = $request->courseUuid
            ? \App\Models\Course::where('uuid', $request->courseUuid)->value('id')
            : null;

        $homework = Homework::create([
            'title' => $request->title,
            'lesson_id' => $lessonId,
            'course_id' => $courseId,
            'instructions' => $request->instructions,
            'submission_type' => $request->get('submissionType', 'text'),
            'due_date' => $request->dueDate,
            'max_score' => $request->get('maxScore', 100),
            'status' => $request->get('status', 'draft'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Homework created successfully',
            'data' => $homework->load('lesson', 'course'),
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $homework = Homework::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'instructions' => 'sometimes|string',
            'submissionType' => 'sometimes|in:text,file,audio,video',
            'dueDate' => 'nullable|date',
            'maxScore' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:draft,published',
        ]);

        $data = $request->only(['title', 'instructions', 'status']);
        if ($request->has('submissionType')) {
            $data['submission_type'] = $request->submissionType;
        }
        if ($request->has('dueDate')) {
            $data['due_date'] = $request->dueDate;
        }
        if ($request->has('maxScore')) {
            $data['max_score'] = $request->maxScore;
        }

        $homework->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Homework updated successfully',
            'data' => $homework,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $homework = Homework::where('uuid', $uuid)->firstOrFail();
        $homework->submissions()->delete();
        $homework->delete();

        return response()->json(['success' => true, 'message' => 'Homework deleted successfully']);
    }

    // ==================== SUBMISSIONS ====================

    public function listSubmissions(Request $request): JsonResponse
    {
        $query = HomeworkSubmission::with('homework', 'student', 'assignedMentor');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->homeworkUuid) {
            $query->whereHas('homework', fn($q) => $q->where('uuid', $request->homeworkUuid));
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $submissions = $query->orderBy('submitted_at', 'desc')
                             ->skip(($page - 1) * $limit)
                             ->take($limit)
                             ->get();

        return response()->json([
            'success' => true,
            'data' => $submissions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getSubmission(string $uuid): JsonResponse
    {
        $submission = HomeworkSubmission::with('homework', 'student', 'assignedMentor')
            ->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $submission]);
    }

    public function reviewSubmission(Request $request, string $uuid): JsonResponse
    {
        $submission = HomeworkSubmission::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'feedback' => 'nullable|string',
            'grade' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:pending,under_review,reviewed,returned',
        ]);

        $submission->update([
            'feedback' => $request->feedback,
            'grade' => $request->grade,
            'status' => $request->get('status', 'reviewed'),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submission reviewed successfully',
            'data' => $submission,
        ]);
    }
}
