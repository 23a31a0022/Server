<?php

require_once 'synthesis.php'; // データベース接続関数を再利用

/**
 * ガチャを実行し、当たったアイテムのリストを返します。
 *
 * @param PDO $pdo
 * @param int $times 実行回数
 * @return array 当たったアイテムのIDリスト
 */
function run_gacha(PDO $pdo, int $times): array
{
    // 1. ガチャの排出アイテムと重みを取得
    $stmt = $pdo->query("SELECT material_id, weight FROM Gacha_Items");
    $gacha_items = $stmt->fetchAll();

    if (empty($gacha_items)) {
        return []; // ガチャにアイテムが設定されていない
    }

    // 2. 重みの合計を計算
    $total_weight = 0;
    foreach ($gacha_items as $item) {
        $total_weight += $item['weight'];
    }

    $results = [];
    for ($i = 0; $i < $times; $i++) {
        // 3. 乱数を生成 (1から重みの合計まで)
        $rand = mt_rand(1, $total_weight);

        // 4. 乱数がどのアイテムに該当するか判定
        $current_weight = 0;
        foreach ($gacha_items as $item) {
            $current_weight += $item['weight'];
            if ($rand <= $current_weight) {
                $results[] = $item['material_id'];
                break; // 当たりが決まったら次の回のループへ
            }
        }
    }
    return $results;
}

/**
 * ガチャの結果をデータベースに反映します。
 *
 * @param PDO $pdo
 * @param array $gacha_results run_gacha()から返されたアイテムIDの配列
 * @return bool 成功したかどうか
 */
function apply_gacha_results(PDO $pdo, array $gacha_results): bool
{
    if (empty($gacha_results)) {
        return false;
    }

    // アイテムIDごとに個数を集計 (例: [1 => 7, 2 => 3])
    $gacha_counts = array_count_values($gacha_results);

    try {
        $pdo->beginTransaction();

        foreach ($gacha_counts as $material_id => $count) {
            $stmt = $pdo->prepare("UPDATE Materials SET possessions = possessions + ? WHERE id = ?");
            $stmt->execute([$count, $material_id]);
        }

        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        return false;
    }
}