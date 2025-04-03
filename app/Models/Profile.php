<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A tömegesen kitölthető attribútumok.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'description',
        'image',
        'contact_info',
        'social_links',
        'meta_data',
        'visits',
        'is_public',
    ];

    /**
     * Automatikusan castolt mezők.
     *
     * @var array
     */
    protected $casts = [
        'contact_info' => 'json',
        'social_links' => 'json',
        'meta_data' => 'json',
        'is_public' => 'boolean',
        'visits' => 'integer',
    ];

    /**
     * A profilhoz tartozó felhasználó kapcsolat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A profilhoz tartozó kártya kapcsolat.
     */
    public function card(): HasOne
    {
        return $this->hasOne(Card::class);
    }

    /**
     * Növeli a látogatások számát.
     *
     * @return bool
     */
    public function incrementVisits(): bool
    {
        $this->visits++;
        return $this->save();
    }

    /**
     * Létrehoz egy alapértelmezett profilt a megadott felhasználóhoz.
     *
     * @param int $userId
     * @param string $name
     * @param string $type
     * @return static
     */
    public static function createDefault(int $userId, string $name = 'Alapértelmezett profil', string $type = 'personal'): self
    {
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'contact_info' => json_encode([]),
            'social_links' => json_encode([]),
        ]);
    }
}
