<?php
// batch-upload.php - 批量上传
require_once 'includes/FileUploader.php';
require_once 'includes/ExcelProcessor.php';

header('Content-Type: application/json');

$uploader = new FileUploader();
$results = $uploader->uploadMultiple($_FILES['files']);

$successCount = count(array_filter($results, fn($r) => $r['success']));
$failCount = count($results) - $successCount;

echo json_encode([
    'success_count' => $successCount,
    'fail_count' => $failCount,
    'results' => $results
]);

