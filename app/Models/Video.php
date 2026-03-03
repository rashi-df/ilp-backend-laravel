<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasUuids;

    protected $fillable = [
        'title', 'description', 'vdo_cipher_id', 'bunny_video_id', 'duration', 'thumbnail',
        'course_id', 'lesson_id', 'status', 'file_size', 'view_count',
    ];

    protected $appends = ['embed_url', 'stream_url'];

    protected $casts = [
        'duration' => 'integer',
        'file_size' => 'integer',
        'view_count' => 'integer',
    ];

    public function getEmbedUrlAttribute(): ?string
    {
        if (!$this->bunny_video_id) {
            return null;
        }
        return 'https://iframe.mediadelivery.net/embed/' . config('services.bunny.library_id') . '/' . $this->bunny_video_id;
    }

    public function getStreamUrlAttribute(): ?string
    {
        if (!$this->bunny_video_id) return null;
        $host = config('services.bunny.pull_zone_hostname');
        return $host ? "https://{$host}/{$this->bunny_video_id}/playlist.m3u8" : null;
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
