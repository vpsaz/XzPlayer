<?php
/**
 * @author    校长bloG <1213235865@qq.com>
 * @github    https://github.com/vpsaz/XzPlayer
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$api_url = 'https://baiapi.cn/api/m3u8af/'; // 必填：填写接口地址 (可替换本地过滤接口)
$api_key = ''; // 必填：填写你的 apikey 广告过滤密钥
$site_title = 'XzPlayer'; // 可选：播放器页面标题
$site_description = 'M3U8视频播放器'; // 可选：页面描述
$site_keywords = 'XzPlayer,M3U8,播放器,视频播放'; // 可选：页面关键词

$safe_url = '';

$video_url = isset($_REQUEST['url']) ? trim($_REQUEST['url']) : '';

if (!empty($video_url) && preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $video_url)) {
    $post_data = [
        'url' => $video_url,
        'type' => 'json',
        'apikey' => $api_key
    ];

    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer: ' . $api_url,
            'Accept: application/json, text/plain, */*'
        ]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        curl_close($ch);
    } else {
        curl_close($ch);
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($result['code']) && $result['code'] == 200) {
                if (isset($result['file_url']) && filter_var($result['file_url'], FILTER_VALIDATE_URL)) {
                    $safe_url = $result['file_url'];
                }
            }
        }
    }
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
