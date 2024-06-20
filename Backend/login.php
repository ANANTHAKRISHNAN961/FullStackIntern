<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Content-Type: application/json; charset=UTF-8");

// Include your database connection file (db.php)
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}


// Handle POST request for user login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve JSON data from the request body
    $data = json_decode(file_get_contents("php://input"), true);

    $email = $data['email'];
    $password = $data['password'];

    // Sanitize inputs (optional, but good practice)
    $email = mysqli_real_escape_string($conn, $email);

    // Query to fetch user by email
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_stmt_init($conn);

    // Prepare statement
    if (mysqli_stmt_prepare($stmt, $query)) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, "s", $email);

        // Execute statement
        mysqli_stmt_execute($stmt);

        // Get result
        $result = mysqli_stmt_get_result($stmt);

        // Check if user exists and verify password
        if ($row = mysqli_fetch_assoc($result)) {
            // Verify hashed password
            if (password_verify($password, $row['password'])) {
                // Fetch user roles
                $userId = $row['id'];
                $roleQuery = "SELECT ur.role_id, r.name AS role_name FROM user_roles ur
                              INNER JOIN roles r ON ur.role_id = r.id
                              WHERE ur.user_id = ?";
                $stmtRoles = mysqli_stmt_init($conn);

                if (mysqli_stmt_prepare($stmtRoles, $roleQuery)) {
                    mysqli_stmt_bind_param($stmtRoles, "i", $userId);
                    mysqli_stmt_execute($stmtRoles);
                    $resultRoles = mysqli_stmt_get_result($stmtRoles);

                    // Fetch roles
                    $roles = [];
                    while ($roleRow = mysqli_fetch_assoc($resultRoles)) {
                        $roles[] = $roleRow['role_name'];
                    }

                    // Return user data and roles
                    echo json_encode([
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $row['id'],
                            'username' => $row['username'],
                            'email' => $row['email'],
                            'roles' => $roles
                        ]
                    ]);
                } else {
                    echo json_encode(['error' => 'Database error']);
                }
            } else {
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['error' => 'User not found']);
        }

        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        // Error preparing statement
        echo json_encode(['error' => 'Database error']);
    }

    // Close connection
    mysqli_close($conn);
}
?>
