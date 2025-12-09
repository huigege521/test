<?php
// includes/ExcelProcessor.php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelProcessor {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // 导入Excel文件
    public function importExcel($filePath, $options = []) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();
            
            $headers = array_shift($data); // 获取表头
            $importedCount = 0;
            $errors = [];
            
            foreach ($data as $rowNumber => $row) {
                if (empty(array_filter($row))) continue; // 跳过空行
                
                $result = $this->processRow($headers, $row, $rowNumber + 2);
                if ($result['success']) {
                    $importedCount++;
                } else {
                    $errors[] = "第" . ($rowNumber + 2) . "行: " . $result['error'];
                }
            }
            
            return [
                'success' => true,
                'imported' => $importedCount,
                'total' => count($data),
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // 批量导入多个Excel文件
    public function batchImport($files, $options = []) {
        $results = [];
        $taskId = $this->createBatchTask('excel_import', count($files));
        
        foreach ($files as $fileInfo) {
            $result = $this->importExcel($fileInfo['path'], $options);
            $result['file_name'] = $fileInfo['name'];
            $results[] = $result;
            
            $this->updateBatchTaskProgress($taskId);
        }
        
        $this->completeBatchTask($taskId);
        return $results;
    }
    
    // 导出数据到Excel
    public function exportToExcel($data, $fileName, $headers = []) {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // 设置表头
            if (!empty($headers)) {
                $sheet->fromArray($headers, null, 'A1');
            }
            
            // 填充数据
            $sheet->fromArray($data, null, 'A2');
            
            // 自动调整列宽
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // 保存文件
            $writer = new Xlsx($spreadsheet);
            $filePath = 'exports/' . $fileName . '_' . date('YmdHis') . '.xlsx';
            $writer->save($filePath);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => basename($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function processRow($headers, $row, $lineNumber) {
        // 数据验证和处理逻辑
        $rowData = array_combine($headers, $row);
        
        // 示例：插入数据库
        try {
            $stmt = $this->db->prepare("INSERT INTO imported_data (name, email, phone, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $rowData['姓名'] ?? '',
                $rowData['邮箱'] ?? '',
                $rowData['电话'] ?? ''
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

