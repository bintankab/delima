<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_id', 'title', 'description', 'status', 'admin_notes'])]
class IdeaModel extends Model
{
    use HasFactory;

    protected $table = 'ideas';

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InnovationCategoryModel::class, 'category_id');
    }
}
