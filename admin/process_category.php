<?php
require_once(__DIR__ . '/../models/Category.php');

try {
    // Validate required fields
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception("Category name is required");
    }

    $category = new Category();
    
    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/categories/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/categories/' . $fileName;
        }
    }
    
    // Prepare category data
    $categoryData = [
        'name' => $_POST['name'],
        'slug' => !empty($_POST['slug']) ? $_POST['slug'] : strtolower(str_replace(' ', '-', $_POST['name'])),
        'description' => $_POST['description'] ?? '',
        'status' => $_POST['status'] ?? 'active',
        'image' => $imagePath
    ];
    
    // Create new category
    $result = $category->createCategory($categoryData);
    
    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Category created successfully",
            "data" => [
                "id" => $result,
                "name" => $categoryData['name'],
                "slug" => $categoryData['slug'],
                "description" => $categoryData['description'],
                "status" => $categoryData['status'],
                "image" => $categoryData['image']
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create category: " . $e->getMessage()
    ]);
}
?> 