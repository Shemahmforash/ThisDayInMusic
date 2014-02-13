<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../bootstrap.php";

$artistRepository = $entityManager->getRepository('Artist');
$artists = $artistRepository->findBy(array("hasTracks" => NULL));

if( count( $artists ) == 0  ) {
    error_log( "no trackless artists. Exitting script." );
    exit;
}

foreach ( $artists as $artist ) {
    $name = $artist->getName();

    if(!$name)
        continue;

    #ignore artists that already have tracks
    if($artist->getTracks()->count()) {
        $entityManager->persist( $artist );
        continue;
    }

    error_log( "processing $name" );

    $query = http_build_query(array( 'artist' => $name,  'bucket' => array('id:spotify-WW', 'tracks'), 'limit'  => "true", "results" => 10));
    $query = preg_replace("/\%5B\d+\%5D/im", "", $query); 

    Echonest\Service\Echonest::configure("2FOIUUMCRLFMAWJXT");
    $response = Echonest\Service\Echonest::query('song', 'search', $query);

    $response = $response->response;

    if( $response && $response->status->code == 0 ) {
        $songs = $response->songs;

        $entityManager->persist( $artist );

        #no songs, move on to next artist
        if(!count($songs)) {
            $artist->setHasTracks( false );
            continue;
        }
        else {
            $artist->setHasTracks( true );
        }

        foreach( $songs as $song ) {
            $track = new \Track();
            $title = $song->title;
            $track->setName( $title );

            $foreign = array_shift( $song->tracks );
            if( $foreign ) {
                $spotifyId = $foreign->foreign_id;

                $track->setSpotifyId( $spotifyId );
            }

            $artist->addTrack( $track );
            $track->assignToArtist( $artist );

            $entityManager->persist( $track );
            
        }
    }
    else {
        echo "error: " . $response->status->message . "\n";
        break;
    }
}

$entityManager->flush();

?>
