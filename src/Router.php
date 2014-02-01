<?php

class Router {
    public static function route() {
        //find the request method and, from the uri, get the parameters involved
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url( $uri );

        preg_match("/^\/(?P<action>[^\/]+)\/?$/", $path['path'], $match);

        $action = ucfirst( $match['action'] );

        if(!$action)
            return;

        $class = "Webservice\ThisDayInMusic\\$action";
        if (!class_exists($class)) {
            throw new Exception("Missing class $action.");
        }

        return $class;
    }
}
