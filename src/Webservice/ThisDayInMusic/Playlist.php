<?php

namespace Webservice\ThisDayInMusic;

class Playlist extends \Webservice\ThisDayInMusic {

    protected function process($method, $path, $parameters ) {
        if( $method === 'GET' ) {
            $this->get($path, $parameters );
        }
        else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: GET');
        }
    }
    
    
}
