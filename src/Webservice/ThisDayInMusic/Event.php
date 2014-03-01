<?php

namespace Webservice\ThisDayInMusic;

class Event extends \Webservice\ThisDayInMusic {

    public function __construct( $entityManager, $config ) {
        $this->results = $config['pagination']['results'];
        $this->offset  = $config['pagination']['offset'];
        $this->fields  = $config['fields'][ $this->resultName() ]['default'];

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

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( $what )
            ->from('Event', 'e')
            ->innerJoin('Artist', 'a', 'WITH', "a.id = e.artist")
            ->where( $where['query'] )
            ->orderBy('e.id', 'ASC')
            ->setFirstResult( $this->offset )
            ->setMaxResults( $this->results )
            ->setParameters( $where['parameters'] );
        $events = $qb->getQuery()->getArrayResult();

        return $events;
    }

    private function put( $parameters ) {
        preg_match("/^api\/v0.1\/event\/(?P<id>\d+)$/", $parameters, $match);

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

        //no events for today in the database
        if( !$this->exist() ) {
            return $this->output( 
                null,
                array(
                    "code" => 3,
                    "status" => "Could not find events for " . $this->date->format("Y-m-d" ) . ". Please try again later."
                )
            );
        }

        //find the total events for the conditions received in the parameters
        $this->total = $this->total();

        $eventRepository = $this->entityManager->getRepository('Event');

        if( isset($parameters['results']) && $parameters['results'] === 'all' ) {
            $this->results = $this->total;     
        }

        //error
        if( $this->offset > $this->total) {
            return $this->output( null, array("code" => 2, "status" => "Offset ($this->offset) is larger than the total results ($this->total)") );
        }

        //get events from the db
        $events = $this->getEvents();

        if( !$events ) {
            return $this->output( null, array("code" => 3, "status" => "No events found. Please try again later.") );
        }

        $this->output($events);
    }
}

?>
