<?php

namespace App\Models;

use Database\Factories\FaqModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'category', 'order'])]
#[UseFactory(FaqModelFactory::class)]
class FaqModel extends Model
{
    use HasFactory;

    protected $table = 'faqs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
