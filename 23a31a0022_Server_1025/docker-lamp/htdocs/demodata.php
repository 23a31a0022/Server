<?php

require_once 'db_config.php';

$dsn =  "mysql:host={$host};dbname={$database};charset=utf8mb4" ;
//echo('aaa');
$pdo = new PDO($dsn,$username,$password);
// $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// $pdo->beginTransaction(); // トランザクション開始
//echo('bbb');
$sql = "SELECT * FROM users/*ここにテーブル名 */ ORDER BY id";
//echo('ccc');
$stmt = $pdo->prepare($sql);
//echo('ddd');
$stmt -> execute();
//echo('eee');
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC )) {
    print_r($row);
    echo("<br/>");
}


    

?>
