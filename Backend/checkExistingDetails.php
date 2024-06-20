<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight request from browsers
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

// Include database connection
include 'db.php'; // Ensure this file has your database connection logic

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if userId is provided in the URL parameters
if (isset($_GET['userId'])) {
    $userId = $_GET['userId'];  // Assuming userId is sent from frontend

    // Prepare SQL statement to fetch user details
    $sql = "SELECT address, card_number, CONCAT(LPAD(card_expiry_month, 2, '0'), '/', LPAD(card_expiry_year, 2, '0')) AS card_expiry, card_cvv
    FROM user_details
    WHERE user_id = ?";

    // Prepare a statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); // 'i' indicates integer type for user_id

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if there are results
    if ($result->num_rows > 0) {
        // Fetch associative array
        $row = $result->fetch_assoc();

        // Return data as JSON response
        echo json_encode($row);
    } else {
        // If no results found, return success response
        echo json_encode(array('status' => 'success', 'message' => 'User details not found'));
    }

    // Close statement
    $stmt->close();
} else {
    // Handle case where userId is not provided in the URL parameters
    echo json_encode(array('status' => 'error', 'message' => 'Missing userId parameter'));
}

// Close database connection
$conn->close();
?>
