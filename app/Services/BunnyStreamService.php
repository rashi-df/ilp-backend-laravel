<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BunnyStreamService
{
    private string $apiKey;
    private string $libraryId;
    private string $baseUrl = 'https://video.bunnycdn.com';

    public function __construct()
    {
        $this->apiKey    = config('services.bunny.api_key', '');
        $this->libraryId = config('services.bunny.library_id', '');
    }

    public function createVideo(string $title): array
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept'    => 'application/json',
        ])->post("{$this->baseUrl}/library/{$this->libraryId}/videos", [
            'title' => $title,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to create video on Bunny.net: ' . $response->body());
        }

        return $response->json();
    }

    public function getTusCredentials(string $bunnyVideoId): array
    {
        $expiry    = time() + 3600;
        $signature = hash('sha256', $this->libraryId . $this->apiKey . $expiry . $bunnyVideoId);

        return [
            'videoId'    => $bunnyVideoId,
            'libraryId'  => $this->libraryId,
            'signature'  => $signature,
            'expiry'     => $expiry,
            'endpoint'   => 'https://video.bunnycdn.com/tusupload',
        ];
    }

    public function deleteVideo(string $bunnyVideoId): bool
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->delete("{$this->baseUrl}/library/{$this->libraryId}/videos/{$bunnyVideoId}");

        return $response->successful();
    }
}
