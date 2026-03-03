<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    // ==================== TEMPLATES ====================

    public function listTemplates(Request $request): JsonResponse
    {
        $templates = CertificateTemplate::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function getTemplate(string $uuid): JsonResponse
    {
        $template = CertificateTemplate::where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function createTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'subtitle' => 'sometimes|string',
            'bodyText' => 'sometimes|string',
            'signatureName' => 'nullable|string',
            'signatureTitle' => 'nullable|string',
            'logoUrl' => 'nullable|string',
            'borderStyle' => 'sometimes|in:classic,modern,ornate,minimal',
            'isDefault' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($request->boolean('isDefault', false)) {
            CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $template = CertificateTemplate::create([
            'title' => $request->title,
            'subtitle' => $request->get('subtitle', 'has successfully completed'),
            'body_text' => $request->get('bodyText', 'This is to certify that'),
            'signature_name' => $request->signatureName,
            'signature_title' => $request->signatureTitle,
            'logo_url' => $request->logoUrl,
            'border_style' => $request->get('borderStyle', 'classic'),
            'is_default' => $request->boolean('isDefault', false),
            'status' => $request->get('status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);
    }

    public function updateTemplate(Request $request, string $uuid): JsonResponse
    {
        $template = CertificateTemplate::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string',
            'subtitle' => 'sometimes|string',
            'bodyText' => 'sometimes|string',
            'signatureName' => 'nullable|string',
            'signatureTitle' => 'nullable|string',
            'logoUrl' => 'nullable|string',
            'borderStyle' => 'sometimes|in:classic,modern,ornate,minimal',
            'isDefault' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($request->has('isDefault') && $request->boolean('isDefault')) {
            CertificateTemplate::where('is_default', true)->where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        $data = array_filter([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'body_text' => $request->bodyText,
            'signature_name' => $request->signatureName,
            'signature_title' => $request->signatureTitle,
            'logo_url' => $request->logoUrl,
            'border_style' => $request->borderStyle,
            'is_default' => $request->has('isDefault') ? $request->boolean('isDefault') : null,
            'status' => $request->status,
        ], fn($v) => $v !== null);

        $template->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template,
        ]);
    }

    public function deleteTemplate(string $uuid): JsonResponse
    {
        $template = CertificateTemplate::where('uuid', $uuid)->firstOrFail();

        if ($template->certificates()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete template with associated certificates',
            ], 400);
        }

        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted successfully']);
    }

    // ==================== CERTIFICATES ====================

    public function index(Request $request): JsonResponse
    {
        $query = Certificate::with('student', 'course', 'template');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('student_name', 'ilike', "%{$request->search}%")
                  ->orWhere('certificate_id', 'ilike', "%{$request->search}%");
            });
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $certs = $query->orderBy('issue_date', 'desc')
                       ->skip(($page - 1) * $limit)
                       ->take($limit)
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $certs,
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
        $cert = Certificate::with('student', 'course', 'template')
            ->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $cert]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'studentUuid' => 'required|string|exists:users,uuid',
            'courseUuid' => 'required|string|exists:courses,uuid',
            'templateUuid' => 'nullable|string|exists:certificate_templates,uuid',
            'issueDate' => 'sometimes|date',
            'expiryDate' => 'nullable|date',
        ]);

        $student = User::where('uuid', $request->studentUuid)->firstOrFail();
        $course = Course::where('uuid', $request->courseUuid)->firstOrFail();
        $templateId = $request->templateUuid
            ? CertificateTemplate::where('uuid', $request->templateUuid)->value('id')
            : CertificateTemplate::where('is_default', true)->value('id');

        // Generate certificate ID: CERT-YYYY-NNNNN
        $year = now()->year;
        $count = Certificate::whereYear('created_at', $year)->count() + 1;
        $certId = 'CERT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $cert = Certificate::create([
            'certificate_id' => $certId,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'template_id' => $templateId,
            'student_name' => $student->name,
            'student_email' => $student->email,
            'course_name' => $course->title,
            'issue_date' => $request->get('issueDate', now()),
            'expiry_date' => $request->expiryDate,
            'status' => 'issued',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificate issued successfully',
            'data' => $cert->load('student', 'course', 'template'),
        ], 201);
    }

    public function revoke(Request $request, string $uuid): JsonResponse
    {
        $cert = Certificate::where('uuid', $uuid)->firstOrFail();

        if ($cert->status === 'revoked') {
            return response()->json(['success' => false, 'message' => 'Certificate already revoked'], 400);
        }

        $request->validate(['revokeReason' => 'nullable|string']);

        $cert->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $request->revokeReason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificate revoked successfully',
            'data' => $cert,
        ]);
    }

    public function verify(string $certificateId): JsonResponse
    {
        $cert = Certificate::with('student', 'course', 'template')
            ->where('certificate_id', $certificateId)->first();

        if (!$cert) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'certificateId' => $cert->certificate_id,
                'studentName' => $cert->student_name,
                'courseName' => $cert->course_name,
                'issueDate' => $cert->issue_date,
                'expiryDate' => $cert->expiry_date,
                'status' => $cert->status,
                'isValid' => $cert->status === 'issued',
            ],
        ]);
    }
}
