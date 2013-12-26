<?php

class Server {
    private $actions = array('birth', 'death', 'event');
    private $entityManager;

    const VERSION = 0.1;

    public function __construct( $entityManager ) {
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
        #TODO: get events for the date in query string
        /*find events in the same day/month as today*/
        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('e')
            ->from('Event', 'e')
            ->where('e.date like :date')
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
    private function existEvents( $parameters ) {
        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(e.id)')
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setParameters(array(
                    'date' => '%' . $parameters['date']->format('m-d')
                ));
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count ? 1 : 0;
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

    private function sanitizeParameters( &$parameters ) {
        if( !$parameters['limit'] || $parameters['limit'] > 15 || $parameters['limit'] < 1 )
            $parameters['limit'] = 15;

        if(!$parameters['page'] || $parameters['page'] < 0 )
            $parameters['page'] = 0;

        if( !$parameters['show'])
            $parameters['show'] = 'date, description, type';

        if( $parameters['day'] && $parameters['month']) {
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

        //$events = $this->getEvents( $parameters );
        $existEvents = $this->existEvents( $parameters );

        //no events for today in the database, get them from site and set them in the database
        if( !$existEvents ) {
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
                )
        );

        echo json_encode($output);
    }

}
?>
