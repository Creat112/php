<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset($data['name']) || empty($data['name']) ||
    !isset($data['email']) || empty($data['email']) ||
    !isset($data['password']) || empty($data['password'])
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);

    exit;
}

$name = $data['name'];
$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_DEFAULT);

try {

    $sql = $conn->prepare(
        'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
    );

    if ($sql->execute([$name, $email, $password])) {

        echo json_encode([
            'status' => 'success',
            'message' => 'User added successfully'
        ]);

    } else {

        echo json_encode([
            'status' => 'error',
            'message' => 'Error adding user'
        ]);

    }

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);

}
?>