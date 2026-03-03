<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DragDropActivity;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    // ==================== QUIZZES ====================

    public function listQuizzes(Request $request): JsonResponse
    {
        $query = Quiz::with('lesson');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $quizzes = $query->orderBy('created_at', 'desc')
                         ->skip(($page - 1) * $limit)
                         ->take($limit)
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getQuiz(string $uuid): JsonResponse
    {
        $quiz = Quiz::with('lesson')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $quiz]);
    }

    public function createQuiz(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'lessonUuid' => 'required|string|exists:lessons,uuid',
            'questions' => 'sometimes|array',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.correctAnswer' => 'required|integer|between:0,3',
            'questions.*.explanation' => 'nullable|string',
            'passingScore' => 'sometimes|integer|between:0,100',
            'status' => 'sometimes|in:draft,published',
        ]);

        $lessonId = Lesson::where('uuid', $request->lessonUuid)->value('id');

        $quiz = Quiz::create([
            'title' => $request->title,
            'lesson_id' => $lessonId,
            'questions' => $request->get('questions', []),
            'passing_score' => $request->get('passingScore', 70),
            'status' => $request->get('status', 'draft'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz created successfully',
            'data' => $quiz->load('lesson'),
        ], 201);
    }

    public function updateQuiz(Request $request, string $uuid): JsonResponse
    {
        $quiz = Quiz::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'questions' => 'sometimes|array',
            'passingScore' => 'sometimes|integer|between:0,100',
            'status' => 'sometimes|in:draft,published',
        ]);

        $data = $request->only(['title', 'status']);
        if ($request->has('questions')) {
            $data['questions'] = $request->questions;
        }
        if ($request->has('passingScore')) {
            $data['passing_score'] = $request->passingScore;
        }

        $quiz->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully',
            'data' => $quiz,
        ]);
    }

    public function deleteQuiz(string $uuid): JsonResponse
    {
        Quiz::where('uuid', $uuid)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Quiz deleted successfully']);
    }

    // ==================== FLASHCARD SETS ====================

    public function listFlashcardSets(Request $request): JsonResponse
    {
        $query = FlashcardSet::with('lesson');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $sets = $query->orderBy('created_at', 'desc')
                      ->skip(($page - 1) * $limit)
                      ->take($limit)
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $sets,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getFlashcardSet(string $uuid): JsonResponse
    {
        $set = FlashcardSet::with('lesson')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $set]);
    }

    public function createFlashcardSet(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'lessonUuid' => 'required|string|exists:lessons,uuid',
            'cards' => 'sometimes|array',
            'cards.*.front' => 'required|string',
            'cards.*.back' => 'required|string',
            'cards.*.hint' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
        ]);

        $lessonId = Lesson::where('uuid', $request->lessonUuid)->value('id');

        $set = FlashcardSet::create([
            'title' => $request->title,
            'lesson_id' => $lessonId,
            'cards' => $request->get('cards', []),
            'status' => $request->get('status', 'draft'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flashcard set created successfully',
            'data' => $set->load('lesson'),
        ], 201);
    }

    public function updateFlashcardSet(Request $request, string $uuid): JsonResponse
    {
        $set = FlashcardSet::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'cards' => 'sometimes|array',
            'status' => 'sometimes|in:draft,published',
        ]);

        $data = $request->only(['title', 'status']);
        if ($request->has('cards')) {
            $data['cards'] = $request->cards;
        }

        $set->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Flashcard set updated successfully',
            'data' => $set,
        ]);
    }

    public function deleteFlashcardSet(string $uuid): JsonResponse
    {
        FlashcardSet::where('uuid', $uuid)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Flashcard set deleted successfully']);
    }

    // ==================== DRAG & DROP ====================

    public function listDragDropActivities(Request $request): JsonResponse
    {
        $query = DragDropActivity::with('lesson');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $activities = $query->orderBy('created_at', 'desc')
                            ->skip(($page - 1) * $limit)
                            ->take($limit)
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getDragDropActivity(string $uuid): JsonResponse
    {
        $activity = DragDropActivity::with('lesson')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $activity]);
    }

    public function createDragDropActivity(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'lessonUuid' => 'required|string|exists:lessons,uuid',
            'type' => 'sometimes|in:ordering,matching',
            'items' => 'sometimes|array',
            'instructions' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
        ]);

        $lessonId = Lesson::where('uuid', $request->lessonUuid)->value('id');

        $activity = DragDropActivity::create([
            'title' => $request->title,
            'lesson_id' => $lessonId,
            'type' => $request->get('type', 'ordering'),
            'items' => $request->get('items', []),
            'instructions' => $request->instructions,
            'status' => $request->get('status', 'draft'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully',
            'data' => $activity->load('lesson'),
        ], 201);
    }

    public function updateDragDropActivity(Request $request, string $uuid): JsonResponse
    {
        $activity = DragDropActivity::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'type' => 'sometimes|in:ordering,matching',
            'items' => 'sometimes|array',
            'instructions' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
        ]);

        $data = $request->only(['title', 'type', 'instructions', 'status']);
        if ($request->has('items')) {
            $data['items'] = $request->items;
        }

        $activity->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'data' => $activity,
        ]);
    }

    public function deleteDragDropActivity(string $uuid): JsonResponse
    {
        DragDropActivity::where('uuid', $uuid)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Activity deleted successfully']);
    }
}
