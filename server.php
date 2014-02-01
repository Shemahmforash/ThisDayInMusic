<?php

require_once "./vendor/autoload.php";
require_once "bootstrap.php";

$class = Router::route();

#get the configuration file for the app
$file = file_get_contents("./etc/config.json");
$config = json_decode( $file, true );

$server = new $class( $entityManager, $config );

?>
