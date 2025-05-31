<?php
class Database {
    private static $host = "localhost";
    private static $db_name = "spk_guru"; 
    private static $username = "febrianth";
    private static $password = "febrianth123";
    private static $conn;

    public static function getConnection() {
        if (self::$conn == null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name,
                    self::$username,
                    self::$password
                );

                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
?>
