<?php

require_once 'db_config.php';

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "データベース初期化を開始します...<br>";

    // --- 既存テーブルの削除 (外部キー制約があるため順序に注意) ---
    $pdo->exec("DROP TABLE IF EXISTS Gacha_Items");
    $pdo->exec("DROP TABLE IF EXISTS Recipe_Ingredients");
    $pdo->exec("DROP TABLE IF EXISTS Recipes");
    $pdo->exec("DROP TABLE IF EXISTS Materials");

    // --- テーブル作成 ---

    // 1. Materials (アイテム・素材)
    // game.php で possessions(所持数), rarity(レア度) を使っているので定義します
    $sql = "CREATE TABLE Materials (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        possessions INT DEFAULT 0,
        rarity INT DEFAULT 1,
        power INT DEFAULT 0
    )";
    $pdo->exec($sql);
    echo "Materials テーブルを作成しました。<br>";

    // 2. Recipes (合成レシピ)
    $sql = "CREATE TABLE Recipes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        result_item_id INT NOT NULL,
        FOREIGN KEY (result_item_id) REFERENCES Materials(id)
    )";
    $pdo->exec($sql);
    echo "Recipes テーブルを作成しました。<br>";

    // 3. Recipe_Ingredients (レシピの素材)
    $sql = "CREATE TABLE Recipe_Ingredients (
        recipe_id INT,
        material_id INT,
        amount INT NOT NULL,
        PRIMARY KEY (recipe_id, material_id),
        FOREIGN KEY (recipe_id) REFERENCES Recipes(id),
        FOREIGN KEY (material_id) REFERENCES Materials(id)
    )";
    $pdo->exec($sql);
    echo "Recipe_Ingredients テーブルを作成しました。<br>";

    // 4. Gacha_Items (ガチャ排出リスト)
    $sql = "CREATE TABLE Gacha_Items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        material_id INT NOT NULL,
        weight INT NOT NULL,
        FOREIGN KEY (material_id) REFERENCES Materials(id)
    )";
    $pdo->exec($sql);
    echo "Gacha_Items テーブルを作成しました。<br>";

    // --- データ投入 ---

    // Materials (ID, 名前, 所持数, レア度, パワー)
    $stmt = $pdo->prepare("INSERT INTO Materials (id, name, possessions, rarity, power) VALUES (?, ?, ?, ?, ?)");
    $materials = [
        [1, '薬草', 10, 1, 0],
        [2, '魔素', 5, 1, 0],
        [3, '鉄鉱石', 5, 1, 0],
        [4, 'ポーション', 0, 2, 10],
        [5, '鉄の剣', 0, 2, 20],
        [6, 'すごい薬草', 0, 3, 0],
        [7, 'ハイポーション', 0, 3, 50],
        [8, '伝説の剣', 0, 5, 100],
    ];
    foreach ($materials as $m) {
        $stmt->execute($m);
    }
    echo "アイテムデータを投入しました。<br>";

    // Recipes (ID, レシピ名, 完成品ID)
    $stmt = $pdo->prepare("INSERT INTO Recipes (id, name, result_item_id) VALUES (?, ?, ?)");
    $recipes = [
        [1, 'ポーション生成', 4],
        [2, '鉄の剣作成', 5],
        [3, 'ハイポーション調合', 7],
    ];
    foreach ($recipes as $r) {
        $stmt->execute($r);
    }
    echo "レシピデータを投入しました。<br>";

    // Recipe_Ingredients (レシピID, 素材ID, 必要数)
    $stmt = $pdo->prepare("INSERT INTO Recipe_Ingredients (recipe_id, material_id, amount) VALUES (?, ?, ?)");
    $ingredients = [
        [1, 1, 2], // ポーション: 薬草x2
        [1, 2, 1], // ポーション: 魔素x1
        [2, 3, 3], // 鉄の剣: 鉄鉱石x3
        [2, 2, 2], // 鉄の剣: 魔素x2
        [3, 4, 1], // ハイポーション: ポーションx1
        [3, 6, 1], // ハイポーション: すごい薬草x1
    ];
    foreach ($ingredients as $i) {
        $stmt->execute($i);
    }

    // Gacha_Items (素材ID, 重み)
    $stmt = $pdo->prepare("INSERT INTO Gacha_Items (material_id, weight) VALUES (?, ?)");
    $gacha = [
        [1, 50], // 薬草
        [2, 30], // 魔素
        [3, 30], // 鉄鉱石
        [6, 5],  // すごい薬草
        [8, 1],  // 伝説の剣
    ];
    foreach ($gacha as $g) {
        $stmt->execute($g);
    }
    echo "ガチャデータを投入しました。<br>";

    echo "<hr><strong>全ての初期化が完了しました。</strong><br>";
    echo "<a href='game.php'>ゲーム画面へ移動する</a>";

} catch (PDOException $e) {
    echo "エラーが発生しました: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>
