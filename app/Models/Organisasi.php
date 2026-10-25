<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'type', 'code', 'address', 'contact_person', 'phone', 'email', 'is_active'])]
class Organisasi extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['jabatan', 'is_primary'])
            ->withTimestamps();
    }

    public function innovations(): HasMany
    {
        return $this->hasMany(Innovation::class);
    }

    public function competitionSubmissions(): HasMany
    {
        return $this->hasMany(CompetitionSubmission::class);
    }

    public function libraryDocuments(): HasMany
    {
        return $this->hasMany(LibraryDocument::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('logo');
    }
}
