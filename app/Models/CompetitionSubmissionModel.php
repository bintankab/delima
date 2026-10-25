<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'competition_id', 'organisasi_id', 'submitted_by', 'innovation_id',
    'assigned_reviewer_id', 'title', 'description', 'status',
])]
class CompetitionSubmissionModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'competition_submissions';

    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionModel::class, 'competition_id');
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(OrganisasiModel::class, 'organisasi_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'submitted_by');
    }

    public function innovation(): BelongsTo
    {
        return $this->belongsTo(InnovationModel::class, 'innovation_id');
    }

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_reviewer_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(ReviewModel::class, 'reviewable');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }
}
