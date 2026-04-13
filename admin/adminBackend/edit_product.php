<?php
// 商品情報編集処理
include "../../functions/common.php";


// リダイレクトするURL(クエリパラメータ―保持)
$url = $_POST["currentUrl"];

// IDがない場合はリダイレクト
if (empty($_POST["id"])) {
    header("Location: " . $url);
    exit;
}

$product_id = $_POST["id"];

// 更新するカラムと値を格納する配列
$updateColumnsSql = [];
$params = [":id" => $product_id];

if (!($_POST["image_path"] == $_POST["before_image_path"])) {
    $updateColumnsSql[] = "image_path = :image_path";
    $params[":image_path"] = $_POST["image_path"];   
} 

if (!($_POST["product_name"] == $_POST["before_product_name"])) {
    $updateColumnsSql[] = "products.name = :product_name";
    $params[":product_name"] = $_POST["product_name"];
} 

if (!($_POST["prefecture_id"] == $_POST["before_prefecture_id"])) {
    $updateColumnsSql[] = "prefecture_id = :prefecture_id";
    $params[":prefecture_id"] = $_POST["prefecture_id"];
} 

if (!($_POST["price"] == $_POST["before_price"])) {
    $updateColumnsSql[] = "price = :price";
    $params[":price"] = $_POST["price"];
} 

if (!($_POST["description"] == $_POST["before_description"])) {
    $updateColumnsSql[] = "description = :description";
    $params[":description"] = $_POST["description"];
}

if (empty($updateColumnsSql)) {
    header("Location: " . $url);
    exit;
}

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    $sql = "UPDATE products
    LEFT JOIN product_images ON products.id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    SET " . implode(", ", $updateColumnsSql) . " WHERE products.id = :id";
    
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);

    header("Location: " . $url);
    exit;

} catch (PDOException $e) {
    header("Location: " . $url);
    exit;
}


?>
