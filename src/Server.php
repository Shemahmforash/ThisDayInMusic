<?php

class Server {
    private $actions = array('birth', 'death', 'event');
    private $entityManager;

    private $pagination_limit = 10;
    private $pagination_page  = 15;
    private $default_fields   = array("date", "description", "type");

    const VERSION = 0.1;

    public function __construct( $entityManager, $config = null ) {
        //config values overwrite object attributes
        if( $config ) {
            $this->pagination_limit = $config['pagination']['limit'];
            $this->pagination_page  = $config['pagination']['page'];
            $this->default_fields   = $config['fields'];
        }

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

    private function getEvents( $parameters = null ) {
        //build the columns to show from the parameters
        foreach ( $parameters['show'] as &$toShow ) {
            $toShow = "e." . trim($toShow);
        }
        $what = join(", ", $parameters['show']);

        //TODO: should one receive just the offset from the parameters?
        $offset = $parameters['page'] * $parameters['limit'];

        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select($what)
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setFirstResult( $offset )
            ->setMaxResults( $parameters['limit'] )
            ->setParameters(array(
                    'date' => '%' . $parameters['date']->format('m-d')
                ));
        $events = $qb->getQuery()->getArrayResult();

        return $events;
    }

    //gets the events from the site and saves them on db
    private function setEvents( $date ) {
        $dim = new ThisDayIn\Music($date->format('j'), $date->format('F'));
        $evs = $dim->getEvents();

        foreach($evs as $ev ) {
            $date   = new DateTime( $ev['date'] );

            if( $ev['type'] !== 'Event') {
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
        if( count( $evs ) )
            $this->entityManager->flush();
    }

    //find out if there are events in the database for this day
    private function totalEvents( $parameters ) {
        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(e.id)')
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setParameters(array(
                    'date' => '%' . $parameters['date']->format('m-d')
                ));
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

    /*
        Pagination
            page => (default 0)
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

    //TODO: improve the checking of the fiability of the parameters received
    private function sanitizeParameters( &$parameters ) {
        if(isset($parameters['limit']) && preg_match( "/\d+/", $parameters['limit'] ) ) {
            if( $parameters['limit'] > 15 || $parameters['limit'] < 1 ) {
                $parameters['limit'] = $this->pagination_limit;
            }
        }
        else {
            $parameters['limit'] = $this->pagination_limit;
        }

        if(isset($parameters['page']) && preg_match( "/\d+/", $parameters['page'] ) ) {
            if( $parameters['page'] < 0 ) {
                $parameters['page'] = $this->pagination_page;
            }
        }
        else {
            $parameters['page'] = $this->pagination_page;
        }

        if( !isset($parameters['show']))
            $parameters['show'] = $this->default_fields;
        else {
            $parameters['show'] = explode(",", $parameters['show']);    
        }

        if( isset($parameters['day']) && isset($parameters['month'])) {
            $month = $parameters['month'];
            $day   = $parameters['day'];
            $date = new DateTime("2013-$month-$day");

            $parameters['date'] = $date;
        }
        else {
            $now = new DateTime("now");
            $parameters['date'] = $now;
        }
    }

    private function get($action = null, $parameters = null) {

        $this->sanitizeParameters( $parameters );

        $eventRepository = $this->entityManager->getRepository('Event');

        $totalEvents = $this->totalEvents( $parameters );

        //no events for today in the database, get them from site and set them in the database
        if( !$totalEvents ) {
            $this->setEvents( $parameters['date'] );
        }

        //get events from the db
        $events = $this->getEvents( $parameters );

        #output datetime object in a simplified way
        $callback = function ( $date ) {
                    $date['date'] = $date['date']->format('Y-m-d');
                    return $date;
                };
        $events = array_map($callback, $events);

        $this->output($events, $totalEvents);
    }

    private function output ($results, $totalEvents, $error = null ) {
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
                    "pagination" => array("total" => $totalEvents, "page" => $this->pagination_page, "results" => $this->pagination_limit ),
                )
        );

        echo json_encode($output);
    }

}
?>
