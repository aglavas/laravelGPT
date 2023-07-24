<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Source extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'sources';

    /**
     * Morphs to URL and File
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
