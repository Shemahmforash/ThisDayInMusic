<?php

class Server {

//    private $actions = array('birth', 'death', 'event');

    //database access
    private $entityManager;

    //config with default values for parameters
    private $config;

    private $offset;
    private $results;
    private $fields;

    private $date;

    const VERSION = 0.1;

    public function __construct( $entityManager, $config ) {
        $this->config = $config;


        //assume default values
        $this->date    = new DateTime("now");
        $this->results = $this->config['pagination']['results'];
        $this->offset  = $this->config['pagination']['offset'];
        $this->fields  = $this->config['fields'];

        $this->entityManager = $entityManager;
        $this->start();
    }

    public function start() {
        //find the request method and, from the uri, get the parameters involved
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url( $uri );

        $paths = explode('/', $path['path']);
        array_shift( $paths );//ignore empty entry

        $action = array_shift($paths);

        $this->process( $method, $action, $_GET );
    }

    private function process($method, $action, $parameters ) {
        switch($method) {
            case 'GET':
                $this->get($action, $parameters );
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
                header('Allow: GET');
                break;
        }
    }

    private function getEvents() {
        //build the columns to show from the parameters
        $fields = array();
        foreach ( $this->fields as $toShow ) {
            array_push($fields, "e." . trim($toShow) );
        }
        $what = join(", ", $fields);

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select($what)
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setFirstResult( $this->offset )
            ->setMaxResults( $this->results )
            ->setParameters(array(
                    'date' => '%' . $this->date->format('m-d')
                ));
        $events = $qb->getQuery()->getArrayResult();

        return $events;
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

    //find out if there are events in the database for this day
    private function totalEvents() {
        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(e.id)')
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setParameters(array(
                    'date' => '%' . $this->date->format('m-d')
                ));
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    /*
        Pagination
            offset => (default 0)
            results per page (default 15)
            total (default 10)l
        Filters
            type
            published
        What to see
            id
            date
            description
            type
            source
            is_published
            
    */

    //TODO: accept filters in the parameters
    private function sanitizeParameters( $parameters ) {
        if(isset($parameters['results']) && preg_match( "/\d+/", $parameters['results'] ) && $parameters['results'] < $this->config['pagination']['max_results'] && $parameters['results'] > 0 ) {
            $this->results = $parameters['results'];
        }

        if(isset($parameters['offset']) && preg_match( "/\d+/", $parameters['offset'] ) && $parameters['offset'] > 0 ) {
            $this->offset = $parameters['offset'];
        }

        if( isset($parameters['fields'] ) && preg_match("/\w+/", $parameters['fields']) ) {
            $this->fields = explode(",", $parameters['fields']);
        }

        if( isset($parameters['day']) && preg_match( "/\d\d/", $parameters['day'] ) && isset($parameters['month']) && preg_match( "/\d\d/", $parameters['month'] ) ) {
            $month = $parameters['month'];
            $day   = $parameters['day'];
            $date = new DateTime("2013-$month-$day");

            $this->date = $date;
        }
    }

    private function get($action = null, $parameters = null) {
        $this->sanitizeParameters( $parameters );

        $this->totalEvents = $this->totalEvents();

        $eventRepository = $this->entityManager->getRepository('Event');

        //no events for today in the database, get them from site and set them in the database
        if( !$this->totalEvents ) {
            $this->setEvents();
        }

        //error
        if( $this->offset > $this->totalEvents) {
            return $this->output( null, array("code" => -1, "status" => "Offset ($this->offset) is larger than the total results ($this->totalEvents)") );
        }

        //get events from the db
        $events = $this->getEvents();

        if( !$events ) {
            return $this->output( null, array("code" => -2, "status" => "Error finding the events for this day. Please try again in a few moments.") );
        }

        #output datetime object in a simplified way
        if( in_array( 'date', $this->fields ) ) {
            $callback = function ( $date ) {
                        $date['date'] = $date['date']->format('Y-m-d');
                        return $date;
                    };
            $events = array_map($callback, $events);
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
            $events = $results;
        }

        $output = array(
            "response" => array(
                    "status" => array("version" => Server::VERSION, "code" => $code, "status" => $status ),
                    "events" => $events,
                    "pagination" => $error ? array() : array("total" => $this->totalEvents, "offset" => $this->offset, "results" => $this->results ),
                )
        );

        echo json_encode($output);
    }

}
?>
