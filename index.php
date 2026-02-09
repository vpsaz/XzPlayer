<?php
/**
 * @author    校长bloG <1213235865@qq.com>
 * @github    https://github.com/vpsaz/XzPlayer
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// ====================== 硬编码配置区（你只需要改这里） ======================
$baiapiapikey = ''; // 必填：填写你的baiapi apikey 广告过滤功能
$site_title = '自定义播放器标题'; // 可选：播放器页面标题
$site_description = '播放器描述'; // 可选：页面描述
$site_keywords = '播放器关键词'; // 可选：页面关键词
// ===========================================================================

$url = $_POST['url'] ?? $_GET['url'] ?? '';

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

function curlPost($url, $postData = [], $timeout = 8)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno) {
        return false;
    }
    return $response;
}

function checkUrlAvailability($url, $timeout = 5)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

$apiKeyToUse = $baiapiapikey;

if (empty($apiKeyToUse)) {
    $safe_url = htmlspecialchars($url, ENT_QUOTES);
} else {
    $maxAttempts = 3;
    $success = false;
    $file_url = '';

    // 移除了keywords参数
    $postData = [
        'url' => $url,
        'type' => 'json'
    ];

    $apiUrl = 'https://baiapi.cn/api/m3u8gl/' . '?apikey=' . urlencode($apiKeyToUse); //如果是本地部署的过滤接口则替换 https://baiapi.cn/api/m3u8gl/ 为你的接口地址

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $content = curlPost($apiUrl, $postData);
        if ($content !== false) {
            $result = json_decode($content, true);
            if (isset($result['code']) && $result['code'] == 200 && !empty($result['file_url'])) {
                $file_url = $result['file_url'];

                if (checkUrlAvailability($file_url)) {
                    $success = true;
                    break;
                } else {
                    error_log("BaiAPI返回的m3u8文件不可用 (尝试 {$attempt}/{$maxAttempts}): " . $file_url);
                    if ($attempt < $maxAttempts) {
                        sleep(1);
                    }
                    continue;
                }
            }
        }

        if ($attempt < $maxAttempts) {
            sleep(1);
        }
    }

    if (!$success) {
        header('Content-type: application/json;charset=utf-8');
        echo json_encode(['code' => 500, 'msg' => '无法获取可用的播放地址，请稍后再试'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safe_url = htmlspecialchars($file_url, ENT_QUOTES);
}

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($site_title, ENT_QUOTES); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description, ENT_QUOTES); ?>" />
    <meta name="keywords" content="<?php echo htmlspecialchars($site_keywords, ENT_QUOTES); ?>" />
    <link rel="shortcut icon" href="https://pic1.imgdb.cn/item/6812e03558cb8da5c8d5d3c3.png" type="image/x-icon" />
    <script src="https://baiapi.cn/js-lib/Mvideo/hls.min.js"></script>
    <script src="https://baiapi.cn/js-lib/Mvideo/artplayer.min.js"></script>
    <script src="https://baiapi.cn/js-lib/Mvideo/artplayer-plugin-ads.min.js"></script>
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #000;
        }
        #video {
            width: 100%;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
        }
    </style>
</head>
<body>
    <div id="video"></div>
    <script>
        window.videoUrl = '<?php echo $safe_url; ?>';
    </script>
    <script src="https://baiapi.cn/js-lib/XzPlayer/player.js"></script>
</body>
</html>
