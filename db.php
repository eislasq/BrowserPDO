<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

class MyPDO extends PDO {
    
    const dbName='TicketsClubMedico';

    function __construct($dsn = NULL, $user = NULL, $pass = NULL, $driver_options = NULL) {
        $host = '127.0.0.1';
        if (NULL === $user) {
            $user = 'root';
        }
        if (NULL === $pass) {
            $pass = '';
        }
        $dbname = self::dbName;
        $driver = 'mysql';

        if (NULL === $dsn) {
            $dsn = "$driver:host=$host;dbname=$dbname";
        }
        parent::__construct($dsn, $user, $pass, $driver_options);
    }

}
