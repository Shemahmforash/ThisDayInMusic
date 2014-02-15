<?php
    require_once __DIR__ . "/../vendor/autoload.php";
    require_once __DIR__ . "/../bootstrap.php";

    $date = new DateTime("now");

    $query = $entityManager->createQuery('SELECT count(p) FROM Playlist p WHERE p.date = :date');
    $query->setParameter("date", $date->format('Y-m-d'));
    $existPlaylist = $query->getSingleScalarResult();

    if( $existPlaylist ) {
        error_log( "playlist for " . $date->format('Y-m-d') . " already exist. Exitting script." );
        exit;
    }

    $track_list = array();

    #find all the events for this day, as well as the event artists and tracks
    $query = $entityManager->createQuery('SELECT e,a FROM Event e JOIN e.artist a WHERE e.date LIKE :date');
    $query->setParameter("date", "%" . $date->format('m-d'));
    $events = $query->getResult();

    $artistRepository = $entityManager->getRepository('Artist');
    $artists = $artistRepository->findBy(array("hasTracks" => NULL));

    #do not show playlist while the cron hasn't set all artist tracks
    if( count( $artists ) ) {
        return array(
            "code" => -2,
            "status" => "No tracks for playlist found. Please try again later."
        );
    }

    #find tracks for each event artist and create a playlist with a track for each one of the artists
    foreach ( $events as $event ) {
        $artist = $event->getArtist();

        #trackless artist will not enter the playlist
        if(!$artist->getTracks()->count() ) {
            error_log("trackless: " . $artist->getName() . ". Not adding to playlist" );
            continue;
        }

        $tracks = $artist->getUnPublishedTracks();

        #ignore artists with all the tracks already published
        if( !count($tracks) )
            continue;

        $track = array_shift( $tracks );

        $event->setTrack( $track );
        $track->assignToEvent( $event );

        $entityManager->persist( $track );
        $entityManager->persist( $event );

        #add to playlist tracks
        array_push( $track_list, $track );
    }

    #create new playlist from the tracks found
    if( count( $track_list ) ) {
        $playlist = new \Playlist();
        $playlist->setDate( $date );

        foreach ( $track_list as $track ) {
            $playlist->addTrack( $track );
        }

        $entityManager->persist( $playlist );

        //update db
        $entityManager->flush();
    }

?>
