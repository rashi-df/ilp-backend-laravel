<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function listByCourse(string $courseUuid): JsonResponse
    {
        $course = Course::where('uuid', $courseUuid)->firstOrFail();
        $modules = Module::with('lessons')
            ->where('course_id', $course->id)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'courseUuid' => 'required|string|exists:courses,uuid',
            'description' => 'nullable|string',
        ]);

        $course = Course::where('uuid', $request->courseUuid)->firstOrFail();
        $maxOrder = Module::where('course_id', $course->id)->max('order') ?? 0;

        $module = Module::create([
            'title' => $request->title,
            'course_id' => $course->id,
            'order' => $maxOrder + 1,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
            'data' => $module,
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $module = Module::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer|min:0',
        ]);

        $module->update($request->only(['title', 'description', 'order']));

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully',
            'data' => $module,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $module = Module::where('uuid', $uuid)->firstOrFail();
        $module->lessons()->delete();
        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'modules' => 'required|array',
            'modules.*.uuid' => 'required|string',
            'modules.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->modules as $item) {
            Module::where('uuid', $item['uuid'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modules reordered successfully',
        ]);
    }
}
