<?php

namespace Webservice;

abstract class ThisDayInMusic {

    const VERSION = 0.1;

    //database access
    protected $entityManager;

    //config with default values for parameters
    protected $config;

    protected $date;
    protected $total;

    //playlist or event type
    protected $type;

    protected $id;

    protected $fields;

    protected $offset;

    public function __construct( $entityManager, $config ) {
        $this->config = $config;
        $this->entityManager = $entityManager;

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

    abstract protected function total();

    abstract protected function exist();

    abstract protected function set();

    abstract protected function prettifyResults( $results );

    abstract protected function resultName();

    //output the webservice results
    protected function output ($results, $error = null ) {
        header('Content-type: application/json');

        if( $error ) {
            $code = $error['code'];
            $status = $error['status'];
            $data = array();
        }
        else {
            $code = 0;
            $status = "Success";

            #output data in simple format
            $data = $this->prettifyResults( $results );
        }

        $response = array(
                "status" => array("version" => self::VERSION, "code" => $code, "status" => $status ),
            );
        if( !$error ) {
            $response[ $this->resultName ] = $data;
            $response['pagination'] = array("total" => intval($this->total), "offset" => $this->offset, "results" => count( $results ) );
        }

        $output = array(
            'response' => $response
        );

        echo json_encode($output);
    }

    public static function outputError( $error ) {
        header('Content-type: application/json');
        $code = $error['code'];
        $status = $error['status'];
        $data = array();

        $response = array(
                "status" => array("version" => self::VERSION, "code" => $code, "status" => $status ),
            );

        $output = array(
            'response' => $response
        );

        echo json_encode($output);
    }

    protected function buildQuery( $what = null, $useParameters = 1 ) {
        //build the columns to show from the parameters
        if( !$what ) {
            $fields = array();
            foreach ( $this->fields as $toShow ) {
                $base = $toShow == 'artist' ? 'a.' : 'e.';

                if( $toShow == 'artist')
                    $toShow = 'name';

                array_push($fields, $base . trim($toShow) );
            }
            $what = join(", ", $fields);
        }

        //build the where part from the parameters
        $where['parameters'] = array(
                    'date' => '%' . $this->date->format('m-d'),
                );

        $where['query'] = array();
        if( $useParameters ) {
            if( $this->type ) {
                $where['parameters']['type'] = "%$this->type%";
            }
            if( isset( $this->tweeted ) ) {
                $where['parameters']['tweeted'] = "$this->tweeted";
            }
            if( $this->id ) {
                $where['parameters']['id'] = "$this->id";
            }
        }

        foreach( $where['parameters'] as $key => $parameter ) {
            if( $key == 'id' or $key == 'tweeted')
                array_push($where['query'], "e.$key = :$key");
            else 
                array_push($where['query'], "e.$key like :$key");
        }

        $where['query'] = join(" and ", $where['query']);

        return array (
                'what'  => $what,
                'where' => $where
            );
    }
}

?>
