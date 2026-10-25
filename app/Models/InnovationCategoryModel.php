<?php

namespace App\Models;

use Database\Factories\InnovationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['parent_id', 'name', 'slug', 'icon'])]
#[UseFactory(InnovationCategoryFactory::class)]
class InnovationCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'innovation_categories';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function innovations(): HasMany
    {
        return $this->hasMany(InnovationModel::class, 'category_id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(IdeaModel::class, 'category_id');
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'kategori';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $childrenByParent = static::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [];
        $seen = [];
        $pending = [$this->id];

        while ($pending !== []) {
            $parentId = array_pop($pending);

            foreach ($childrenByParent->get($parentId, []) as $child) {
                if (isset($seen[$child->id])) {
                    continue;
                }

                $seen[$child->id] = true;
                $ids[] = $child->id;
                $pending[] = $child->id;
            }
        }

        return $ids;
    }
}
