<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function listAll(): JsonResponse
    {
        $lessons = Lesson::with([
                             'module:id,uuid,title,course_id',
                             'module.course:id,uuid,title,category_id',
                             'module.course.category:id,uuid,name',
                         ])
                         ->orderBy('title')
                         ->get(['id', 'uuid', 'title', 'type', 'module_id']);

        return response()->json(['success' => true, 'data' => $lessons]);
    }

    public function listByModule(string $moduleUuid): JsonResponse
    {
        $module = Module::where('uuid', $moduleUuid)->firstOrFail();
        $lessons = Lesson::where('module_id', $module->id)->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $lessons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'moduleUuid' => 'required|string|exists:modules,uuid',
            'type' => 'sometimes|in:video,quiz,flashcard,reading,assignment',
            'duration' => 'nullable|integer|min:0',
            'content' => 'nullable|array',
        ]);

        $module = Module::where('uuid', $request->moduleUuid)->firstOrFail();
        $maxOrder = Lesson::where('module_id', $module->id)->max('order') ?? 0;

        $lesson = Lesson::create([
            'title' => $request->title,
            'module_id' => $module->id,
            'order' => $maxOrder + 1,
            'type' => $request->get('type', 'reading'),
            'duration' => $request->duration,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully',
            'data' => $lesson,
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $lesson = Lesson::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'type' => 'sometimes|in:video,quiz,flashcard,reading,assignment',
            'duration' => 'nullable|integer|min:0',
            'content' => 'nullable|array',
            'order' => 'sometimes|integer|min:0',
        ]);

        $lesson->update($request->only(['title', 'type', 'duration', 'content', 'order']));

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully',
            'data' => $lesson,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $lesson = Lesson::where('uuid', $uuid)->firstOrFail();
        $lesson->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lesson deleted successfully',
        ]);
    }
}
