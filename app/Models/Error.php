<?php

namespace App\Models;

use Database\Factories\ErrorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fingerprint', 'server_name', 'log_source', 'message', 'solution', 'resolved', 'occurrence_count'])]
class Error extends Model
{
    public const LOG_SOURCE_SERVER = 'server';

    public const LOG_SOURCE_APPLICATION = 'application';

    /** @use HasFactory<ErrorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'resolved' => 'boolean',
            'occurrence_count' => 'integer',
        ];
    }
}
