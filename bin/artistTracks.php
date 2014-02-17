<?php

//cron each 2 minutes of the first hour of every day
// */2 0 * * * /usr/bin/php pathtoproject/bin/artistTracks.php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../bootstrap.php";

$result = \Webservice\ThisDayInMusic::findArtistTracks( $entityManager );

?>
