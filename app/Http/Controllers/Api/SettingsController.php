<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function get(): JsonResponse
    {
        $settings = Settings::getSettings();

        return response()->json([
            'success' => true,
            'data' => $this->formatSettings($settings),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'appName' => 'sometimes|string',
            'supportEmail' => 'nullable|email',
            'contactPhone' => 'nullable|string',
            'website' => 'nullable|string',
            'paymentProvider' => 'sometimes|in:stripe,razorpay,paypal,manual',
            'paymentApiKey' => 'nullable|string',
            'paymentSecretKey' => 'nullable|string',
            'paymentCurrency' => 'sometimes|string|size:3',
            'smtpHost' => 'nullable|string',
            'smtpPort' => 'sometimes|integer',
            'smtpUsername' => 'nullable|string',
            'smtpPassword' => 'nullable|string',
        ]);

        $settings = Settings::getSettings();

        $data = [];
        $fieldMap = [
            'appName' => 'app_name',
            'supportEmail' => 'support_email',
            'contactPhone' => 'contact_phone',
            'website' => 'website',
            'paymentProvider' => 'payment_provider',
            'paymentApiKey' => 'payment_api_key',
            'paymentSecretKey' => 'payment_secret_key',
            'paymentCurrency' => 'payment_currency',
            'smtpHost' => 'smtp_host',
            'smtpPort' => 'smtp_port',
            'smtpUsername' => 'smtp_username',
            'smtpPassword' => 'smtp_password',
        ];

        foreach ($fieldMap as $requestKey => $dbKey) {
            if ($request->has($requestKey)) {
                $data[$dbKey] = $request->input($requestKey);
            }
        }

        $settings->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $this->formatSettings($settings->fresh()),
        ]);
    }

    private function formatSettings(Settings $settings): array
    {
        return [
            'app' => [
                'name' => $settings->app_name,
                'supportEmail' => $settings->support_email,
                'contactPhone' => $settings->contact_phone,
                'website' => $settings->website,
            ],
            'payment' => [
                'provider' => $settings->payment_provider,
                'apiKey' => $settings->payment_api_key,
                'secretKey' => $settings->payment_secret_key ? '••••••' : null,
                'currency' => $settings->payment_currency,
            ],
            'email' => [
                'smtpHost' => $settings->smtp_host,
                'smtpPort' => $settings->smtp_port,
                'smtpUsername' => $settings->smtp_username,
                'smtpPassword' => $settings->smtp_password ? '••••••' : null,
            ],
        ];
    }
}
