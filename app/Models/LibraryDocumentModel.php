<?php

namespace App\Models;

use Database\Factories\LibraryDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

#[Fillable([
    'organisasi_id', 'uploaded_by', 'title', 'slug', 'nomor_dokumen', 'penyusun',
    'tahun_terbit', 'tanggal_terbit', 'description', 'type', 'is_public',
])]
#[UseFactory(LibraryDocumentFactory::class)]
class LibraryDocumentModel extends Model implements HasMedia
{
    use HasFactory, HasTags, InteractsWithMedia, SoftDeletes;

    protected $table = 'library_documents';

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'tanggal_terbit' => 'date',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(OrganisasiModel::class, 'organisasi_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'uploaded_by');
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'dokumen';
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
        $this->addMediaCollection('file')->singleFile();
    }
}
