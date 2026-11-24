<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A visitor is a salted hash of (IP, user-agent, day): not reversible, not linkable across days. */
class DailyVisitor extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
