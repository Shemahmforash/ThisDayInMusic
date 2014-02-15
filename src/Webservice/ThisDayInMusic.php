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

        //assume default values
        $this->date    = new \DateTime("now");

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

    abstract protected function resultName();

    abstract protected function tableAbbr();

    abstract protected function prettifyResults( $results );

    //output the webservice results
    public function output ($results, $error = null ) {
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
            $response[ $this->resultName() ] = $data;
            $response['pagination'] = array("total" => intval($this->total), "offset" => $this->offset, "results" => count( $results ) );
        }

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
                $base = $toShow == 'artist' ? 'a.' : $this->tableAbbr() . ".";

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
                array_push($where['query'], $this->tableAbbr() . ".$key = :$key");
            else 
                array_push($where['query'], $this->tableAbbr() . ".$key like :$key");
        }

        $where['query'] = join(" and ", $where['query']);

        return array (
                'what'  => $what,
                'where' => $where
            );
    }

    protected function sanitizeParameters( $parameters ) {

        //check valid parameters
        foreach ($parameters as $key => $value) {
            if(!in_array($key, $this->config['parameters'][ $this->resultName() ])) {
                return array("code" => -3, "status" => "Parameter '$key' is not accepted.");     
            }
        }

        /*Check parameters' value*/
        if(isset($parameters['results']) && preg_match( "/\d+/", $parameters['results'] ) && $parameters['results'] < $this->config['pagination']['max_results'] && $parameters['results'] > 0 ) {
            $this->results = $parameters['results'];
        }

        if(isset($parameters['offset']) && preg_match( "/\d+/", $parameters['offset'] ) && $parameters['offset'] > 0 ) {
            $this->offset = $parameters['offset'];
        }

        if( isset($parameters['fields'] ) && is_array( $parameters['fields'] ) ) {

            $fields = array();
            foreach( $parameters['fields'] as $field) {
                if( in_array($field, $this->config['fields'][ $this->resultName() ]['accepted'] ) && !in_array($field, $fields) ) {
                    array_push( $fields, $field);     
                }
                else {
                    return array("code" => -4, "status" => "Field '$field' is not accepted.");     
                }

            }
            if( count( $fields ) )
                $this->fields = $fields;
        }

        //the day/month each must be a pair of numbers
        if( isset($parameters['day']) && preg_match( "/\d\d/", $parameters['day'] ) && isset($parameters['month']) && preg_match( "/\d\d/", $parameters['month'] ) ) {
            $month = $parameters['month'];
            $day   = $parameters['day'];
            $date = new \DateTime("2014-$month-$day");

            $this->date = $date;
        }

        /*Filters*/
        if( isset($parameters['type'] ) && preg_match("/\w+/", $parameters['type'] ) ) {
            $this->type = $parameters['type'];
        }

        if( isset($parameters['tweeted'] ) && preg_match("/[1|0]/", $parameters['tweeted'] ) ) {
            $this->tweeted = $parameters['tweeted'];
        }

        if( isset($parameters['id'] ) && preg_match("/\d+/", $parameters['id'] ) ) {
            $this->id = $parameters['id'];
        }
    }
}

?>
