<?php
// 商品購入処理
include "../functions/common.php";


// cartページの購入ボタン(POST)以外でアクセスしてきた場合、
// 購入処理は実行しない
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// POSTで送られてきている、
// ログインIDと注文の合計金額を取得して格納
if (empty($_POST["loginId"]) || empty($_POST["totalPrice"])) {
    header("Location: ../index.php");
    exit;
}
$loginId = $_POST["loginId"];
$totalPrice = $_POST["totalPrice"];

// DB接続
try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // ログインユーザーのカート内商品取得
    // 商品の在庫も取得し、カート内商品の個数と比較
    $sql = "SELECT
    cart.id AS cart_id,
    cart.product_id AS product_id,
    cart.num,
    products.price,
    products.stock,
    cart.num <= products.stock AS compare
    FROM cart
    LEFT JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = ?";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([$loginId]);
    $orderProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カート内商品の個数と在庫を比較した結果を調べる
    if ($orderProducts) {
        // 在庫不足フラグ変数
        $stockShortage = false;
        foreach ($orderProducts as $product) {
            // 在庫が足りていれば１。足りていなければ０が格納されている 
            if (!$product["compare"]) {
                // フラグを立てる
                $stockShortage = true;
            }
        }

        // 在庫が足りない商品がある場合
        // カートに戻す
        if ($stockShortage) {
            header("Location: ../cart.php");
            exit;
        }
    } else {
        header("Location: ../index.php");
        exit;
    }

    // 購入処理

    // ordersテーブルにユーザーIDと合計価格保存
    $sql = "INSERT INTO orders (user_id, total_price) VALUES (?, ?)";
    $stmt = $dbh->prepare($sql);
    $result = $stmt->execute([$loginId, $totalPrice]);

    if (!$result) {
        header("Location: ../index.php");
        exit;
    }

    // ordersテーブルに作成したレコード（注文）のIDを取得
    $lastInsertId = $dbh->lastInsertId();

    // order_productsテーブルに商品ID、価格、個数を格納
    foreach ($orderProducts as $product) {
        $sql = "INSERT INTO order_products (order_id, product_id, price, num) VALUES (?, ?, ?, ?)";
        $stmt = $dbh->prepare($sql);
        $result = $stmt->execute([$lastInsertId, $product["product_id"], $product["price"], $product["num"]]);

        // 格納できればカートから商品を削除して
        // 商品の在庫を減らす
        if ($result) {
            // カート内商品削除
            $sql = "DELETE FROM cart WHERE id = ?";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([$product["cart_id"]]);

            // 在庫変更
            $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([$product["num"], $product["product_id"]]);

        } else {
            exit;
        }
    }
    header("Location: ../index.php");
    exit;


} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}
?>