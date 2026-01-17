<?php

/**
 * @return PDO|null 接続に成功した場合はPDOオブジェクト、失敗した場合はnull
 */
function connect_db(): ?PDO
{
    require 'db_config.php';
    $dsn =  "mysql:host={$host};dbname={$database};charset=utf8mb4" ;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {

        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // 実際のアプリケーションでは、より詳細なエラーハンドリングを行います
        echo "DB Error: " . $e->getMessage();
        error_log($e->getMessage());
        return null;
    }
}

/**
 * 指定されたレシピIDに基づいてアイテムを合成（強化）します。
 *
 * @param PDO $pdo データベース接続オブジェクト
 * @param int $recipeId 実行するレシピのID
 * @return array 処理結果 (success: bool, message: string)
 */
function synthesize_by_recipe(PDO $pdo, int $recipeId): array
{
    try {
        // トランザクション開始
        $pdo->beginTransaction();

        // 1. レシピと完成品アイテムの存在を確認
        $stmt = $pdo->prepare("SELECT result_item_id FROM Recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        if (!$recipe) {
            $pdo->rollBack();
            return ['success' => false, 'message' => '指定されたレシピが存在しません。'];
        }
        $resultItemId = $recipe['result_item_id'];

        // 2. レシピに必要な素材を取得
        $stmt = $pdo->prepare("SELECT material_id, amount FROM Recipe_Ingredients WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
        $ingredients = $stmt->fetchAll();

        if (empty($ingredients)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'レシピに素材が登録されていません。'];
        }

        // 3. 全ての素材が足りているか確認
        foreach ($ingredients as $ingredient) {
            $materialId = $ingredient['material_id'];
            $requiredAmount = $ingredient['amount'];

            // FOR UPDATEで行をロックし、競合を防ぐ
            $stmt = $pdo->prepare("SELECT possessions FROM Materials WHERE id = ? FOR UPDATE");
            $stmt->execute([$materialId]);
            $material = $stmt->fetch();

            if (!$material || $material['possessions'] < $requiredAmount) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "素材が足りません (ID: {$materialId})。"];
            }
        }

        // 4. 素材アイテムを減らす
        foreach ($ingredients as $ingredient) {
            $stmt = $pdo->prepare("UPDATE Materials SET possessions = possessions - ? WHERE id = ?");
            $stmt->execute([$ingredient['amount'], $ingredient['material_id']]);
        }

        // 5. 完成品アイテムを増やす
        $stmt = $pdo->prepare("UPDATE Materials SET possessions = possessions + 1 WHERE id = ?");
        $stmt->execute([$resultItemId]);

        // すべての処理が成功したらコミット
        $pdo->commit();

        return ['success' => true, 'message' => '合成に成功しました！'];

    } catch (\PDOException $e) {
        // エラーが発生したらロールバック
        $pdo->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'エラーが発生しました。処理を中断します。'];
    }
}

?>
