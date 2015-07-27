<?php

namespace ThisDayInMusic\Http\Transformers;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class ArtistTransformer extends TransformerAbstract
{

    protected $defaultIncludes = [
        'tracks'
    ];

    protected $availableIncludes = [
        'events'
    ];

    public function transform(\ThisDayInMusic\Artist $artist)
    {
        $data = [
            'id' => (int) $artist->id,
            'name' => (string) $artist->name,
        ];

        return $data;
    }

    public function includeEvents(\ThisDayInMusic\Artist $artist)
    {
        $events = $artist->events;

        return $this->collection($events, new \ThisDayInMusic\Http\Transformers\EventTransformer);
    }

    public function includeTracks(\ThisDayInMusic\Artist $artist)
    {
        $tracks = $artist->tracks;

        return $this->collection($tracks, new \ThisDayInMusic\Http\Transformers\TrackTransformer);
    }
}
