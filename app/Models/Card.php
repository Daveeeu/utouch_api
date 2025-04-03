<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A tömegesen kitölthető attribútumok.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'status',
        'user_id',
        'profile_id',
        'activated_at',
    ];

    /**
     * Automatikusan castolt mezők.
     *
     * @var array
     */
    protected $casts = [
        'activated_at' => 'datetime',
    ];

    /**
     * A kártyához tartozó felhasználó kapcsolat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A kártyához tartozó profil kapcsolat.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Ellenőrzi, hogy a kártya aktiválható-e.
     *
     * @return bool
     */
    public function isActivatable(): bool
    {
        return $this->status === 'inactive' && $this->user_id === null;
    }

    /**
     * Aktiválja a kártyát a megadott felhasználóhoz.
     *
     * @param int $userId
     * @param int $profileId
     * @return bool
     */
    public function activate(int $userId, int $profileId): bool
    {
        if (!$this->isActivatable()) {
            return false;
        }

        $this->user_id = $userId;
        $this->profile_id = $profileId;
        $this->status = 'active';
        $this->activated_at = now();

        return $this->save();
    }
}
