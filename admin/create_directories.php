<?php
// Create upload directories if they don't exist
$directories = [
    '../uploads',
    '../uploads/avatars',
    '../uploads/products',
    '../uploads/categories'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

echo "Directories created successfully!"; 