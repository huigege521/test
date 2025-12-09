<?php
// includes/FileUploader.php
class FileUploader {
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'];
    private $maxFileSize = 50 * 1024 * 1024; // 50MB
    private $uploadPath;
    
    public function __construct($uploadPath = 'uploads/files/') {
        $this->uploadPath = rtrim($uploadPath, '/') . '/';
        $this->createDirectory($this->uploadPath);
    }
    
    // 单文件上传
    public function uploadSingle($file, $options = []) {
        try {
            // 验证文件
            $this->validateFile($file);
            
            // 生成唯一文件名
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $this->generateFileName($extension);
            $filePath = $this->uploadPath . $fileName;
            
	    // 检查目录是否可写
            if (!is_writable($this->uploadPath)) {
                throw new Exception('上传目录不可写: ' . $this->uploadPath);
            }

            // 移动文件
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // 设置文件权限
                chmod($filePath, 0644);

		return [
                    'success' => true,
                    'file_name' => $fileName,
                    'original_name' => $file['name'],
                    'file_path' => $filePath,
                    'file_size' => $file['size'],
                    'file_type' => $file['type']
                ];
            } else {
		$error = error_get_last();
                throw new Exception('文件移动失败' . ($error['message'] ?? '未知错误'));
            }
        } catch (Exception $e) {
	    // 记录详细错误日志
            error_log('文件上传错误: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // 批量文件上传
    public function uploadMultiple($files, $options = []) {
        $results = [];
        
        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $name,
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
                
                $results[] = $this->uploadSingle($file, $options);
            }
        }
        
        return $results;
    }
    
    // 分片上传（大文件支持）
    public function uploadChunk($chunk, $chunkNumber, $totalChunks, $fileName) {
        $tempDir = $this->uploadPath . 'temp/';
        $this->createDirectory($tempDir);
        
        $chunkPath = $tempDir . $fileName . '.part' . $chunkNumber;
        
        if (move_uploaded_file($chunk['tmp_name'], $chunkPath)) {
            // 检查是否所有分片都已上传
            $uploadedChunks = $this->getUploadedChunks($tempDir, $fileName);
            
            if (count($uploadedChunks) == $totalChunks) {
                return $this->mergeChunks($tempDir, $fileName, $totalChunks);
            }
            
            return ['success' => true, 'chunk' => $chunkNumber];
        }
        
        return ['success' => false, 'error' => '分片上传失败'];
    }
    
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadError($file['error']));
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('文件大小超过限制');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('不支持的文件类型');
        }
    }
    
    private function createDirectory($path) {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new Exception('无法创建目录: ' . $path);
            }
        }
        
        // 确保目录可写
        /*if (!is_writable($path)) {
            if (!chmod($path, 0755)) {
                throw new Exception('目录权限设置失败: ' . $path);
            }
	}*/
        
    }

    private function generateFileName($extension) {
        return uniqid() . '_' . time() . '.' . $extension;
    }
    
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        
        return $errors[$errorCode] ?? '未知错误';
    }
}

