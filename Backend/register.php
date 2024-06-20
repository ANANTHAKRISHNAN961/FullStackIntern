<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include your database connection file
include 'db.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read incoming JSON payload
    $data = json_decode(file_get_contents('php://input'), true);

    // Sanitize inputs (if needed)
    $username = mysqli_real_escape_string($conn, $data['username'] ?? '');
    $password = mysqli_real_escape_string($conn, $data['password'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');

    // Check if all fields are provided
    if (empty($username) || empty($password) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    // Hash password (optional, but highly recommended)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Example: inserting data into database
    $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$hashedPassword', '$email')";

    if (mysqli_query($conn, $query)) {
        // Get the user ID of the newly inserted user
        $userId = mysqli_insert_id($conn);

        // Insert the default role (assuming role_id = 1 is for 'customer')
        $roleQuery = "INSERT INTO user_roles (user_id, role_id) VALUES ($userId, 1)";
        mysqli_query($conn, $roleQuery);

        // Registration successful
        echo json_encode(['success' => 'Registration successful']);
    } else {
        // Database error
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

$conn->close();
?>
