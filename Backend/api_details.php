<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php'; // Include the file containing the database connection

// Handle GET requests for fetching product details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if 'id' parameter is provided
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        try {
            $stmt = $conn->prepare("SELECT stock, material, manufactured_by, weight FROM product_details WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $details = $result->fetch_assoc();
                echo json_encode(['details' => $details]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product details not found']);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID not provided']);
    }
}

$conn->close();
?>
