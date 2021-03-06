<?php

namespace ThisDayInMusic;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    public $timestamps = false;

    public function artist()
    {
        return $this->belongsTo('\ThisDayInMusic\Artist');
    }

    public function track()
    {
        return $this->belongsTo('\ThisDayInMusic\Track');
    }

}
