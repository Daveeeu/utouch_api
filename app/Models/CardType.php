<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'valid_days',
        'price',
        'features',
    ];

    protected $casts = [
        'valid_days' => 'integer',
        'price' => 'float',
        'features' => 'array',
    ];

    /**
     * Kapcsolat a kártyákhoz
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}
