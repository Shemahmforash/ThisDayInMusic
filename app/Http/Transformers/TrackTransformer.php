<?php

namespace ThisDayInMusic\Http\Transformers;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class TrackTransformer extends TransformerAbstract
{

    protected $availableIncludes = [
        'events', 'artist', 'playlists'
    ];

    public function transform(\ThisDayInMusic\Track $track)
    {
        $data = [
            'id' => (int) $track->id,
            'name' => (string) $track->name,
        ];

        return $data;
    }

    public function includeEvents(\ThisDayInMusic\Track $track)
    {
        $events = $track->events;

        return $this->collection($events, new \ThisDayInMusic\Http\Transformers\EventTransformer);
    }

    public function includePlaylists(\ThisDayInMusic\Track $track)
    {
        $playlists = $track->playlists;

        return $this->collection($playlists, new \ThisDayInMusic\Http\Transformers\PlaylistTransformer);
    }

    public function includeArtist(\ThisDayInMusic\Track $track)
    {
        $artist = $track->artist;

        return $this->artist($tracks, new \ThisDayInMusic\Http\Transformers\ArtistTransformer);
    }
}
