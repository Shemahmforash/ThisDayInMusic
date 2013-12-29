<?php

class Server {
    //database access
    private $entityManager;

    //config with default values for parameters
    private $config;

    private $id;
    private $offset;
    private $results;
    private $fields;
    private $type;
    private $date;
    private $tweeted;

    const VERSION = 0.1;

    public function __construct( $entityManager, $config ) {
        $this->config = $config;

        //assume default values
        $this->date    = new DateTime("now");
        $this->results = $this->config['pagination']['results'];
        $this->offset  = $this->config['pagination']['offset'];
        $this->fields  = $this->config['fields']['default'];

        $this->entityManager = $entityManager;
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

    private function process($method, $path, $parameters ) {
        if( $method === 'GET' ) {
            $this->get($path, $parameters );
        }
        else if( $method === 'PUT' ) {
            $this->put( $path );
        }

        #TODO: allow the 'put' method to receive data to update the tweeted status and the video status (both fields yet to be added to the db)
        else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: GET');
        }
    }

    private function getEvents() {
        $query = $this->buildQuery();

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->where( $where['query'] )
            ->setFirstResult( $this->offset )
            ->setMaxResults( $this->results )
            ->setParameters( $where['parameters'] );
        $events = $qb->getQuery()->getArrayResult();

        return $events;
    }

    private function buildQuery( $what = null ) {
        //build the columns to show from the parameters
        if( !$what ) {
            $fields = array();
            foreach ( $this->fields as $toShow ) {
                array_push($fields, "e." . trim($toShow) );
            }
            $what = join(", ", $fields);
        }

        //build the where part from the parameters
        $where['parameters'] = array(
                    'date' => '%' . $this->date->format('m-d')
                );

        $where['query'] = array();
        if( $this->type ) {
            $where['parameters']['type'] = "%$this->type%";
        }
        if( $this->tweeted ) {
            $where['parameters']['tweeted'] = "$this->tweeted";
        }
        if( $this->id ) {
            $where['parameters']['id'] = "$this->id";
        }

        foreach( $where['parameters'] as $key => $parameter ) {
            array_push($where['query'], "e.$key like :$key");
        }

        $where['query'] = join(" and ", $where['query']);

        return array (
                'what'  => $what,
                'where' => $where
            );
        
    }

    //find out if there are events in the database for this day
    private function totalEvents() {
        
        $query = $this->buildQuery( "count(e.id)");

        $what  = $query['what'];
        $where = $query['where'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->where( $where['query'] )
            ->setParameters( $where['parameters'] );
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    //gets the events from the site and saves them on db
    private function setEvents() {
        $dim = new ThisDayIn\Music($this->date->format('j'), $this->date->format('F'));
        $evs = $dim->getEvents();

        foreach($evs as $ev ) {
            $date   = new DateTime( $ev['date'] );

            if( $ev['type'] === 'Death') {
                $ev['description'] = sprintf('%s, %s', $ev['name'], $ev['description']);
            }

            //unlike the death events, the birth events do not include in the text information
            if( $ev['type'] === 'Birth') {
                $ev['description'] = sprintf('%s, %s was born', $ev['name'], $ev['description']);
            }

            //set current event
            $event = new Event(); 
            $event->setDate( $date );
            $event->setDescription( $ev['description'] ); 
            $event->setType( $ev['type'] ); 
            $event->setSource( $dim->getSource() ); 
            $this->entityManager->persist( $event );
        }

        //insert all events to db
        if( count( $evs ) ) {
            $this->entityManager->flush();
            $this->totalEvents = $this->totalEvents();
        }
    }

    /*
        Pagination
            offset => (default 0)
            results per page (default 15)
            total (default 10)l
        Filters
            type
            tweeted
        What to see
            id
            date
            description
            type
            source
            tweeted
            
    */

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

        if( isset($parameters['day']) && preg_match( "/\d\d/", $parameters['day'] ) && isset($parameters['month']) && preg_match( "/\d\d/", $parameters['month'] ) ) {
            $month = $parameters['month'];
            $day   = $parameters['day'];
            $date = new DateTime("2013-$month-$day");

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

    private function get($path, $parameters = null) {
        if( $path ) {
            return $this->output( null, array("code" => -5, "status" => "Invalid path '$path' for HTTP GET method") );
        }

        $error = $this->sanitizeParameters( $parameters );

        if( $error ) {
            return $this->output( null, $error );     
        }

        $this->totalEvents = $this->totalEvents();

        $eventRepository = $this->entityManager->getRepository('Event');

        //no events for today in the database, get them from site and set them in the database
        if( !$this->totalEvents ) {
            $this->setEvents();
        }

        //TODO: should one maintain this?
        if( $parameters['results'] === 'all' ) {
            $this->results = $this->totalEvents;     
        }

        //error
        if( $this->offset > $this->totalEvents) {
            return $this->output( null, array("code" => -1, "status" => "Offset ($this->offset) is larger than the total results ($this->totalEvents)") );
        }

        //get events from the db
        $events = $this->getEvents();

        //TODO: no error here, just no events found
        if( !$events ) {
            return $this->output( null, array("code" => -2, "status" => "Error finding the events for this day. Please try again in a few moments.") );
        }

        $this->output($events);
    }

    private function output ($results, $error = null ) {
        header('Content-type: application/json');

        if( $error ) {
            $code = $error['code'];
            $status = $error['status'];
            $events = array();
        }
        else {
            $code = 0;
            $status = "Success";

            #output datetime object in a simplified way
            if( in_array( 'date', $this->fields ) ) {
                $callback = function ( $date ) {
                            $date['date'] = $date['date']->format('Y-m-d');
                            return $date;
                        };
                $results = array_map($callback, $results );
            }

            $events = $results;
        }

        $response = array(
                "status" => array("version" => Server::VERSION, "code" => $code, "status" => $status ),
            );
        if( !$error ) {
            $response['events'] = $events;
            $response['pagination'] = array("total" => intval($this->totalEvents), "offset" => $this->offset, "results" => count( $results ) );
        }

        $output = array(
            'response' => $response
        );

        echo json_encode($output);
    }
}
?>
