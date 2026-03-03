<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VdoCipherService
{
    private string $apiSecret;
    private string $baseUrl = 'https://dev.vdocipher.com/api';

    public function __construct()
    {
        $this->apiSecret = config('services.vdocipher.secret', env('VDOCIPHER_API_SECRET', ''));
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Apisecret {$this->apiSecret}",
            'Content-Type' => 'application/json',
        ];
    }

    public function getUploadCredentials(string $title): array
    {
        $response = Http::withHeaders($this->headers())
            ->put("{$this->baseUrl}/videos", ['title' => $title]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get upload credentials from VdoCipher: ' . $response->body());
        }

        return $response->json();
    }

    public function getVideoOtp(string $videoId, int $ttl = 300): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/videos/{$videoId}/otp", ['ttl' => $ttl]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get video OTP from VdoCipher: ' . $response->body());
        }

        return $response->json();
    }

    public function getVideoInfo(string $videoId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/videos/{$videoId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to get video info from VdoCipher: ' . $response->body());
        }

        return $response->json();
    }

    public function deleteVideo(string $videoId): bool
    {
        $response = Http::withHeaders($this->headers())
            ->delete("{$this->baseUrl}/videos", ['videos' => $videoId]);

        return $response->successful();
    }

    public function listVideos(int $page = 1, int $limit = 20): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/videos", [
                'page' => $page,
                'limit' => $limit,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to list videos from VdoCipher: ' . $response->body());
        }

        return $response->json();
    }
}
