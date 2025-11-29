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
        require_once 'db_config.php';

        try {
            // データベースへ接続
            $dsn =  "mysql:host={$host};dbname={$database};charset=utf8mb4" ;
            $pdo = new PDO($dsn, $username, $password);
            // エラー発生時に例外をスローする設定
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // データを取得するSQL
            $sql = "SELECT site_name, site_url FROM sites ORDER BY id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 取得したデータを使って<li>タグと<a>タグを生成
            foreach ($sites as $site) {
                // htmlspecialchars() を使って安全な文字列に変換
                $name = htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars($site['site_url'], ENT_QUOTES, 'UTF-8');
                
                echo "<li><a href='{$url}' target='_blank'>{$name}</a></li>";
            }
        } catch (PDOException $e) {
            echo "データベースエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            // 実際の運用では、エラーの詳細はログファイルに記録し、ユーザーには汎用的なエラーメッセージを見せるのが望ましいです。
            // die();
        }
        ?>
    </ul>
</body>
</html>
