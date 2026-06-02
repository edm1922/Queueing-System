<?php
date_default_timezone_set('Asia/Manila');

class Database {
    private $host = "localhost";
    private $db_name = "queuing_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("SET time_zone = '+08:00'");
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        return $this->conn;
    }
}

session_start();

function requireAuth() {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        $token = $_COOKIE['auth_token'] ?? '';
    }
    
    if (empty($token)) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (isset($data['token'])) {
            $token = $data['token'];
        }
    }
    
    if (empty($token)) {
        return null;
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT s.*, u.id as uid, u.username, u.display_name, u.role, u.window_id
            FROM user_sessions s
            JOIN users u ON u.id = s.user_id
            WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            return [
                'id' => $session['uid'],
                'username' => $session['username'],
                'display_name' => $session['display_name'],
                'role' => $session['role'],
                'window_id' => $session['window_id'] ?? null
            ];
        }
    } catch (Exception $e) {
        return null;
    }
    
    return null;
}

function requireRole($allowedRoles) {
    $user = requireAuth();
    
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    if (!in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
    
    return $user;
}
?>