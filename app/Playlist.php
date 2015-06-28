<?php

namespace ThisDayInMusic;

use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    //

    public function tracks()
    {
        return $this->belongsToMany('\ThisDayInMusic\Track');
    }
}
