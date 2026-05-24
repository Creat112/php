<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include('db.php');

try {

    $stmt = $conn->prepare('SELECT * FROM users');
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);

}
?>