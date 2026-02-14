<?php
// カート内商品個数変更処理
include "../functions/common.php";


// カートページで商品の個数が変更された時、
// POSTでcartIDとnum（個数）が送られてくる

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// POSTで送られてきている、
// cartIDとnumを取得して格納
if (empty($_POST["cartId"]) || empty($_POST["num"])) {
    header("Location: ../index.php");
    exit;
}
$cartId = $_POST["cartId"];
$num = $_POST["num"];
// DB接続
try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // 送られてきたcartIDを参照して
    // cartテーブルのnumを置き換える
    $sql = "UPDATE cart SET num = ? WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$num, $cartId]);

    header("Location: ../cart.php");
    exit;

} catch (PDOException $e) {
    header("Location: ../index.php");
    exit;
}
?>