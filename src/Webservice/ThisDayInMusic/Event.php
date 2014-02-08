<?php

namespace Webservice\ThisDayInMusic;

class Event extends \Webservice\ThisDayInMusic {

    private $results;

    public function __construct( $entityManager, $config ) {
        $this->results = $config['pagination']['results'];
        $this->offset  = $config['pagination']['offset'];
        $this->fields  = $config['fields']['default'];

        parent::__construct( $entityManager, $config );
    }

    protected function process($method, $path, $parameters ) {
        if( $method === 'GET' ) {
            $this->get($parameters );
        }
        else if( $method === 'PUT' ) {
            $this->put( $path );
        }
        else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: GET');
        }
    }

    protected function prettifyResults( $results ) {
        #output datetime object in a simplified way
        if( in_array( 'date', $this->fields ) ) {
            $callback = function ( $date ) {
                        $date['date'] = $date['date']->format('Y-m-d');
                        return $date;
                    };
            $results = array_map($callback, $results );
        }
        return $results;
    }

    protected function resultName() {
        return "events";    
    }

    protected function tableAbbr() {
        return "e";    
    }

    protected function exist() {
        $what = "count(e.id)";

        $query = $this->buildQuery( "count(e.id)", 0);

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->innerJoin('Artist', 'a', 'WITH', "a.id = e.artist")
            ->where( $where['query'] )
            ->setParameters( $where['parameters'] );


        $count = $qb->getQuery()->getSingleScalarResult();

        return $count ? 1 : 0;
    }

    //find out if there are events in the database for this day
    protected function total() {
        $query = $this->buildQuery( "count(e.id)");

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->innerJoin('Artist', 'a', 'WITH', "a.id = e.artist")
            ->where( $where['query'] )
            ->setParameters( $where['parameters'] );
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    private function getEvents() {
        $query = $this->buildQuery();

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->innerJoin('Artist', 'a', 'WITH', "a.id = e.artist")
            ->where( $where['query'] )
            ->setFirstResult( $this->offset )
            ->setMaxResults( $this->results )
            ->setParameters( $where['parameters'] );
        $events = $qb->getQuery()->getArrayResult();

        return $events;
    }

    private function findEventArtist( $text ) {
        \Echonest\Service\Echonest::configure($this->config['echonest']['key']);

        $response = \Echonest\Service\Echonest::query('artist', 'extract', array(
            'text'    => $text,
            'sort'    => 'hotttnesss-desc',
            'results' => '1',
            'bucket'  => 'id:spotify-WW',
        ));

        if( $response && $response->response->status->code == 0 ) {
            $artist = $response->response->artists[0];

            $spotifyId = array_shift( $artist->foreign_ids );
            $spotifyId = $spotifyId->foreign_id;

            $name = $artist->name;

            return array( 'name' => $name, 'spotifyId' => $spotifyId );
        }
        else {
            return;
        }
    }

    private function findArtistTracks( $artist ) {

        $api = new \Webservice\Spotify\WebApi();
        $results = $api->get('search', 'artist', 'json', array("q" => $artist));

        var_dump( $results ); die;
        
    }

    private function put( $parameters ) {
        preg_match("/^event\/(?P<id>\d+)$/", $parameters, $match);

        if( !isset($match['id']) ) {
            header('HTTP/1.1 400 Bad Request');
            return;
        }

        //read the data in the put
        $data = json_decode(file_get_contents('php://input'), true);
        if (is_null($data)) {
            header('HTTP/1.1 400 Bad Request');
            return;
        }

        //update database
        $event = $this->entityManager->find("Event", (int)$match['id'] );
        if( $event && isset( $data['tweeted'] ) ) {
            $event->setTweeted( $data['tweeted'] );
            $this->entityManager->flush();
        }

        //output the updated entry
        $this->id = $match['id'];
        array_push($this->fields, "tweeted", "id");
        $events = $this->getEvents();

        return $this->output( $events );
    }

    private function get($parameters = null) {
        $error = $this->sanitizeParameters( $parameters );

        if( $error ) {
            return $this->output( null, $error );     
        }

        //no events for today in the database, get them from site and set them in the database
        if( !$this->exist() ) {
            $this->set();
       }

        //find the total events for the conditions received in the parameters
        $this->total = $this->total();

        $eventRepository = $this->entityManager->getRepository('Event');

        if( isset($parameters['results']) && $parameters['results'] === 'all' ) {
            $this->results = $this->total;     
        }

        //error
        if( $this->offset > $this->total) {
            return $this->output( null, array("code" => -1, "status" => "Offset ($this->offset) is larger than the total results ($this->total)") );
        }

        //get events from the db
        $events = $this->getEvents();

        if( !$events ) {
            return $this->output( null, array("code" => -2, "status" => "No events found.") );
        }

        $this->output($events);
    }

    private function sanitizeParameters( $parameters ) {
        //check valid parameters
        foreach ($parameters as $key => $value) {
            if(!in_array($key, $this->config['parameters'])) {
                return array("code" => -3, "status" => "Parameters '$key' is not accepted.");     
            }
        }

        /*Check parameters' value*/
        if(isset($parameters['results']) && preg_match( "/\d+/", $parameters['results'] ) && $parameters['results'] < $this->config['pagination']['max_results'] && $parameters['results'] > 0 ) {
            $this->results = $parameters['results'];
        }

        if(isset($parameters['offset']) && preg_match( "/\d+/", $parameters['offset'] ) && $parameters['offset'] > 0 ) {
            $this->offset = $parameters['offset'];
        }

        if( isset($parameters['fields'] ) && is_array( $parameters['fields'] ) ) {

            $fields = array();
            foreach( $parameters['fields'] as $field) {
                if( in_array($field, $this->config['fields']['accepted'] ) && !in_array($field, $fields) ) {
                    array_push( $fields, $field);     
                }
                else {
                    return array("code" => -4, "status" => "Field '$field' is not accepted.");     
                }

            }
            if( count( $fields ) )
                $this->fields = $fields;
        }

        //the day/month each must be a pair of numbers
        if( isset($parameters['day']) && preg_match( "/\d\d/", $parameters['day'] ) && isset($parameters['month']) && preg_match( "/\d\d/", $parameters['month'] ) ) {
            $month = $parameters['month'];
            $day   = $parameters['day'];
            $date = new \DateTime("2013-$month-$day");

            $this->date = $date;
        }

        /*Filters*/
        if( isset($parameters['type'] ) && preg_match("/\w+/", $parameters['type'] ) ) {
            $this->type = $parameters['type'];
        }

        if( isset($parameters['tweeted'] ) && preg_match("/[1|0]/", $parameters['tweeted'] ) ) {
            $this->tweeted = $parameters['tweeted'];
        }

        if( isset($parameters['id'] ) && preg_match("/\d+/", $parameters['id'] ) ) {
            $this->id = $parameters['id'];
        }
    }

    protected function set() {
        $dim = new \ThisDayIn\Music($this->date->format('j'), $this->date->format('F'));
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
                $artist = $this->findEventArtist( $ev['description'] );

                $ev['name'] = $artist['name'];
                $ev['spotifyId'] = $artist['spotifyId'];
            }
            #TODO: find artist spotify id for the other event types

            //set current event
            $event = new \Event(); 
            $event->setDate( $date );
            $event->setDescription( $ev['description'] ); 
            $event->setType( $ev['type'] ); 
            $event->setSource( $dim->getSource() ); 
            $this->entityManager->persist( $event );

            //connects the event to an artist
            if( $ev['name'] ) {
                $artist = $this->entityManager->getRepository('Artist')->findBy(array('name' => $ev['name']));

                if(!$artist) {
                    $artist = new \Artist();
                    $artist->setName( $ev['name'] );

                    if($ev['spotifyId'] )
                        $artist->setSpotifyId($ev['spotifyId']);
                }

                $artist->assignToEvent( $event );
                $event->setArtist( $artist );

                //TODO: find artist tracks
                #$tracks = $this->findArtistTracks( $ev['name'] );

                $this->entityManager->persist( $artist );
            }
        }

        //insert all events to db
        if( count( $evs ) ) {
            $this->entityManager->flush();
            $this->total = $this->total();
        }
    }
}

?>
