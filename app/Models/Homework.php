<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    use HasUuids;

    protected $table = 'homework';

    protected $fillable = [
        'title', 'lesson_id', 'course_id', 'instructions',
        'submission_type', 'due_date', 'max_score', 'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_score' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }
}
