<?php

//cron each once a day at 00:00
// 0 0 * * * /usr/bin/php pathtoproject/bin/artistTracks.php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../bootstrap.php";

$date = new DateTime("now");

$query = $entityManager->createQuery('SELECT count(e) FROM Event e WHERE e.date LIKE :date');
$query->setParameter("date", "%" . $date->format('m-d'));
$eventNumber = $query->getSingleScalarResult();

if( $eventNumber ) {
    error_log( "events for " . $date->format('Y-m-d') . " already exist. Exitting script." );
    exit;
}

$dim = new \ThisDayIn\Music($date->format('j'), $date->format('F'));
$evs = $dim->getEvents();

foreach($evs as $ev ) {
    $date   = new \DateTime( $ev['date'] );

    if( $ev['type'] === 'Death') {
        $ev['description'] = sprintf('%s, %s', $ev['name'], $ev['description']);
    }

    //unlike the death events, the birth events do not include enough information in the description.
    if( $ev['type'] === 'Birth') {
        $ev['description'] = sprintf('%s, %s was born', $ev['name'], $ev['description']);
    }

    //must find artist name for these kind of events
    if( $ev['type'] == 'Event') {
        $artist = findEventArtist( $ev['description'] );

        $ev['name'] = $artist['name'];
        if(isset( $artist['spotifyId'] ) )
            $ev['spotifyId'] = $artist['spotifyId'];
    }
    #TODO: find artist spotify id for the other event types

    //set current event
    $event = new \Event(); 
    $event->setDate( $date );
    $event->setDescription( $ev['description'] ); 
    $event->setType( $ev['type'] ); 
    $event->setSource( $dim->getSource() ); 
    $entityManager->persist( $event );

    //connects the event to an artist
    if( $ev['name'] ) {
        $artist = $entityManager->getRepository('Artist')->findBy(array('name' => $ev['name']));

        $artist = array_shift( $artist );

        if(!$artist) {
            $artist = new \Artist();
            $artist->setName( $ev['name'] );

            $event->setArtist( $artist );

            if(isset( $ev['spotifyId'] ) )
                $artist->setSpotifyId($ev['spotifyId']);
        }

        error_log("artist name: " . $artist->getName() );

        $artist->assignToEvent( $event );

        $entityManager->persist( $artist );

        #one must save here so it copes for repeated artist in the event list
        $entityManager->flush();
    }
}

//insert all events to db
if( count( $evs ) ) {
    $entityManager->flush();
}

function findEventArtist( $text ) {

    #get the configuration file for the app
    $file = file_get_contents(__DIR__ . "/../etc/config.json");
    $config = json_decode( $file, true );

    \Echonest\Service\Echonest::configure($config['echonest']['key']);

    $response = \Echonest\Service\Echonest::query('artist', 'extract', array(
        'text'    => $text,
        'sort'    => 'hotttnesss-desc',
        'results' => '1',
        'bucket'  => 'id:spotify-WW',
    ));

    if( $response && $response->response->status->code == 0 ) {

        $artists =$response->response->artists;

        if( !is_array(  $artists ) )
            return;

        if( !count( $artists ) )
            return;

        $artist = array_shift( $artists );
        $return = array("name" => $artist->name );

        error_log( "name = " . $artist->name );

        if( isset( $artist->foreign_ids ) && is_array( $artist->foreign_ids ) ) {
            $spotifyId = array_shift( $artist->foreign_ids );
            $spotifyId = $spotifyId->foreign_id;

            $return['spotifyId'] = $spotifyId;
        }

        return $return;
    }
    else {
        return;
    }
}

?>
