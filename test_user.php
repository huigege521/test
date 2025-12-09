<?php
// test_user.php
echo "当前运行用户: " . get_current_user() . "\n";
echo "进程用户ID: " . posix_getuid() . "\n";
echo "进程组ID: " . posix_getgid() . "\n";
echo "有效用户ID: " . posix_geteuid() . "\n";

// 检查可写性
$test_file = 'test_write.txt';
if (file_put_contents($test_file, 'test') !== false) {
    echo "✅ 文件写入成功\n";
    unlink($test_file);
} else {
    echo "❌ 文件写入失败\n";
}

// 检查目录权限
echo "uploads/ 权限: " . substr(sprintf('%o', fileperms('uploads/')), -4) . "\n";
?>
