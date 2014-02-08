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
            return \Webservice\ThisDayInMusic::outputError(array("code" => -6, "status" => "Invalid action supplied to the webservice. Please check the documentation." ));

        $class = "\Webservice\ThisDayInMusic\\$action";
        if (!class_exists($class)) {
            return \Webservice\ThisDayInMusic::outputError(array("code" => -6, "status" => "Invalid action supplied to the webservice. Please check the documentation." ));
        }

        return $class;
    }
}
