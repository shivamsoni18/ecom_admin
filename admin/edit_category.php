<?php
session_start();
require_once(__DIR__ . '/../models/Category.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Category ID is required";
    header('Location: categories.php');
    exit();
}

$category = new Category();
$categoryData = $category->getCategoryById($_GET['id']);

if (!$categoryData) {
    $_SESSION['error'] = "Category not found";
    header('Location: categories.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .current-image {
            max-width: 200px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Category</h4>
                    </div>
                    <div class="card-body">
                        <form id="categoryForm">
                            <input type="hidden" id="categoryId" value="<?php echo htmlspecialchars($categoryData['id']); ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($categoryData['name']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="slug" 
                                       name="slug" 
                                       value="<?php echo htmlspecialchars($categoryData['slug']); ?>">
                                <small class="text-muted">Leave empty to auto-generate from name</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"><?php echo htmlspecialchars($categoryData['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo $categoryData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $categoryData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <?php if (!empty($categoryData['image'])): ?>
                                    <div>
                                        <img src="<?php echo '../' . htmlspecialchars($categoryData['image']); ?>" 
                                             alt="Current category image" 
                                             class="current-image img-thumbnail">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Category</button>
                            <a href="categories.php" class="btn btn-secondary">Back to Categories</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('categoryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('id', document.getElementById('categoryId').value);
            formData.append('name', document.getElementById('name').value);
            formData.append('slug', document.getElementById('slug').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('status', document.getElementById('status').value);
            
            const imageFile = document.getElementById('image').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }

            try {
                const response = await fetch('process_edit_category.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('Category updated successfully!');
                    window.location.href = 'categories.php';
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error updating category: ' + error.message);
            }
        });

        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
        });
    </script>
</body>
</html> 