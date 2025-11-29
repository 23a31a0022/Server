<?php
session_start(); // 合成結果のメッセージを受け取るためにセッションを開始します

// 関数が定義されたファイルを読み込みます
require_once 'synthesis.php';
require_once 'gacha.php'; // gacha.phpも読み込む

$pdo = connect_db();
if (!$pdo) {
    die("データベースに接続できませんでした。");
}

// --- POSTリクエスト処理 ---
// このページにPOSTリクエストが送られてきた場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 合成リクエストの処理
    if (isset($_POST['recipe_id'])) {
        $targetRecipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
        if ($targetRecipeId) {
            $result = synthesize_by_recipe($pdo, $targetRecipeId);
            $_SESSION['message'] = $result['message'];
        }
    }

    // ガチャリクエストの処理
    if (isset($_POST['action']) && $_POST['action'] === 'run_gacha') {
        $pulls = filter_input(INPUT_POST, 'pulls', FILTER_VALIDATE_INT);
        if ($pulls > 0) {
            $gacha_results = run_gacha($pdo, $pulls); // 指定された回数ガチャを実行
            if (!empty($gacha_results)) {
                if (apply_gacha_results($pdo, $gacha_results)) {
                    $_SESSION['gacha_results'] = $gacha_results; // 結果をセッションに保存
                    $_SESSION['message'] = "{$pulls}連ガチャを実行しました！";
                } else {
                    $_SESSION['message'] = 'ガチャ結果の反映に失敗しました。';
                }
            }
        } else {
            $_SESSION['message'] = 'ガチャの実行に失敗しました。';
        }
    }

    // 処理が終わったら、同じページにリダイレクトしてPOSTデータをクリアします
    header('Location: game.php'); // ★ 1. リダイレクト先を新しいファイル名に変更
    exit();
}

// --- データの取得 ---

// 1. 現在の所持アイテム一覧を取得
$stmt_materials = $pdo->query("SELECT id, name, possessions FROM Materials ORDER BY id");
$my_items = $stmt_materials->fetchAll(PDO::FETCH_ASSOC); // ★先に全件取得します

// ガチャ結果の名前表示などで使えるように、IDをキーにした連想配列も作成します
$my_items_map = [];
foreach ($my_items as $item) {
    $my_items_map[$item['id']] = $item;
}

// 2. 合成可能なレシピの一覧と、その素材を取得
// SQLでJOINを使い、レシピ情報と素材情報を一度に取得します
$sql_recipes = "
    SELECT
        r.id AS recipe_id,
        r.name AS recipe_name,
        ri.material_id,
        ri.amount,
        m.name AS material_name
    FROM Recipes AS r
    JOIN Recipe_Ingredients AS ri ON r.id = ri.recipe_id
    JOIN Materials AS m ON ri.material_id = m.id
    ORDER BY r.id, ri.material_id;
";
$stmt_recipes = $pdo->query($sql_recipes);
$recipe_data = $stmt_recipes->fetchAll();

// 取得したレシピデータを見やすいように整形します
$recipes = [];
foreach ($recipe_data as $row) {
    $recipes[$row['recipe_id']]['name'] = $row['recipe_name'];
    $recipes[$row['recipe_id']]['ingredients'][] = [
        'name' => $row['material_name'],
        'amount' => $row['amount']
    ];
}

// セッションに保存されたメッセージがあれば取得し、表示後に削除します
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ガチャの結果があれば取得し、表示後に削除します
$gacha_results_ids = $_SESSION['gacha_results'] ?? null;
$gacha_results_display = [];
if ($gacha_results_ids) {
    foreach ($gacha_results_ids as $id) {
        $gacha_results_display[] = $my_items_map[$id]['name'] ?? '不明なアイテム';
    }
}
unset($_SESSION['gacha_results']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アイテム合成</title>
    <style>
        body { font-family: sans-serif; padding: 2em; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 500px; margin-bottom: 2em; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { padding: 1em; margin-bottom: 1em; border-radius: 5px; }
        .success { background-color: #e6ffed; border: 1px solid #b7ebc9; color: #2d6b42; }
        .error { background-color: #ffebe6; border: 1px solid #ffc9ba; color: #8b3b2d; }
        .recipe { border: 1px solid #ddd; padding: 1em; margin-bottom: 1em; width: 480px; }
        .recipe button { font-size: 1em; padding: 0.5em 1em; }
        .container { display: flex; gap: 2em; }
        .gacha-result { background-color: #f0f8ff; padding: 1em; border: 1px solid #b3d7ff; margin-top: 1em; }
    </style>
</head>
<body>

    <h1>アイテム合成</h1>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, '成功') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($gacha_results_display)): ?>
        <div class="gacha-result">
            <strong>ガチャ結果:</strong>
            <?= htmlspecialchars(implode(', ', $gacha_results_display), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <h2>現在の所持アイテム</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名前</th>
                <th>所持数</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($my_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['possessions'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="container">
        <div class="synthesis-area">
            <h2>合成レシピ</h2>
            <?php foreach ($recipes as $id => $recipe): ?>
                <div class="recipe">
                    <h3><?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p>
                        <strong>素材:</strong>
                        <?php
                            $ingredient_parts = [];
                            foreach ($recipe['ingredients'] as $ing) {
                                $ingredient_parts[] = htmlspecialchars($ing['name'], ENT_QUOTES, 'UTF-8') . ' x ' . htmlspecialchars($ing['amount'], ENT_QUOTES, 'UTF-8');
                            }
                            echo implode(' + ', $ingredient_parts);
                        ?>
                    </p>
            <form action="game.php" method="POST"> <!-- ★ 2. フォームの送信先を新しいファイル名に変更 -->
                        <input type="hidden" name="recipe_id" value="<?= $id ?>">
                        <button type="submit">このレシピで合成する</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="gacha-area">
            <h2>素材ガチャ</h2>
            <div class="recipe"> <!-- 見た目を合わせるために .recipe クラスを使用 -->
                <h3>素材ガチャ</h3>
                <p>素材をまとめて入手します。</p>
                <form action="game.php" method="POST" style="margin-bottom: 1em;">
                    <input type="hidden" name="action" value="run_gacha">
                    <input type="hidden" name="pulls" value="10">
                    <button type="submit">10連を引く</button>
                </form>
                <form action="game.php" method="POST">
                    <input type="hidden" name="action" value="run_gacha">
                    <input type="hidden" name="pulls" value="100">
                    <button type="submit">100連を引く</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
