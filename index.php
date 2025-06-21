<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取提交的多个 URL
    $urls = isset($_POST['urls']) ? explode("\n", trim($_POST['urls'])) : [];

    // 结果存储数组
    $statusMessages = [];
    $normalUrls = [];
    $interceptedUrls = [];
    $continueUrls = [];  // 新增一个数组来存放继续访问的 URL

    foreach ($urls as $index => $url) {
        $url = trim($url);
        if (empty($url)) {
            $statusMessages[] = "第 " . ($index + 1) . " 个 URL: 输入的网址为空";
            continue;
        }

        // 核心接口地址
        $coreUrl = "https://link.wtturl.cn/?aid=1128&lang=zh&scene=im&jumper_version=1&target=";
        
        // 拼接完整的请求 URL
        $finalUrl = $coreUrl . urlencode($url);

        // 初始化 cURL 请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $finalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // 允许处理重定向
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");

        // 执行请求并获取页面内容
        $html = curl_exec($ch);

        // 错误处理
        if (curl_errno($ch)) {
            $statusMessages[] = "URL: $url - cURL 错误 - " . curl_error($ch);
        } else {
            // 判断页面内容中是否包含潜在的第三方网址或风险
            if (strpos($html, '停止') !== false) {
                // 如果页面包含 "停止"，认为有安全风险
                $statusMessages[] = ["url" => $url, "status" => "❌ 拦截状态"];
                $interceptedUrls[] = $url;  // 记录拦截的 URL
            } elseif (strpos($html, '继续访问') !== false) {
                // 如果页面包含 "继续访问"，认为需要继续访问
                $statusMessages[] = ["url" => $url, "status" => "⚠️ 继续访问状态"];
                $continueUrls[] = $url;  // 记录继续访问的 URL
            } else {
                // 否则，返回正常状态
                $statusMessages[] = ["url" => $url, "status" => "✅ 正常状态"];
                $normalUrls[] = $url;  // 记录正常的 URL
            }
        }

        // 关闭 cURL 会话
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量检测</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f7f7f8;
            margin: 0;
            padding: 0;
            color: #333;
            width: 100%;
            height: 100%;
        }

        header {
            background-color: #007aff;
            color: white;
            text-align: center;
            padding: 25px 0;
            font-size: 1.5em;
            border-radius: 12px 12px 0 0;
            width: 100%;
        }

        h1 {
            margin: 0;
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 30px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        textarea {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            margin-bottom: 20px;
            border: 2px solid #ccc;
            border-radius: 12px;
            box-sizing: border-box;
            height: 200px;
            resize: none;
        }

        input[type="submit"] {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            border: none;
            border-radius: 12px;
            background-color: #007aff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #005bb5;
        }

        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        th, td {
            padding: 20px;
            text-align: left;
            font-size: 16px;
        }

        th {
            background-color: #f4f4f4;
        }

        .status-cell {
            font-weight: bold;
            text-align: center;
        }

        .status-cell span {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .normal {
            background-color: #e1f7e1;
            color: #28a745;
        }

        .intercepted {
            background-color: #fde0e0;
            color: #dc3545;
        }

        .continue {
            background-color: #fff9e0;
            color: #ffc107;
        }

        .result-section {
            margin-top: 30px;
        }

        .result-section h3 {
            font-size: 1.25em;
            margin-bottom: 10px;
        }

        .result-section table {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .result-section table th,
        .result-section table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .result-section table th {
            background-color: #f4f4f4;
        }

        .result-section table td {
            background-color: #f9f9f9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            textarea {
                height: 160px;
            }

            input[type="submit"] {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>批量检测</h1>
</header>

<div class="container">
    <form action="index.php" method="POST">
        <label for="urls">请输入多个 URL（每个 URL 一行）：</label><br>
        <textarea name="urls" id="urls" placeholder="例如: https://example1.com&#10;https://example2.com&#10;https://example3.com" required></textarea><br>
        <input type="submit" value="提交">
    </form>

    <?php if (isset($statusMessages)): ?>
        <div class="result-section">
            <h3>正常的 URL：</h3>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($normalUrls as $normalUrl): ?>
                        <tr>
                            <td><?= htmlspecialchars($normalUrl); ?></td>
                            <td class="normal">✅ 正常状态</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>拦截的 URL：</h3>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interceptedUrls as $interceptedUrl): ?>
                        <tr>
                            <td><?= htmlspecialchars($interceptedUrl); ?></td>
                            <td class="intercepted">❌ 拦截状态</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>继续访问的 URL：</h3>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($continueUrls as $continueUrl): ?>
                        <tr>
                            <td><?= htmlspecialchars($continueUrl); ?></td>
                            <td class="continue">⚠️ 继续访问状态</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
