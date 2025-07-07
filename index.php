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

header('Content-type: text/html;charset=utf-8');

$safe_url = htmlspecialchars($url, ENT_QUOTES);
$currentDomain = $_SERVER['HTTP_HOST'];
$refererDomain = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);

if ($refererDomain === $currentDomain && !empty($titleParam)) {
    $title = '正在播放：' . htmlspecialchars($titleParam, ENT_QUOTES);
} else {
    $title = htmlspecialchars($conf['site_title'], ENT_QUOTES) . ' - 播放器';
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $conf['description']; ?>">
    <meta name="keywords" content="<?php echo $conf['keywords']; ?>">
    <link rel="shortcut icon" href="https://pic1.imgdb.cn/item/6812e03558cb8da5c8d5d3c3.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.4.8/dist/hls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flv.js@1.6.2/dist/flv.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dashjs@4.7.1/dist/dash.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/artplayer@5.2.3/dist/artplayer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/artplayer-plugin-ads@latest/dist/artplayer-plugin-ads.min.js"></script>
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