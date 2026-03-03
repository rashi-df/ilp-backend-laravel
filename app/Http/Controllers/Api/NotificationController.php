<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::with('creator');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->targetAudience) {
            $query->where('target_audience', $request->targetAudience);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $notifications = $query->orderBy('created_at', 'desc')
                                ->skip(($page - 1) * $limit)
                                ->take($limit)
                                ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
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
        $notification = Notification::with('creator')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $notification]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'targetAudience' => 'sometimes|in:all,students,mentors,premium,basic,inactive,course_specific',
            'targetCourse' => 'nullable|string',
            'scheduledAt' => 'nullable|date',
            'status' => 'sometimes|in:draft,scheduled',
        ]);

        $status = $request->get('status', 'draft');
        if ($request->scheduledAt) {
            $status = 'scheduled';
        }

        $notification = Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'target_audience' => $request->get('targetAudience', 'all'),
            'target_course' => $request->targetCourse,
            'scheduled_at' => $request->scheduledAt,
            'status' => $status,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification,
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a sent notification',
            ], 400);
        }

        $request->validate([
            'title' => 'sometimes|string',
            'message' => 'sometimes|string',
            'targetAudience' => 'sometimes|in:all,students,mentors,premium,basic,inactive,course_specific',
            'targetCourse' => 'nullable|string',
            'scheduledAt' => 'nullable|date',
            'status' => 'sometimes|in:draft,scheduled',
        ]);

        $data = $request->only(['title', 'message', 'status']);
        if ($request->has('targetAudience')) {
            $data['target_audience'] = $request->targetAudience;
        }
        if ($request->has('targetCourse')) {
            $data['target_course'] = $request->targetCourse;
        }
        if ($request->has('scheduledAt')) {
            $data['scheduled_at'] = $request->scheduledAt;
        }

        $notification->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully',
            'data' => $notification,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a sent notification',
            ], 400);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
    }

    public function send(string $uuid): JsonResponse
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        if ($notification->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Notification already sent',
            ], 400);
        }

        // In a real app, you'd dispatch a job to send to recipients.
        // Here we update status and record sent time.
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_count' => 0, // Would be computed in real implementation
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => $notification,
        ]);
    }
}
