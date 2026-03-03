<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function __construct(
        private BunnyStreamService $bunny,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Video::with('course', 'lesson');

        if ($request->search) {
            $query->where('title', 'ilike', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $videos = $query->orderBy('created_at', 'desc')
                        ->skip(($page - 1) * $limit)
                        ->take($limit)
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $videos,
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
        $video = Video::with('course', 'lesson')->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function getUploadCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        $bunnyVideo    = $this->bunny->createVideo($request->title);
        $bunnyVideoId  = $bunnyVideo['guid'];
        $tusCredentials = $this->bunny->getTusCredentials($bunnyVideoId);

        $video = Video::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'bunny_video_id' => $bunnyVideoId,
            'status'         => 'processing',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'video'          => $video,
                'tusCredentials' => $tusCredentials,
            ],
        ]);
    }

    public function confirmUpload(Request $request, string $uuid): JsonResponse
    {
        $video = Video::where('uuid', $uuid)->firstOrFail();
        $video->update(['status' => 'ready']);

        return response()->json([
            'success' => true,
            'message' => 'Upload confirmed',
            'data' => $video,
        ]);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $video = Video::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:processing,ready,failed',
        ]);

        $video->update($request->only(['title', 'description', 'thumbnail', 'duration', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully',
            'data' => $video,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $video = Video::where('uuid', $uuid)->firstOrFail();

        if ($video->bunny_video_id) {
            try {
                $this->bunny->deleteVideo($video->bunny_video_id);
            } catch (\Exception $e) {
                // Log but don't fail if Bunny delete fails
            }
        } elseif ($video->vdo_cipher_id) {
            try {
                $this->vdoCipher->deleteVideo($video->vdo_cipher_id);
            } catch (\Exception $e) {
                // Log but don't fail if VdoCipher delete fails
            }
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }

    public function getOtp(string $uuid): JsonResponse
    {
        $video = Video::where('uuid', $uuid)->firstOrFail();

        if (!$video->vdo_cipher_id) {
            return response()->json([
                'success' => false,
                'message' => 'No VdoCipher ID for this video',
            ], 400);
        }

        $otp = $this->vdoCipher->getVideoOtp($video->vdo_cipher_id);

        $video->increment('view_count');

        return response()->json([
            'success' => true,
            'data' => $otp,
        ]);
    }

    public function linkToLesson(Request $request, string $uuid): JsonResponse
    {
        $video = Video::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'courseUuid' => 'nullable|string|exists:courses,uuid',
            'lessonUuid' => 'nullable|string|exists:lessons,uuid',
        ]);

        $courseId = $request->courseUuid
            ? Course::where('uuid', $request->courseUuid)->value('id')
            : null;
        $lessonId = $request->lessonUuid
            ? Lesson::where('uuid', $request->lessonUuid)->value('id')
            : null;

        $video->update([
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video linked successfully',
            'data' => $video,
        ]);
    }
}
