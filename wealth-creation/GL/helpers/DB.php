<?php
class DB {
    private static $instance = null;

    public static function conn() {

        if (self::$instance === null) {

            // LOCAL XAMPP MYSQL SETTINGS
            $host = "localhost";
            $dbname = "wealth_creation";   // <- CHANGE THIS
            $username = "root";               // default for XAMPP
            $password = "";                   // default empty password
            $charset = "utf8";

            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            try {
                self::$instance = new PDO($dsn, $username, $password, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ));
            } catch (PDOException $e) {
                die("DB Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
?>
