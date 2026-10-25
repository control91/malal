<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** First-party, cookie-less audience counters (daily aggregates only). */
class DailyPageView extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
