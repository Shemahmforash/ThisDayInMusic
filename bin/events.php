<?php

//cron once a day at 00:00
// 0 0 * * * /usr/bin/php pathtoproject/bin/artistTracks.php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../bootstrap.php";

$str = file_get_contents( __DIR__ . "/date.txt");

#$date = new DateTime("now");
$date = new \DateTime($str);
$limit = new \Datetime("2014-12-31");

if( $date > $limit ) {
    error_log( "Date out of range: " . $date->format('Y-m-d') ); 
    exit;
}

$result = \Webservice\ThisDayInMusic::findEvents( $entityManager, $date );
if( $result == 0 ) {
    error_log( "All events for " . $date->format('Y-m-d') . " imported sucessfully."); 
}

date_add($date, date_interval_create_from_date_string('1 days'));
$str = file_put_contents(__DIR__ . "/date.txt", $date->format("Y-m-d")  );

?>
