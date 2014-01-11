<?php

require 'vendor/autoload.php';

class StackTest extends PHPUnit_Framework_TestCase {

    protected $webservice = "http://thisdayinmusic.shemahmforash.kd.io/";
    protected $response;

    protected function setUp() {
        $json = file_get_contents( $this->webservice );
        $this->response = json_decode( $json, true );
    }

    public function testStructure() {
        $this->assertArrayHasKey('response', $this->response);
        $this->assertArrayHasKey('status', $this->response['response']);
        $this->assertArrayHasKey('events', $this->response['response']);
        $this->assertArrayHasKey('pagination', $this->response['response']);
    }
}

?>
