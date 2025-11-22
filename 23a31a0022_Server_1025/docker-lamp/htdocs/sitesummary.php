<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>リンク一覧</title>
</head>
<body>
    <h1>お気に入りサイト</h1>
    <ul>
        <?php
        // ※実際にはここにデータベースへの接続とデータ取得のコードが入ります。
        require_once 'db_config.php';

        $dsn =  "mysql:host={$host};dbname={$database};charset=utf8mb4" ;
        $pdo = new PDO($dsn,$username,$password);
        // 以下は取得したデータを$sitesという配列に格納したと仮定した例です。
        $sites = [
            ['site_name' => 'Google', 'site_url' => 'https://www.google.com'],
            ['site_name' => 'Wikipedia', 'site_url' => 'https://www.wikipedia.org'],
            ['site_name' => 'ELDENRING', 'site_url' => 'https://www.eldenring.jp/index.html']

        ];

        // 取得したデータを使って<li>タグと<a>タグを生成
        foreach ($sites as $site) {
            // htmlspecialchars() を使って安全な文字列に変換
            $name = htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($site['site_url'], ENT_QUOTES, 'UTF-8');
            
            echo "<li><a href='{$url}' target='_blank'>{$name}</a></li>";
        }
        ?>
    </ul>
</body>
</html>
