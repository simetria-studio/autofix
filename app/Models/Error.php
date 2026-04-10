<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['server_name', 'message', 'solution', 'resolved'])]
class Error extends Model
{
    protected function casts(): array
    {
        return [
            'resolved' => 'boolean',
        ];
    }
}
