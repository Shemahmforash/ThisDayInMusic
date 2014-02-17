<?php

namespace Webservice;

abstract class ThisDayInMusic {

    const VERSION = 0.1;

    //database access
    protected $entityManager;

    //config with default values for parameters
    protected $config;

    protected $date;
    protected $total;

    //playlist or event type
    protected $type;

    protected $id;
    protected $fields;
    protected $offset;

    public function __construct( $entityManager, $config ) {
        $this->config = $config;
        $this->entityManager = $entityManager;

        //assume default values
        $this->date    = new \DateTime("now");

        $this->start();
    }

    public function start() {
        //find the request method and, from the uri, get the parameters involved
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url( $uri );
        $arguments = preg_replace( "/^\//", "", $path['path']);

        $this->process( $method, $arguments, $_GET );
    }

    abstract protected function process($method, $path, $parameters );

    abstract protected function total();

    abstract protected function exist();

    abstract protected function resultName();

    abstract protected function tableAbbr();

    abstract protected function prettifyResults( $results );

    //output the webservice results
    public function output ($results, $error = null ) {
        header('Content-type: application/json');

        if( $error ) {
            $code = $error['code'];
            $status = $error['status'];
            $data = array();
        }
        else {
            $code = 0;
            $status = "Success";

            #output data in simple format
            $data = $this->prettifyResults( $results );
        }

        $response = array(
                "status" => array("version" => self::VERSION, "code" => $code, "status" => $status ),
            );
        if( !$error ) {
            $response[ $this->resultName() ] = $data;
            $response['pagination'] = array("total" => intval($this->total), "offset" => $this->offset, "results" => count( $results ) );
        }

        $output = array(
            'response' => $response
        );

        echo json_encode($output);
    }

    protected function buildQuery( $what = null, $useParameters = 1 ) {
        //build the columns to show from the parameters
        if( !$what ) {
            $fields = array();
            foreach ( $this->fields as $toShow ) {
                $base = $toShow == 'artist' ? 'a.' : $this->tableAbbr() . ".";

                if( $toShow == 'artist')
                    $toShow = 'name';

                array_push($fields, $base . trim($toShow) );
            }
            $what = join(", ", $fields);
        }

        //build the where part from the parameters
        $where['parameters'] = array(
                    'date' => '%' . $this->date->format('m-d'),
                );

        $where['query'] = array();
        if( $useParameters ) {
            if( $this->type ) {
                $where['parameters']['type'] = "%$this->type%";
            }
            if( isset( $this->tweeted ) ) {
                $where['parameters']['tweeted'] = "$this->tweeted";
            }
            if( $this->id ) {
                $where['parameters']['id'] = "$this->id";
            }
        }

        foreach( $where['parameters'] as $key => $parameter ) {
            if( $key == 'id' or $key == 'tweeted')
                array_push($where['query'], $this->tableAbbr() . ".$key = :$key");
            else 
                array_push($where['query'], $this->tableAbbr() . ".$key like :$key");
        }

        $where['query'] = join(" and ", $where['query']);

        return array (
                'what'  => $what,
                'where' => $where
            );
    }

    protected function sanitizeParameters( $parameters ) {

        //check valid parameters
        foreach ($parameters as $key => $value) {
            if(!in_array($key, $this->config['parameters'][ $this->resultName() ])) {
                return array("code" => -3, "status" => "Parameter '$key' is not accepted.");     
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
                if( in_array($field, $this->config['fields'][ $this->resultName() ]['accepted'] ) && !in_array($field, $fields) ) {
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
            $date = new \DateTime("2014-$month-$day");

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

    #finds and sets the tracks for all trackless artists in the DB
    public static function findArtistTracks( $entityManager ) {
        #get the configuration file for the app
        $file = file_get_contents(__DIR__ . "/../../etc/config.json");
        $config = json_decode( $file, true );

        $artistRepository = $entityManager->getRepository('Artist');
        $artists = $artistRepository->findBy(array("hasTracks" => NULL));

        if( count( $artists ) == 0  ) {
            error_log( "no trackless artists. Exit" );
            return;
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

            $spotifyId = $artist->getSpotifyId();

            $parameters = array('bucket' => array('id:spotify-WW', 'tracks'), 'limit'  => "true", "results" => 10);
            if( $spotifyId )
                $parameters['artist_id'] = $spotifyId;
            else
                $parameters['artist'] = $name;
            
            $query = http_build_query($parameters);
            $query = preg_replace("/\%5B\d+\%5D/im", "", $query); 

            \Echonest\Service\Echonest::configure($config['echonest']['key']);
            $response = \Echonest\Service\Echonest::query('song', 'search', $query);

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
                error_log("error: " . $response->status->message );
                break;
            }
        }

        $entityManager->flush();
    }

    #finds the events for a specific date a fills the database with them
    public static function findEvents($entityManager, $date) {
        error_log("date: " . $date->format('Y-m-d') );
        $query = $entityManager->createQuery('SELECT count(e) FROM Event e WHERE e.date LIKE :date');
        $query->setParameter("date", "%" . $date->format('m-d'));
        $eventNumber = $query->getSingleScalarResult();

        if( $eventNumber ) {
            error_log( "events for " . $date->format('Y-m-d') . " already exist." );
            return "1";
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
                $artist = self::findEventArtist( $ev['description'] );

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
        
        return 0;
    }

    public static function findEventArtist( $text ) {

        #get the configuration file for the app
        $file = file_get_contents(__DIR__ . "/../../etc/config.json");
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
    }
}

?>
