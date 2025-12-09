<?php
// export.php - 数据导出
require_once 'includes/FileExporter.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'excel';

try {
    $db = new PDO(...$databaseConfig);
    $exporter = new FileExporter($db);
    
    $queries = [
        'users' => "SELECT id, username, email, created_at FROM users",
        'products' => "SELECT id, name, price, stock FROM products", 
        'orders' => "SELECT o.id, u.username, o.total, o.created_at FROM orders o JOIN users u ON o.user_id = u.id"
    ];
    
    if (!isset($queries[$type])) {
        throw new Exception('无效的导出类型');
    }
    
    if ($format === 'excel') {
        $processor = new ExcelProcessor($db);
        $data = $db->query($queries[$type])->fetchAll(PDO::FETCH_ASSOC);
        $result = $processor->exportToExcel($data, $type);
    } else {
        $result = $exporter->exportToCSV($queries[$type], $type);
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

