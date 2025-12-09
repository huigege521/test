<?php
// includes/FileExporter.php
class FileExporter {
    private $db;
    private $exportPath = 'exports/';
    
    public function __construct($db) {
        $this->db = $db;
        $this->createDirectory($this->exportPath);
    }
    
    // 导出数据到CSV
    public function exportToCSV($query, $fileName, $headers = []) {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data)) {
                return ['success' => false, 'error' => '没有数据可导出'];
            }
            
            $filePath = $this->exportPath . $fileName . '_' . date('YmdHis') . '.csv';
            $file = fopen($filePath, 'w');
            
            // 写入BOM头（解决中文乱码）
            fwrite($file, "\xEF\xBB\xBF");
            
            // 写入表头
            if (!empty($headers)) {
                fputcsv($file, $headers);
            } else {
                fputcsv($file, array_keys($data[0]));
            }
            
            // 写入数据
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
            
            $this->logExport($fileName, $filePath, count($data));
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'record_count' => count($data)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // 批量导出多个查询结果
    public function batchExport($exportTasks) {
        $results = [];
        
        foreach ($exportTasks as $task) {
            $result = $this->exportToCSV(
                $task['query'],
                $task['file_name'],
                $task['headers'] ?? []
            );
            $results[] = $result;
        }
        
        return $results;
    }
    
    // 生成ZIP压缩包
    public function createZipArchive($files, $zipName) {
        $zipPath = $this->exportPath . $zipName . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();
            
            return ['success' => true, 'file_path' => $zipPath];
        }
        
        return ['success' => false, 'error' => 'ZIP文件创建失败'];
    }
    
    private function logExport($exportName, $filePath, $recordCount) {
        $stmt = $this->db->prepare("
            INSERT INTO export_records (export_name, file_path, record_count, export_time) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$exportName, $filePath, $recordCount]);
    }
    
    private function createDirectory($path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

