<?php

require_once "../vendor/autoload.php";
require_once "../bootstrap.php";

/*
#find trackless artists
$qb = $entityManager->createQueryBuilder();
$qb->select( "a.name" )
    ->from('Artist', 'a')
#    ->setFirstResult( 6 )
    ->setMaxResults( 10 )
    ;
$artists = $qb->getQuery()->getResult();
*/

$artistRepository = $entityManager->getRepository('Artist');
$artists = $artistRepository->findAll();

$count = 0;

foreach ( $artists as $artist ) {
    $name = $artist->getName();

    if(!$name)
        continue;

    #ignore artists that already have tracks
    if($artist->getTracks()->count())
        continue;

    $query = http_build_query(array( 'artist' => $name,  'bucket' => array('id:spotify-WW', 'tracks'), 'limit'  => "true", "results" => 10));
    $query = preg_replace("/\%5B\d+\%5D/im", "", $query); 

    Echonest\Service\Echonest::configure("2FOIUUMCRLFMAWJXT");
    $response = Echonest\Service\Echonest::query('song', 'search', $query);

    $response = $response->response;

    if( $response && $response->status->code == 0 ) {
        $songs = $response->songs;

        #no songs, move on to next artist
        if(!count($songs))
            continue;

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
            $entityManager->persist( $artist );
            
        }
    }

    $count++;
}

$entityManager->flush();

?>
