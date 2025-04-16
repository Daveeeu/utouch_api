<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'status',
        'user_id',
        'profile_id',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Kártya státuszok
     */
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    /**
     * Kapcsolat a felhasználóhoz
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kapcsolat a profilhoz
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Kapcsolat a kártyatípushoz
     */
    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class);
    }

    /**
     * Egyedi kód generálása
     */
    public static function generateUniqueCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            // Kód formázása (pl. XXXX-XXXX)
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Aktiválás metódus
     */
    public function activate()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            $this->status = self::STATUS_ACTIVE;
            $this->activated_at = now();


            $this->save();
        }

        return $this;
    }

    /**
     * Ellenőrizzük, hogy a kártya hozzá van-e rendelve felhasználóhoz
     */
    public function isAssigned(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Ellenőrizzük, hogy a kártya lejárt-e
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Ellenőrizzük, hogy a kártya aktív-e
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
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
    public function activateCard(int $userId, int $profileId): bool
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
