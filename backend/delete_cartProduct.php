<?php
// カート内商品削除処理
include "../functions/common.php";

// カートページで商品の削除ボタンが押された時、
// POSTでcartIDが送られてくる

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// POSTで送られてきている、
// cartIDと取得して格納
if (empty($_POST["cartId"])) {
    header("Location: ../index.php");
    exit;
}
$cartId = $_POST["cartId"];

// DB接続
try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // 送られてきたcartIDを参照して
    // cartテーブルからレコードを削除する
    $sql = "DELETE FROM cart WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$cartId]);

    header("Location: ../cart.php");
    exit;

} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}
?>