<?php

namespace ThisDayInMusic\Http\Transformers;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class EventTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
        'artist'
    ];

    protected $availableIncludes = [
        'track'
    ];

    public function transform(\ThisDayInMusic\Event $event)
    {
        $data = [
            'id' => (int) $event->id,
            'description' => (string) $event->description,
            'date' => $event->date,
            'type' => (string) $event->type,
        ];

        return $data;
    }

    public function includeArtist(\ThisDayInMusic\Event $event)
    {
        $artist = $event->artist;
        if ($artist) {
            return $this->item($artist, new \ThisDayInMusic\Http\Transformers\ArtistTransformer);
        }

    }

    public function includeTrack(\ThisDayInMusic\Event $event)
    {
        $track = $event->track;

        return $this->item($track, new \ThisDayInMusic\Http\Transformers\TrackTransformer);
    }
}
