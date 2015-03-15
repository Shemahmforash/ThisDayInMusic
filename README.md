ThisDayInMusic
==============

This is a webservice with information about what happened in this day in music history.

## Installation
   * This project is in Composer format, so you just need to run `php composer.phar install`, and all dependencies will be installed.
   * Fill in your database credentials in bootstrap.php and run:
`php vendor/bin/doctrine orm:schema-tool:create`

## Documentation

### Response Codes
   * 0  - Success
   * -1 - Unknown error
   * 1  - Invalid uri supplied to the Webservice
   * 2  - Invalid parameter
   * 3  - No data was found
