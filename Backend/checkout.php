<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

include 'db.php'; // Assuming your database connection is in db.php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get data from POST request
$data = json_decode(file_get_contents("php://input"));

if (!$data->saveAddress && !$data->saveCard) {
    http_response_code(200);
    echo json_encode(array("status" => "success", "message" => "No update needed."));
    exit;
}

// Check if all required fields are provided
if (
    !isset($data->userId) ||
    !isset($data->receiverName) ||
    !isset($data->phone) ||
    !isset($data->paymentMethod)
) {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data provided."));
    exit;
}

// Check if the user already has a record in the user_details table
$query = "SELECT COUNT(*) AS count, address, card_number, card_expiry_month, card_expiry_year, card_cvv FROM user_details WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $data->userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    // Initialize variables for optional fields
    $address = isset($data->address) && $data->address !== '' ? $data->address : $row['address'];
    $cardNumber = isset($data->cardNumber) && $data->cardNumber !== '' ? $data->cardNumber : $row['card_number'];
    $cardCVV = isset($data->cardCVV) && $data->cardCVV !== '' ? $data->cardCVV : $row['card_cvv'];

    // Process card expiry to separate month and year
    $cardExpiry = isset($data->cardExpiry) && $data->cardExpiry !== '' ? $data->cardExpiry : '/';
    list($cardExpiryMonth, $cardExpiryYear) = explode('/', $cardExpiry);
    $cardExpiryMonth = isset($cardExpiryMonth) && $cardExpiryMonth !== '' ? trim($cardExpiryMonth) : $row['card_expiry_month'];
    $cardExpiryYear = isset($cardExpiryYear) && $cardExpiryYear !== '' ? trim($cardExpiryYear) : $row['card_expiry_year'];
} else {
    // If no existing record found, initialize with provided values or NULL if not provided
    $address = isset($data->address) && $data->address !== '' ? $data->address : NULL;
    $cardNumber = isset($data->cardNumber) && $data->cardNumber !== '' ? $data->cardNumber : NULL;
    $cardCVV = isset($data->cardCVV) && $data->cardCVV !== '' ? $data->cardCVV : NULL;

    // Process card expiry to separate month and year
    $cardExpiry = isset($data->cardExpiry) && $data->cardExpiry !== '' ? $data->cardExpiry : '/';
    list($cardExpiryMonth, $cardExpiryYear) = explode('/', $cardExpiry);
    $cardExpiryMonth = isset($cardExpiryMonth) && $cardExpiryMonth !== '' ? trim($cardExpiryMonth) : NULL;
    $cardExpiryYear = isset($cardExpiryYear) && $cardExpiryYear !== '' ? trim($cardExpiryYear) : NULL;
}

// Update or insert into user_details table based on whether the user already exists
if ($row['count'] > 0) {
    // Update existing record
    $query = "UPDATE user_details SET 
                address = IFNULL(?, address), 
                card_number = IFNULL(?, card_number), 
                card_expiry_month = IFNULL(?, card_expiry_month), 
                card_expiry_year = IFNULL(?, card_expiry_year), 
                card_cvv = IFNULL(?, card_cvv),
                updated_at = CURRENT_TIMESTAMP() 
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssssi",
        $address, $cardNumber, $cardExpiryMonth, $cardExpiryYear, $cardCVV,
        $data->userId
    );
} else {
    // Insert new record
    $query = "INSERT INTO user_details (user_id, address, card_number, card_expiry_month, card_expiry_year, card_cvv) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "isssis",
        $data->userId,    // Parameter 1: user_id (integer)
        $address,         // Parameter 2: address (string)
        $cardNumber,      // Parameter 3: card_number (string)
        $cardExpiryMonth, // Parameter 4: card_expiry_month (string)
        $cardExpiryYear,  // Parameter 5: card_expiry_year (string)
        $cardCVV          // Parameter 6: card_cvv (string)
    );
}

// Execute the SQL statement
if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(array("status" => "success", "message" => "User details saved successfully."));
} else {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Failed to save user details: " . $stmt->error));
}

// Close statement and database connection
$stmt->close();
$conn->close();
?>