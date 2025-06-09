<?php
class Project {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAllProjects($lang = 'nl') {
        $titleField = "title_$lang";
        $descField = "description_$lang";
        
        $stmt = $this->pdo->query("
            SELECT p.*, c.name_$lang as category_name, 
                   p.$titleField as title, 
                   p.$descField as description 
            FROM projects p
            JOIN categories c ON p.category_id = c.id
            ORDER BY p.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectById($id, $lang = 'en') {
        $titleField = "title_$lang";
        $descField = "description_$lang";
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name_$lang as category_name, 
                   p.$titleField as title, 
                   p.$descField as description 
            FROM projects p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProjectImages($projectId) {
        $stmt = $this->pdo->prepare("SELECT * FROM project_images WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProject($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO projects 
            (title_nl, title_en, category_id, description_nl, description_en, image) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title_nl'],
            $data['title_en'],
            $data['category_id'],
            $data['description_nl'],
            $data['description_en'],
            $data['image']
        ]);
    }

    public function updateProject($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE projects SET 
                title_nl = ?, 
                title_en = ?, 
                category_id = ?, 
                description_nl = ?, 
                description_en = ?,
                image = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title_nl'],
            $data['title_en'],
            $data['category_id'],
            $data['description_nl'],
            $data['description_en'],
            $data['image'],
            $id
        ]);
    }

    public function deleteProject($id) {
        $this->pdo->beginTransaction();
        
        try {
            // Delete related images first
            $stmt = $this->pdo->prepare("DELETE FROM project_images WHERE project_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the project
            $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addImageToProject($projectId, $imagePath) {
        $stmt = $this->pdo->prepare("INSERT INTO project_images (project_id, image) VALUES (?, ?)");
        return $stmt->execute([$projectId, $imagePath]);
    }

    public function deleteImage($imageId) {
        $stmt = $this->pdo->prepare("DELETE FROM project_images WHERE id = ?");
        return $stmt->execute([$imageId]);
    }
}
?>