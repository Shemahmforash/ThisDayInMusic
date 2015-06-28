<?php

namespace ThisDayInMusic;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    /**
     * Get the tracks for this artist.
     */
    public function tracks()
    {
        return $this->hasMany('\ThisDayInMusic\Track');
    }

    /**
     * Gets the events for the artist
     */
    public function events()
    {
        return $this->hasMany('\ThisDayInMusic\Event');
    }
}
