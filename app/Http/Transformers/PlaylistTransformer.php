<?php

namespace ThisDayInMusic\Http\Transformers;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class PlaylistTransformer extends TransformerAbstract
{

    protected $defaultIncludes = [
        'tracks'
    ];

    public function transform(\ThisDayInMusic\Playlist $playlist)
    {
        $data = [
            'id' => (int) $playlist->id,
            'name' => (string) $playlist->name,
        ];

        return $data;
    }

    public function includeTracks(\ThisDayInMusic\Playlist $playlist)
    {
        $tracks = $playlist->tracks;

        return $this->collection($tracks, new \ThisDayInMusic\Http\Transformers\TrackTransformer);
    }
}
