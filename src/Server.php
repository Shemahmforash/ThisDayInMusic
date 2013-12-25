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

        $this->process( $method, $action, $path['query']);

    }

    private function process($method, $action, $query) {
        switch($method) {
            case 'GET':
                $this->get($action, $query);
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
                header('Allow: GET');
                break;
        }
    }

    private function getEvents( $query = null ) {

        #TODO: get events for the date in query string
        $now = new DateTime("now");

        /*find events in the same day/month as today*/
        //TODO: change this query builder to criteria matching
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('e')
            ->from('Event', 'e')
            ->where('e.date like :date')
            ->setParameters(array(
                    'date' => '%' . $now->format('m-d')
                ));
        $events = $qb->getQuery()->getArrayResult();

        return $events;
    }

    //gets the events from the site and saves them on db
    private function setEvents() {
        $dim = new ThisDayIn\Music();
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

    private function get($action = null, $query = null) {
        $eventRepository = $this->entityManager->getRepository('Event');

        $events = $this->getEvents( $query );

        //no events for today in the database, get them from site
        if( !count( $events) ) {

            $this->setEvents();

            $events = $this->getEvents( $query );
        }

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
