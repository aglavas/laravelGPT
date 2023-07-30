<?php

namespace App\Models;

use App\Models\Enums\UsageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Usage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'usage_type',
        'usage_amount',
        'user_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'usage_type' => UsageType::class
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function usageable(): MorphTo
    {
        return $this->morphTo();
    }
}
