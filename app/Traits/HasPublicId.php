<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property int $id
 * @property string $public_id
 *
 */
trait HasPublicId
{
    use HasUlids;

    /**
     * Class has unique ids
     *
     * @return string[]
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }
}
