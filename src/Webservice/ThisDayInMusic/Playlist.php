<?php

namespace Webservice\ThisDayInMusic;

class Playlist extends \Webservice\ThisDayInMusic {

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

        //find the total events for the conditions received in the parameters
        $this->total = $this->total();
    }

    protected function set() {

        #find all the events for this day, as well as the event artists
        #the find tracks for each artist and create a playlist with a track for each one of the artists

        #and what about having a cron

        return;     
    }

    protected function total() {
        return;
    }

    protected function prettifyResults( $results ) {
        return $results;
    }

    protected function resultName() {
        return "tracks";    
    }

    protected function tableAbbr() {
        return "p";    
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

    private function sanitizeParameters( $parameters ) {
        //TODO implement this method, reuse the code from Event by putting it in the base class
        return;

    }
    
    
}
