<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'lesson_id', 'questions', 'passing_score', 'status'];

    protected $casts = [
        'questions' => 'array',
        'passing_score' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
