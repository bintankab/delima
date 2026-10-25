<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'category', 'order'])]
class FaqModel extends Model
{
    use HasFactory;

    protected $table = 'faqs';
}
