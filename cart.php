<?php
// カートページ
include "./functions/common.php";

redirectIfUnauth();

$loginId = $_SESSION["loginId"];

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
    products.name AS product_name,
    products.price,
    products.stock,
    product_images.image_path,
    CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
    cart.num <= products.stock AS compare
    FROM cart
    LEFT JOIN products ON cart.product_id = products.id
    LEFT JOIN product_images ON cart.product_id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    WHERE cart.user_id = ?";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([$loginId]);
    $cartProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カート内商品の個数と在庫を比較した結果を調べる
    if ($cartProducts) {
        // 在庫不足の商品を格納する変数
        $stockShortageProducts = [];
        foreach ($cartProducts as $product) {
            if (!$product["compare"]) {
                $stockShortageProducts[] = $product;
            }
        }
    }

} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カート内商品</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">

</head>
<body>
    <?php include "./components/header.php"; ?>
    <h2>カート内商品</h2>
    <main class="demo_flex">
        <ul class="demo_cart_products">
            <?php
            if (empty($cartProducts)) {
                echo "<li>カートに商品はありません</li>";
            }

            if (!empty($stockShortageProducts)) {
                echo '<p class="stock-shortage-message">在庫が不足しています。</p>';
                echo "<ul>";
                foreach ($stockShortageProducts as $product) {
                ?>
                    <li class="stock-shortage-list">
                        <p>商品名：<?= $product["product_name"]; ?></p>
                        <p>個数：<?= $product["num"]; ?></p>
                        <p>在庫：<?= $product["stock"]; ?></p>
                    </li>  
                <?php
                } 
            }
            $totalPrice = 0;
            $totalNum = 0;
            foreach ($cartProducts as $product) {
                $prefectureName = $product["prefecture_name"];
                $totalPrice += $product["price"] * $product["num"];
                $totalNum += $product["num"];
            ?>
            <li class="cart_productlist">
                <?php
                if ($product["image_path"]) {
                    echo '<img src="' . $product["image_path"] . '">';
                } else {
                    echo '<img src="https://placehold.jp/300x300.png">';
                }
                ?>
                <div class="cart_product_info">
                    <div class="cart_product_namebox">
                        <p class="cart_product_prefecturename"><?= $prefectureName; ?></p>
                        <p class="cart_product_productname"><?= $product["product_name"]; ?></p>
                    </div>
                    <p class="cart_product_price"><?= $product["price"]; ?>円</p>
                    <form class="cart_product_numberbox" action="./backend/edit_cartProductNum.php" method="POST">
                        <p class="cart_product_number">個数：
                            <select class="cartProductNumInput" name="num" onchange="this.form.submit()">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i; ?>" <?= ($product["num"] == $i) ? "selected" : ""; ?>><?= $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </p>
                        <input type="hidden" name="cartId" value="<?= $product["cart_id"]; ?>">
                    </form>
                </div>
                <form class="product_delete" action="./backend/delete_cartProduct.php" method="POST">
                    <button type="submit">削除</button>
                    <input type="hidden" name="cartId" value="<?= $product["cart_id"] ?>">
                </form>
            </li>
            <?php
            }
            ?>
        </ul>
        <div class="demo_cartForm">
            <p>小計（<?= $totalNum; ?>個の商品）：<span class="total-price"><?= $totalPrice; ?></span>円</p>
            <form action="./backend/order.php" method="POST">
                <button type="submit">購入に進む</button>
                <input type="hidden" name="loginId" value="<?= $loginId ?>">
                <input type="hidden" name="totalPrice" value="<?= $totalPrice ?>">
            </form>
        </div>
    </main>
    <?php include "./components/footer.php"; ?>
</body>
</html>
