<?php
$host = 'mysql';
$database = 'test_db'; // 適切なデータベース名に変更してください
$username = 'root';
$password = 'root'; // docker-compose.ymlで定義したパスワード

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $options = [
        // 🚨 重要な設定：エラーモードを例外に設定し、すべてのエラーを捕捉する
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $pdo = new PDO($dsn, $username, $password, $options);

    // 接続成功
    echo "✅ PDO接続に成功しました。\n"; 

    // 以降の処理
    // ...

} catch (PDOException $e) {
    // ❌ 接続失敗
    // エラーメッセージを出力して、原因を特定する
    echo "❌ 接続エラー: " . $e->getMessage() . "\n";
    echo "❌ エラーコード: " . $e->getCode() . "\n";
    // 処理を終了
    exit; 
}

// 接続成功した場合のみ実行されるコード
echo "💡 接続後の処理が実行されています。\n";