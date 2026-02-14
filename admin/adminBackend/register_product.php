<?php
// 新規商品登録処理
include "../../functions/common.php";



// リダイレクトするURL(クエリパラメータ―保持)
$url = $_POST["currentUrl"];

if (empty($_POST["product_name"]) ||
empty($_POST["prefecture_id"]) ||
empty($_POST["price"]) ||
empty($_POST["description"])) {
    header("Location: " . $url);
    exit;
}

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    
    // productsテーブルに新規レコードを作成
    // 都道府県ID、商品名、価格、商品テキストを保存
    $sql = "INSERT INTO products (
    prefecture_id,
    name,
    price,
    description
    ) VALUES (?, ?, ?, ?)";

    $stmt = $dbh->prepare($sql);
    $result = $stmt->execute([
        $_POST["prefecture_id"],
        $_POST["product_name"],
        $_POST["price"],
        $_POST["description"],
    ]);

    if (!$result) {
        header("Location: " . $url);
    exit;
    }

    $image_path = isset($_POST["image_path"]) ? $_POST["image_path"] : null;
    
    
    // 今作成した商品の商品画像を保存

    // productsテーブルの作成したレコードと、
    // product_imagesテーブルに作成するレコードを外部キーで紐づけるため
    // productsテーブルに作成したレコードのidを取得
    $lastInsertId = $dbh->lastInsertId();
    // 取得したidとimage_pathを保存
    $sql = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$lastInsertId, $image_path]);

    header("Location: " . $url);
    exit;
    
} catch (PDOException $e) {
    header("Location: " . $url);
    exit;
}
?>