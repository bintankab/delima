<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['title', 'slug', 'description', 'registration_start', 'registration_end', 'tahun', 'status'])]
class Competition extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'registration_start' => 'date',
            'registration_end' => 'date',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CompetitionSubmission::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(225)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('banner');

        $this->addMediaConversion('medium')
            ->width(1200)
            ->height(630)
            ->nonQueued()
            ->performOnCollections('banner');
    }
}
