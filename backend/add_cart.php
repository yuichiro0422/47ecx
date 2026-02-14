<?php
// カートに商品を追加するバックエンド処理
include "../functions/common.php";

redirectIfUnauth();

// 商品IDと個数がPOSTで送られてきているか確認
// 片方でも来ていなければTOPにリダイレクト
if (empty($_POST["id"]) || empty($_POST["num"])) {
    header("Location: ../index.html");
    exit;
}

// sessionからログイン中のユーザーのID取得
$loginId = $_SESSION["loginId"];

// POSTで送られてきた商品IDと個数取得
$productId = $_POST["id"];
$num = $_POST["num"];

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    // 既に同じ商品がカートに入っていないか調べる
    $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$loginId, $productId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // 既に同じ商品がカートに入っていたら、個数を追加する
        $sql = "UPDATE cart SET num = num + ? WHERE user_id = ? AND product_id = ?";
        $stmt = $dbh->prepare($sql);
        $result = $stmt->execute([$num, $loginId, $productId]);
    } else {
        // 入っていなければ、カートに追加
        $sql = "INSERT INTO cart (user_id, product_id, num) VALUES (?, ?, ?)";
        $stmt = $dbh->prepare($sql);
        $result = $stmt->execute([$loginId, $productId, $num]);
    }

    //　sql実行結果確認　失敗なら元の商品ページに戻す
    if (!$result) {
        header("Location: ../product.php?id=$productId");
        exit;
    }

    header("Location: ../product.php?id=$productId");
    exit;

} catch (PDOException $e) {
    header("Location: ../product.php?id=$productId");
    exit;
}
?>