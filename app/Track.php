<?php

namespace ThisDayInMusic;

use Illuminate\Database\Eloquent\Model;

class Track extends Model
{

    public $timestamps = false;

    /**
     * the artist of the track
     * @return Eloquent relationship
     */
    public function artist()
    {
        return $this->belongsTo('\ThisDayInMusic\Artist');
    }

    /**
     * Gets the events for the track
     */
    public function events()
    {
        return $this->hasMany('\ThisDayInMusic\Event');
    }

    public function playlists()
    {
        return $this->belongsToMany('\ThisDayInMusic\Playlist');
    }
}
