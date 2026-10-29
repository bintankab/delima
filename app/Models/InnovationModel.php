<?php

namespace App\Models;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use Database\Factories\InnovationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'user_id', 'inisiator', 'category_id', 'assigned_reviewer_id',
    'title', 'slug', 'description', 'latar_belakang', 'manfaat', 'link_video', 'link_file_pendukung', 'jenis_inovasi', 'tahapan',
    'tahun', 'status', 'origin', 'is_featured', 'testing_at', 'implementation_at',
])]
#[UseFactory(InnovationFactory::class)]
class InnovationModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'innovations';

    protected function casts(): array
    {
        return [
            'jenis_inovasi' => JenisInovasi::class,
            'inisiator' => JenisInisiator::class,
            'tahapan' => TahapanInovasi::class,
            'is_featured' => 'boolean',
            'testing_at' => 'datetime',
            'implementation_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InnovationCategoryModel::class, 'category_id');
    }

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_reviewer_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(ReviewModel::class, 'reviewable');
    }

    public function competitionSubmissions(): HasMany
    {
        return $this->hasMany(CompetitionSubmissionModel::class, 'innovation_id');
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'inovasi';
        }

        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('cover', 'gallery');

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->nonQueued()
            ->performOnCollections('cover', 'gallery');
    }
}
