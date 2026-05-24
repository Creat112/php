<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

if (!$email || !$password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required'
    ]);
    exit;
}

try {

    // =========================
    // FIND USER
    // =========================

    $sql = $conn->prepare("
        SELECT id, name, email, password, role
        FROM users
        WHERE email = ?
    ");

    $sql->execute([$email]);

    $user = $sql->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
        exit;
    }

    // =========================
    // VERIFY PASSWORD
    // =========================

    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
        exit;
    }

    // =========================
    // SUCCESS RESPONSE
    // =========================

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ]
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>