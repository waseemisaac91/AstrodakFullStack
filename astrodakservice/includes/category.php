<?php
// includes/category.php

class Category {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Get all categories
     * @return array
     */
    public function getAllCategories() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category by ID
     * @param int $id
     * @return array|null
     */
    public function getCategoryById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get category by English name
     * @param string $name_en
     * @return array|null
     */
    public function getCategoryByNameEn($name_en) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE name_en = ?");
            $stmt->execute([$name_en]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category by English name: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get category by Dutch name
     * @param string $name_nl
     * @return array|null
     */
    public function getCategoryByNameNl($name_nl) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE name_nl = ?");
            $stmt->execute([$name_nl]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category by Dutch name: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get category by name in either language
     * @param string $name
     * @return array|null
     */
    public function getCategoryByName($name) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE name_en = ? OR name_nl = ?");
            $stmt->execute([$name, $name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category by name: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Add new category
     * @param string $name_en English name
     * @param string $name_nl Dutch name
     * @return bool|int Returns category ID on success, false on failure
     */
    public function addCategory($name_en, $name_nl) {
        try {
            // Check if category already exists (by either name)
            if ($this->getCategoryByNameEn($name_en) || $this->getCategoryByNameNl($name_nl)) {
                throw new Exception("Category already exists");
            }
            
            $stmt = $this->pdo->prepare("INSERT INTO categories (name_en, name_nl) VALUES (?, ?)");
            $result = $stmt->execute([trim($name_en), trim($name_nl)]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error adding category: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update category
     * @param int $id
     * @param string $name_en English name
     * @param string $name_nl Dutch name
     * @return bool
     */
    public function updateCategory($id, $name_en, $name_nl) {
        try {
            // Check if another category with same names exists
            $existing_en = $this->getCategoryByNameEn($name_en);
            $existing_nl = $this->getCategoryByNameNl($name_nl);
            
            if (($existing_en && $existing_en['id'] != $id) || ($existing_nl && $existing_nl['id'] != $id)) {
                throw new Exception("Category name already exists");
            }
            
            $stmt = $this->pdo->prepare("UPDATE categories SET name_en = ?, name_nl = ? WHERE id = ?");
            return $stmt->execute([trim($name_en), trim($name_nl), $id]);
        } catch (PDOException $e) {
            error_log("Error updating category: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete category
     * @param int $id
     * @return bool
     */
    public function deleteCategory($id) {
        try {
            // Check if category is being used by projects
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE category_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception("Cannot delete category that is being used by projects");
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get categories with project count
     * @return array
     */
    public function getCategoriesWithProjectCount() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, COUNT(p.id) as project_count 
                FROM categories c 
                LEFT JOIN projects p ON c.id = p.category_id 
                GROUP BY c.id 
                ORDER BY c.name_en ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories with project count: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if category exists
     * @param int $id
     * @return bool
     */
    public function categoryExists($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking category existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get popular categories (most used)
     * @param int $limit
     * @return array
     */
    public function getPopularCategories($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, COUNT(p.id) as usage_count 
                FROM categories c 
                LEFT JOIN projects p ON c.id = p.category_id 
                GROUP BY c.id 
                HAVING usage_count > 0
                ORDER BY usage_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching popular categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search categories by name (searches both English and Dutch names)
     * @param string $searchTerm
     * @return array
     */
    public function searchCategories($searchTerm) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM categories 
                WHERE name_en LIKE ? OR name_nl LIKE ?
                ORDER BY name_en ASC
            ");
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->execute([$searchPattern, $searchPattern]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category name by language
     * @param array $category Category array from database
     * @param string $language 'en' or 'nl'
     * @return string
     */
    public function getCategoryName($category, $language = 'en') {
        if ($language === 'nl' && !empty($category['name_nl'])) {
            return $category['name_nl'];
        }
        return $category['name_en'];
    }
}