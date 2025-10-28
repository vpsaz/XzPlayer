<?php
/**
 * @author    校长bloG <1213235865@qq.com>
 * @github    https://github.com/vpsaz/XzPlayer
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$config_file = __DIR__ . '/config.php';
$conf = include($config_file);

$url = $_POST['url'] ?? $_GET['url'] ?? '';
$titleParam = $_POST['title'] ?? $_GET['title'] ?? '';

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

$baiapikeywords = ''; // 自定义广告关键词 (多个使用","分隔)
$baiapiapikey = ''; // 若单独使用该接口且需要开启广告过滤则需要填写 apikey

function curlPost($url, $postData = [], $timeout = 8) {
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

// 确定要使用的 API Key
$apiKeyToUse = '';
if (!empty($conf['baiapi_key'])) {
    $apiKeyToUse = $conf['baiapi_key'];
} elseif (!empty($baiapiapikey)) {
    $apiKeyToUse = $baiapiapikey;
}

if (empty($apiKeyToUse)) {
    $safe_url = htmlspecialchars($url, ENT_QUOTES);
} else {
    $maxAttempts = 2;
    $success = false;
    $file_url = '';

    $postData = [
        'url' => $url,
        'keywords' => $baiapikeywords,
        'type' => 'json'  // 添加type=json参数
    ];

    $apiUrl = 'https://baiapi.cn/api/m3u8gl/?apikey=' . urlencode($apiKeyToUse);

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $content = curlPost($apiUrl, $postData);
        if ($content !== false) {
            $result = json_decode($content, true);
            if (isset($result['code']) && $result['code'] == 200 && !empty($result['file_url'])) {
                $file_url = $result['file_url'];
                $success = true;
                break;
            }
        }
    }

    if (!$success) {
        header('Content-type: application/json;charset=utf-8');
        echo json_encode(['code' => 500, 'msg' => '无法从 BaiAPI 获取播放地址，请稍后再试'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safe_url = htmlspecialchars($file_url, ENT_QUOTES);
}

$currentDomain = $_SERVER['HTTP_HOST'];
$refererDomain = '';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $refererDomain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?? '';
}

if ($refererDomain === $currentDomain && !empty($titleParam)) {
    $title = '正在播放：' . htmlspecialchars($titleParam, ENT_QUOTES);
} else {
    $title = htmlspecialchars($conf['site_title'], ENT_QUOTES) . ' - 播放器';
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $conf['description']; ?>" />
    <meta name="keywords" content="<?php echo $conf['keywords']; ?>" />
    <link rel="shortcut icon" href="https://pic1.imgdb.cn/item/6812e03558cb8da5c8d5d3c3.png" type="image/x-icon" />
    <script src="https://v.vpsaz.cn/js/Mvideo/hls.min.js"></script>
    <script src="https://v.vpsaz.cn/js/Mvideo/flv.min.js"></script>
    <script src="https://v.vpsaz.cn/js/Mvideo/dash.all.min.js"></script>
    <script src="https://v.vpsaz.cn/js/Mvideo/artplayer.min.js"></script>
    <script src="https://v.vpsaz.cn/js/Mvideo/artplayer-plugin-ads.min.js"></script>
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
    <script src="https://v.vpsaz.cn/js/player.js"></script>
</body>
</html>
