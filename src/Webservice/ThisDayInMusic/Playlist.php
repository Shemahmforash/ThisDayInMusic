<?php

namespace Webservice\ThisDayInMusic;

class Playlist extents Webservice\ThisDayInMusic {

    private function process($method, $path, $parameters ) {
        if( $method === 'GET' ) {
            $this->get($path, $parameters );
        }
        else {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: GET');
        }
    }
    
    
}
