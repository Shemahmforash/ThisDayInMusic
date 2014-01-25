<?php

abstract class API {

    //config with default values for parameters
    protected $config;

    public function __construct( $entityManager, $config ) {
        $this->config = $config;

        //assume default values
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
}

?>
