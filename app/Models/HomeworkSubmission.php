<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'homework_id', 'student_id', 'student_name', 'student_email',
        'content', 'file_url', 'assigned_mentor_id', 'mentor_name',
        'status', 'feedback', 'grade', 'submitted_at', 'reviewed_at',
    ];

    protected $casts = [
        'grade' => 'float',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assignedMentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_mentor_id');
    }
}
