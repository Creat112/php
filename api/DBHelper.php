<?php
class DBHelper {
    private static $conn = null;
    private static function init() {
        if (self::$conn === null) {
            $host = "localhost";
            $dbname = "ambr";
            $username = "root";
            $password = "";
            try {
                self::$conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
    }
    public static function getConnection() {
        self::init();
        return self::$conn;
    }
    public static function query($sql, $params = []) {
        self::init();
        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
?>
