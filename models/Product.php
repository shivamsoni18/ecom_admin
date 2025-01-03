<?php
require_once(__DIR__ . '/../config/Database.php');

class Product {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllProducts() {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch images for each product
        foreach ($products as &$product) {
            $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$product['id']]);
            $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $products;
    }

    public function getProductById($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addProduct($data) {
        $sql = "INSERT INTO products (name, sku, description, price, stock, category_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['sku'],
            $data['description'],
            $data['price'],
            $data['stock'],
            $data['category_id'],
            $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    public function updateProduct($data) {
        try {
            $sql = "UPDATE products 
                    SET name = :name,
                        sku = :sku,
                        description = :description,
                        category_id = :category_id,
                        price = :price,
                        stock = :stock,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id";
                
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'price' => $data['price'],
                'stock' => $data['stock'],
                'status' => $data['status']
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error updating product: " . $e->getMessage());
        }
    }

    public function deleteProduct($product_id) {
        try {
            $this->db->beginTransaction();

            // Delete product images
            $sql = "DELETE FROM product_images WHERE product_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$product_id]);
            
            // Delete the product
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$product_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addProductImages($product_id, $image_paths, $primary_index = 0) {
        try {
            $this->db->beginTransaction();
            
            foreach ($image_paths as $index => $path) {
                $is_primary = ($index === $primary_index) ? 1 : 0;
                $sql = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$product_id, $path, $is_primary]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getProductImages($product_id) {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteProductImage($image_id) {
        $sql = "DELETE FROM product_images WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$image_id]);
    }

    public function checkSkuExists($sku, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM products WHERE sku = ?";
        $params = [$sku];

        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    public function updateStock($product_id, $quantity) {
        $sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $product_id, $quantity]);
    }

    public function getProductCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM products";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveProductCount() {
        $sql = "SELECT COUNT(*) as count FROM products WHERE status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getLowStockProductCount($threshold = 5) {
        $sql = "SELECT COUNT(*) as count FROM products WHERE stock <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threshold]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function setPrimaryImage($image_id, $product_id) {
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // First, set all images of this product to non-primary
            $sql = "UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Then set the selected image as primary
            $sql = "UPDATE product_images SET is_primary = 1 WHERE id = :image_id AND product_id = :product_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'image_id' => $image_id,
                'product_id' => $product_id
            ]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error setting primary image: " . $e->getMessage());
        }
    }

    public function deleteImage($image_id, $product_id) {
        try {
            // First get the image path
            $sql = "SELECT image_path FROM product_images WHERE id = :image_id AND product_id = :product_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'image_id' => $image_id,
                'product_id' => $product_id
            ]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                // Delete the physical file
                $fullPath = __DIR__ . '/../' . $image['image_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                // Delete from database
                $sql = "DELETE FROM product_images WHERE id = :image_id AND product_id = :product_id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'image_id' => $image_id,
                    'product_id' => $product_id
                ]);
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Error deleting image: " . $e->getMessage());
        }
    }

    public function uploadProductImages($product_id, $files) {
        try {
            $uploadDir = '../uploads/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($files['tmp_name'] as $key => $tmp_name) {
                if ($files['error'][$key] === 0) {
                    $filename = uniqid() . '_' . $files['name'][$key];
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        // Save image information to database
                        $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                                VALUES (:product_id, :image_path, :is_primary)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            'product_id' => $product_id,
                            'image_path' => 'uploads/products/' . $filename,
                            'is_primary' => 0
                        ]);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Error uploading images: " . $e->getMessage());
        }
    }
}