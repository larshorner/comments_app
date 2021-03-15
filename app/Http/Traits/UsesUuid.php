<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;

trait UsesUuid
{

    /**
     * Generate UUIDs instead of integers for primary keys
     */
    protected static function bootUsesUuid() {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Remove the autoincrement of ID seeing as we are using UUIDs
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Set the primary key type to string, because we are using UUIDs
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
