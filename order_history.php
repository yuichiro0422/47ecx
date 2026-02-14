<?php
// 注文履歴ページ
include "./functions/common.php";

redirectIfUnauth();

$loginId = $_SESSION["loginId"];

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    $sql = "SELECT
    orders.id AS order_id,
    orders.total_price,
    DATE_FORMAT(orders.created_at, '%Y年%m月%d日') AS order_date,
    order_products.product_id,
    order_products.price,
    order_products.num,
    products.name AS product_name,
    CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
    product_images.image_path AS image_path
    FROM orders 
    LEFT JOIN order_products ON orders.id = order_products.order_id
    LEFT JOIN products ON order_products.product_id = products.id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    LEFT JOIN product_images ON products.id = product_images.product_id
    WHERE orders.user_id = ?;";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([$loginId]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}

// DBから取得した注文履歴をorderIDごと（１回の注文ごと）に分ける
$orders = [];

foreach ($list as $row) {
    $orderId = $row["order_id"];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [];
    }
    $orders[$orderId][] = $row;
}


// orderIDごとに分けられた$ordersの中身
// Array
// (
//      ↓ order_id(１回の注文のid)
//     [7] => Array
//         (
//             そのときに注文された商品
//             [0] => Array ( ... ジンギスカンのデータ ... )
//             [1] => Array ( ... 牛タンのデータ ... )
//             [2] => Array ( ... 沖縄そばのデータ ... )
//             [3] => Array ( ... 博多ラーメンのデータ ... )
//         )
//     [8] => Array
//         (
//             [0] => Array ( ... ジンギスカンのデータ ... )
//             [1] => Array ( ... もんじゃ焼きのデータ ... )
//         )
// )

// リストの順番を逆にする（日付が降順になる）
$orders =  array_reverse($orders);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文履歴</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>
    <?php include "./components/header.php"; ?>
    <h2>注文履歴</h2>
    <main>
        <!-- 
        ２階層のリストで表示
        <ul> <== 注文のリスト
            <li> <== １回の注文
                <ul> <== 商品のリスト
                    <li> <== 商品の情報
                    </li> 
                </ul>
            </li>
            <li> <== １回の注文
                <ul> <== 商品のリスト
                    <li> <== 商品の情報
                    </li> 
                </ul>
            </li>
            　・
            　・
            　・
            　・
        </ul>
          -->
        <ul class="demo_orders">
            <?php
            // 注文リストを繰り返しで出力
            foreach ($orders as $order) {
            ?>
            <li class="demo_order">
                <div class="demo_orderInfo">
                    <!-- 注文日 -->
                    <p><?= $order[0]['order_date'] ?></p>
                    <!-- 合計金額 -->
                    <p>合計：<?= $order[0]['total_price'] ?>円</p>
                </div>
                <ul class="demo_orderProducts">
                    <?php
                    // 注文ごとに商品リストを繰り返しで出力
                    foreach ($order as $product) {
                    ?>
                    <li class="demo_orderProduct">
                        <!-- 商品画像 -->
                        <?php
                        if ($product["image_path"]) {
                            echo '<img src="' . $product["image_path"] . '">';
                        } else {
                            echo '<img src="https://placehold.jp/300x300.png">';
                        }
                        ?>
                        <div>
                            <p><?= $product["prefecture_name"]; ?></p>
                            <p><?= $product["product_name"]; ?></p>
                            <p><?= $product["price"]; ?>円</p>
                            <p>個数：<?= $product["num"]; ?></p>
                        </div>
                    </li>
                    <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
            }
            ?>
        </ul>
    </main>

    <!-- フッター -->
    <?php include "./components/footer.php"; ?>
</body>
</html>