<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件管理系统</title>
    <script src="./includes/axios.min.js"></script>
    <style>
        .upload-area {
            border: 2px dashed #ccc;
            padding: 50px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            margin: 10px 0;
        }
        .progress {
            height: 100%;
            background-color: #007bff;
            width: 0%;
            transition: width 0.3s;
        }
        .file-list {
            margin: 20px 0;
        }
        .file-item {
            padding: 10px;
            border: 1px solid #ddd;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<?php error_reporting(E_ALL);
ini_set('display_errors', 1);?>
<body>
    <h1>文件管理系统</h1>
    
    <!-- 单文件上传 -->
    <div>
        <h3>单文件上传</h3>
        <input type="file" id="singleFile" accept=".xlsx,.xls,.csv,.jpg,.png,.pdf">
        <button onclick="uploadSingle()">上传</button>
    </div>
    
    <!-- 批量上传 -->
    <div>
        <h3>批量文件上传</h3>
        <div class="upload-area" id="batchUploadArea">
            <p>拖拽文件到这里或点击选择文件</p>
            <input type="file" id="batchFiles" multiple style="display:none">
        </div>
        <div id="batchProgress"></div>
    </div>
    
    <!-- 数据导出 -->
    <div>
        <h3>数据导出</h3>
        <select id="exportType">
            <option value="users">用户数据</option>
            <option value="products">产品数据</option>
            <option value="orders">订单数据</option>
        </select>
        <button onclick="exportData()">导出Excel</button>
        <button onclick="exportCSV()">导出CSV</button>
    </div>
    
    <script>
        // 单文件上传
        async function uploadSingle() {
            const fileInput = document.getElementById('singleFile');
            if (!fileInput.files[0]) {
                alert('请选择文件');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            
            try {
                const response = await axios.post('upload.php', formData, {
                    headers: {'Content-Type': 'multipart/form-data'},
                    onUploadProgress: progress => {
                        const percent = Math.round((progress.loaded * 100) / progress.total);
                        console.log(`上传进度: ${percent}%`);
                    }
                });
                
                alert(response.data.success ? '上传成功' : '上传失败: ' + response.data.error);
            } catch (error) {
                alert('上传失败: ' + error.message);
            }
        }
        
        // 批量上传
        document.getElementById('batchUploadArea').addEventListener('click', () => {
            document.getElementById('batchFiles').click();
        });
        
        document.getElementById('batchFiles').addEventListener('change', async function(e) {
            const files = e.target.files;
            if (files.length === 0) return;
            
            const formData = new FormData();
            for (let file of files) {
                formData.append('files[]', file);
            }
            
            try {
                const response = await axios.post('batch-upload.php', formData, {
                    headers: {'Content-Type': 'multipart/form-data'},
                    onUploadProgress: progress => {
                        const percent = Math.round((progress.loaded * 100) / progress.total);
                        document.getElementById('batchProgress').innerHTML = 
                            `<div class="progress-bar"><div class="progress" style="width: ${percent}%"></div></div>`;
                    }
                });
                
                alert(`批量上传完成: ${response.data.success_count} 成功, ${response.data.fail_count} 失败`);
            } catch (error) {
                alert('批量上传失败: ' + error.message);
            }
        });
        
        // 数据导出
        async function exportData() {
            const exportType = document.getElementById('exportType').value;
            
            try {
                const response = await axios.get(`export.php?type=${exportType}&format=excel`);
                if (response.data.success) {
                    window.open(`download.php?file=${response.data.file_path}`);
                } else {
                    alert('导出失败: ' + response.data.error);
                }
            } catch (error) {
                alert('导出失败: ' + error.message);
            }
        }
    </script>
</body>
</html>
