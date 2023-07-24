<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SourceFile extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'source_files';

    /**
     * @var string[]
     */
    protected $fillable = ['filename', 'filepath'];

    /**
     * @return MorphOne
     */
    public function source(): MorphOne
    {
        return $this->morphOne(Source::class, 'sourceable');
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::created(function ($model) {
            $source = new Source();
            $source->sourceable()->associate($model);
            $source->save();
        });
    }
}
