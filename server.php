<?php

require_once "bootstrap.php";

$productRepository = $entityManager->getRepository('Event');
$products = $productRepository->findAll();

$class = Router::route();

#get the configuration file for the app
$file = file_get_contents("./etc/config.json");
$config = json_decode( $file, true );

if( $class ) {
    error_log("Route to $class");
    $server = new $class( $entityManager, $config );
}

?>
