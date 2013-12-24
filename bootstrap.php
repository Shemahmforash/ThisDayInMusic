<?php
    // bootstrap.php
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    require_once "vendor/autoload.php";

    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
    // or if you prefer yaml or XML
    //$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
    //$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

    // database configuration parameters
    $conn = array(
            'dbname'   => 'DayIn',
            'user'     => 'root',
            'password' => 'forsakenart',
#            'password' => 'ZGKU.Bq!',
            'host'     => 'localhost',
            'driver'   => 'pdo_mysql',
        );

    // obtaining the entity manager
    $entityManager = EntityManager::create($conn, $config);

    //twitter credentials
    $twitter = array(
            'consumerKey'       => 'eaGnBo5pB5fztuH43PWEg',
            'consumerSecret'    => 'ASEfFrBKubGpCupgueFwGxP73i2bK7WydYrmnYcc',
            'accessToken'       => '1509987306-ONotq4g9TRlOiAVEv5tCUvqHw1LDl7N2FXN5sP5',
            'accessTokenSecret' => 'rwwkTXDcz7ujX9QYuEVeUDzn2g8WwT3xJRV0t1k6U',
        );
