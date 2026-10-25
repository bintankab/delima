<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'organisasi_id', 'assigned_to', 'subject', 'status'])]
class ConsultationModel extends Model
{
    use HasFactory;

    protected $table = 'consultations';

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(OrganisasiModel::class, 'organisasi_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ConsultationReplyModel::class, 'consultation_id');
    }
}
