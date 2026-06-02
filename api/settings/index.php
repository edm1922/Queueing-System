<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($settings && isset($settings['poster_images'])) {
            $settings['poster_images'] = json_decode($settings['poster_images'], true) ?: [];
        }
        if ($settings && isset($settings['poster_announcements'])) {
            $settings['poster_announcements'] = json_decode($settings['poster_announcements'], true) ?: [];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
    } else {
        requireRole(['admin']);
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
            $uploadedFiles = [];

            if (!empty($_FILES['poster_images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../uploads/posters/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $total = count($_FILES['poster_images']['name']);
                for ($i = 0; $i < $total; $i++) {
                    if ($_FILES['poster_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['poster_images']['name'][$i], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($ext, $allowed)) continue;
                        $name = uniqid('poster_') . '.' . $ext;
                        $dest = $uploadDir . $name;
                        move_uploaded_file($_FILES['poster_images']['tmp_name'][$i], $dest);
                        $uploadedFiles[] = 'uploads/posters/' . $name;
                    }
                }

                $existing = [];
                $stmt = $conn->query("SELECT poster_images FROM display_settings WHERE id = 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['poster_images']) {
                    $existing = json_decode($row['poster_images'], true) ?: [];
                }

                $data['poster_images'] = json_encode(array_merge($existing, $uploadedFiles));
            } else {
                if (isset($data['remove_poster'])) {
                    $removeIdx = intval($data['remove_poster']);
                    $stmt = $conn->query("SELECT poster_images FROM display_settings WHERE id = 1");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $images = [];
                    if ($row && $row['poster_images']) {
                        $images = json_decode($row['poster_images'], true) ?: [];
                    }
                    if (isset($images[$removeIdx])) {
                        $filePath = __DIR__ . '/../../' . $images[$removeIdx];
                        if (file_exists($filePath)) unlink($filePath);
                        array_splice($images, $removeIdx, 1);
                    }
                    $data['poster_images'] = json_encode($images);
                }
            }
        } else {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON input');
            }

            if (isset($data['remove_poster'])) {
                $removeIdx = intval($data['remove_poster']);
                $stmt = $conn->query("SELECT poster_images FROM display_settings WHERE id = 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $images = [];
                if ($row && $row['poster_images']) {
                    $images = json_decode($row['poster_images'], true) ?: [];
                }
                if (isset($images[$removeIdx])) {
                    $filePath = __DIR__ . '/../../' . $images[$removeIdx];
                    if (file_exists($filePath)) unlink($filePath);
                    array_splice($images, $removeIdx, 1);
                }
                $data['poster_images'] = json_encode($images);
            }
        }

        $fields = [];
        $params = [];
        
        $allowedFields = [
            'company_name', 'branch_name', 'address', 'welcome_message',
            'refresh_interval', 'video_url', 'video_type', 'video_volume',
            'video_title', 'video_sponsor', 'video_cta',
            'auto_play_video', 'display_layout', 'active_announcement',
            'cutoff_time', 'company_logo', 'theme_color',
            'poster_enabled', 'poster_interval', 'poster_duration', 'poster_images', 'poster_announcements'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $sql = "UPDATE display_settings SET " . implode(', ', $fields) . " WHERE id = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
