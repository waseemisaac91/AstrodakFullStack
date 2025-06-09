<?php
class Review {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAllReviews($approvedOnly = true, $lang = 'en') {
        $reviewField = "review_$lang";
        
        $sql = "SELECT id, name, review_nl, rating, approved, publish FROM reviews";
        
        if ($approvedOnly) {
            $sql .= " WHERE approved = 1";
        }
        
        $sql .= " ORDER BY publish DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnmoderatedReviews() {
        $stmt = $this->pdo->query("
            SELECT id, name, review_nl, rating, publish 
            FROM reviews 
            WHERE approved = 0 
            ORDER BY publish DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addReview($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO reviews 
            (name, review_nl, rating, publish) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['review_nl'],
            $data['rating'],
            $data['publish'] ?? date('Y-m-d')
        ]);
    }

    public function approveReview($id) {
        $stmt = $this->pdo->prepare("UPDATE reviews SET approved = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteReview($id) {
        $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAverageRating() {
        $stmt = $this->pdo->query("SELECT AVG(rating) as average FROM reviews WHERE approved = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($result['average'], 1);
    }
public function getRecentReviews($limit = 5) {
   
    $stmt = $this->pdo->prepare("
        SELECT id, name, review_nl, rating, publish
        FROM reviews
        WHERE approved = 1
        ORDER BY publish DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>