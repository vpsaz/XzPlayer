<?php
/**
 * @author    校长bloG <1213235865@qq.com>
 * @github    https://github.com/vpsaz
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$url = $_POST['url'] ?? $_GET['url'] ?? '';
$type = $_POST['type'] ?? $_GET['type'] ?? '';

if (empty($url)) {
    header('Content-type: application/json;charset=utf-8');
    echo json_encode(['code' => 404, 'msg' => "请输入URL"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^https?:\/\//i', $url)) {
    header('Content-type: application/json;charset=utf-8');
    echo json_encode(['code' => 404, 'msg' => "请输入正确的URL"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$baiapiApiKey = ''; // M3U8广告过滤 的密钥
$baiapikeywords = ''; // 自定义广告关键词 (多个使用","分隔)
$baiapiUrl = 'https://baiapi.cn/api/m3u8gl/?apikey=' . $baiapiApiKey . '&url=' . urlencode($url) . '&keywords=' . $baiapikeywords;

$cacheDir = __DIR__ . '/Cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheFileName = md5($url) . '.m3u8';
$cacheFilePath = $cacheDir . '/' . $cacheFileName;

$cacheExpire = 60;

if (!file_exists($cacheFilePath) || filemtime($cacheFilePath) < (time() - $cacheExpire)) {
    $maxAttempts = 2;
    $success = false;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $content = @file_get_contents($baiapiUrl);
        if ($content !== false && strlen($content) > 10) {
            file_put_contents($cacheFilePath, $content);
            $success = true;
            break;
        }
    }

    if (!$success) {
        header('Content-type: application/json;charset=utf-8');
        echo json_encode(['code' => 500, 'error' => '无法获取有效的m3u8内容'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$now = time();
foreach (glob(__DIR__ . '/Cache/*.m3u8') as $file) {
    if (is_file($file) && filemtime($file) < $now - $cacheExpire) {
        @unlink($file);
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

if (is_file($cacheFilePath)) {
    $safe_url = $protocol . $host . $scriptDir . '/Cache/' . $cacheFileName;
    $safe_url = htmlspecialchars($safe_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if (strtolower($type) === 'json') {
        header('Content-type: application/json;charset=utf-8');
        $expiresAt = date('Y-m-d H:i:s', filemtime($cacheFilePath) + $cacheExpire);
        echo json_encode([
            'code' => 200,
            'file_url' => $safe_url,
            'expires_at' => $expiresAt
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        header("Location: $safe_url");
    }
} else {
    header('Content-type: application/json;charset=utf-8');
    echo json_encode(['code' => 500, 'error' => '无法获取有效的m3u8内容'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>