<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Create New Category</h4>
                    </div>
                    <div class="card-body">
                        <form id="categoryForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug">
                                <small class="text-muted">Leave empty to auto-generate from name</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary">Create Category</button>
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
            formData.append('name', document.getElementById('name').value);
            formData.append('slug', document.getElementById('slug').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('status', document.getElementById('status').value);
            
            const imageFile = document.getElementById('image').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }

            try {
                const response = await fetch('process_category.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('Category created successfully!');
                    window.location.href = 'categories.php';
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error creating category: ' + error.message);
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