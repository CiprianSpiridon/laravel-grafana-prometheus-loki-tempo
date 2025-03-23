<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;

abstract class DynamoDbBaseModel extends DynamoDbModel
{
    /**
     * DynamoDB doesn't support incremented IDs, so we'll use UUIDs
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The storage format of the model's date columns.
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }

            if ($model->timestamps) {
                $model->created_at = $model->freshTimestamp();
                $model->updated_at = $model->created_at;
            }
        });

        static::updating(function ($model) {
            if ($model->timestamps) {
                $model->updated_at = $model->freshTimestamp();
            }
        });
    }
}
