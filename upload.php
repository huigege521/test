<?php
// upload.php - 单文件上传
require_once 'includes/FileUploader.php';
require_once 'includes/Database.php';

header('Content-Type: application/json');

try {
    $uploader = new FileUploader();
    $result = $uploader->uploadSingle($_FILES['file']);
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

