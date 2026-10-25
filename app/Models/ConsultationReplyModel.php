<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['consultation_id', 'user_id', 'message'])]
class ConsultationReplyModel extends Model
{
    use HasFactory;

    protected $table = 'consultation_replies';

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(ConsultationModel::class, 'consultation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
