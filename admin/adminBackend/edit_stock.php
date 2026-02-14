<?php
// 在庫変更処理
include "../../functions/common.php";



// リダイレクトするURL(クエリパラメータ―保持)
$url = $_POST["currentUrl"];

if (empty($_POST["id"]) || !isset($_POST["stock"])) {
    header("Location: " . $url);
    exit;
}

$id = $_POST["id"];
$stock = $_POST["stock"];

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    $sql = "UPDATE products SET stock = ? WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$stock, $id]);
    
    echo $stock;

    header("Location: " . $url);
    exit;

} catch (PDOException $e) {
    header("Location: " . $url);
    exit;
}
?>