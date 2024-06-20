<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php'; // Include the file containing the database connection

// Handle GET requests for fetching products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if 'id' parameter is provided
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT id, name, description, image, price FROM product WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        $sql = "SELECT id, name, description, image, price FROM product";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $products = array();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
    } else {
        echo json_encode([]);
    }
    $stmt->close();
}

// Handle POST requests for adding/updating a product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && isset($_POST['id']) && isset($_POST['name']) && isset($_POST['description']) && isset($_POST['price']) && isset($_POST['category_id'])) {
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category_id']); // Added category_id
        $imagePath = 'Images/' . basename($_FILES['image']['name']); // Get the image filename

        // Corrected the upload directory path
        $uploadDir = 'C:/xampp/htdocs/Elite bags/Images/';
        $uploadPath = $uploadDir . basename($_FILES['image']['name']);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            // Insert product with provided ID and category_id
            $sql = "INSERT INTO product (id, name, description, image, price, category_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssdi", $id, $name, $description, $imagePath, $price, $category_id);

            if ($stmt->execute()) {
                echo json_encode(array("success" => "Product added successfully"));
            } else {
                echo json_encode(array("error" => "Error executing query: " . $stmt->error));
            }
            $stmt->close();
        } else {
            echo json_encode(array("error" => "Error uploading image"));
        }
    } else {
        echo json_encode(array("error" => "Missing required fields"));
    }
}

$conn->close();
?>
