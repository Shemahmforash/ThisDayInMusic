<?php

namespace Webservice\ThisDayInMusic;

class Playlist extends \Webservice\ThisDayInMusic {

    private $tracks = array();

    public function __construct( $entityManager, $config ) {
        $this->offset  = $config['pagination']['offset'];

        parent::__construct( $entityManager, $config );
    }

    protected function process($method, $path, $parameters ) {
        if( $method === 'GET' ) {
            $this->get( $parameters );
        }
        else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: GET');
        }
    }

    protected function get($parameters) {
        $error = $this->sanitizeParameters( $parameters );

        if( $error ) {
            return $this->output( null, $error );     
        }

        #no playlist, set it from the event -> artist -> tracks tables
        if( !$this->exist() ) {
            $this->set();
        }

#TODO: even if playlist exists, there should be the chance to add more tracks to it.

        if(!$this->tracks) {
            #get the tracks from the db     

            #TODO: filter by offset and number of results
            $query = $this->entityManager->createQuery('SELECT p,t FROM Playlist p JOIN p.tracks t WHERE p.date LIKE :date');
            $query->setParameter("date", "%" . $this->date->format('m-d'));
            
            try {
                $playlist = $query->getSingleResult();
            } catch (\Doctrine\ORM\NoResultException $e ) {
                return $this->output( null, array("code" => -2, "status" => "No tracks for playlist found.") );
            }

            $this->tracks = $playlist->getTracks();
        }

        $this->total  = $this->total();

        if( isset($parameters['results']) && $parameters['results'] === 'all' ) {
            $this->results = $this->total;     
        }

        //error
        if( $this->offset > $this->total) {
            return $this->output( null, array("code" => -1, "status" => "Offset ($this->offset) is larger than the total results ($this->total)") );
        }

        if( !$this->total() ) {
            return $this->output( null, array("code" => -2, "status" => "No tracks for playlist found.") );
        }

        $this->output($this->tracks);
    }

    protected function set() {
        #find all the events for this day, as well as the event artists and tracks
        $query = $this->entityManager->createQuery('SELECT e,a FROM Event e JOIN e.artist a WHERE e.date LIKE :date');
        $query->setParameter("date", "%" . $this->date->format('m-d'));
        $events = $query->getResult();

        $artistRepository = $this->entityManager->getRepository('Artist');
        $artists = $artistRepository->findBy(array("hasTracks" => NULL));

        if( count( $artists ) )
            return $this->output( null, array("code" => -2, "status" => "No tracks for playlist found. Please try again later.") );

        #find tracks for each artist and create a playlist with a track for each one of the artists
        foreach ( $events as $event ) {
            $artist = $event->getArtist();

            #trackless artist will not enter the playlist
            if(!$artist->getTracks()->count() ) {
                error_log("trackless: " . $artist->getName() );
                continue;
            }

            $tracks = $artist->getTracks()->toArray();

            #get the first track without event
            $track = array_shift( $tracks );

            $this->entityManager->persist( $track );
            $this->entityManager->persist( $event );
            $event->setTrack( $track );
            $track->assignToEvent( $event );

            #add to playlist tracks
            array_push( $this->tracks, $track );
        }

        #create new playlist from the tracks found
        if( count( $this->tracks ) ) {
            $playlist = new \Playlist();
            $playlist->setDate( $this->date );
            $this->entityManager->persist( $playlist );

            foreach ( $this->tracks as $track ) {
                $playlist->addTrack( $track );
            }

            //update db
            $this->entityManager->flush();
            //set the total tracks in the attribute
            $this->total = $this->total();
        }
    }

    protected function prettifyResults( $results ) {
        $data = array();
        foreach( $results as $track) {
            $info = array(
                'name'      => $track->getName(),
                'artist'    => $track->getArtist()->getname(),
                'spotifyId' => $track->getSpotifyId(),
                'event'     => $track->getEvent() ? $track->getDescription() : "",
            );
            array_push( $data, $info );
        }

        return $data;
    }

    protected function resultName() {
        return "tracks";    
    }

    protected function tableAbbr() {
        return "p";    
    }

    protected function total() {
        return count( $this->tracks );
    }

    protected function exist() {
        $query = $this->buildQuery( "count(p.id)", 0);

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Playlist', 'p')
            ->innerJoin('Track', 't', 'WITH', "p.id = t.id")
            ->where( $where['query'] )
            ->setParameters( $where['parameters'] );

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count ? 1 : 0;
    }
    
}
