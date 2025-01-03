<?php
require_once(__DIR__ . '/../models/Category.php');

try {
    // Validate required fields
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Category ID is required");
    }
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
            
            // Delete old image if exists
            $oldCategory = $category->getCategoryById($_POST['id']);
            if ($oldCategory && !empty($oldCategory['image'])) {
                $oldImagePath = __DIR__ . '/../' . $oldCategory['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }
    }
    
    // Prepare category data
    $categoryData = [
        'id' => $_POST['id'],
        'name' => $_POST['name'],
        'slug' => !empty($_POST['slug']) ? $_POST['slug'] : strtolower(str_replace(' ', '-', $_POST['name'])),
        'description' => $_POST['description'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Only include image in update if a new one was uploaded
    if ($imagePath) {
        $categoryData['image'] = $imagePath;
    }
    
    // Update category
    $result = $category->updateCategory($categoryData);
    
    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Category updated successfully",
            "data" => $categoryData
        ]);
    } else {
        throw new Exception("Failed to update category");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update category: " . $e->getMessage()
    ]);
}
?> 