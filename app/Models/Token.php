<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $table = 'tokens';

    protected $fillable = [
        'user_id', 'token',
    ];

    /**
     * Usuario al que pertenece el token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
