<?php
// 商品一覧ページ

include "./functions/common.php";   // 必須

// products.phpでクリックされた商品のidをリンクから取得
$id = $_GET["id"];

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // 商品テーブルと商品画像テーブルを結合してデータベースから取得
    $sql = "SELECT 
    products.id AS product_id,
    products.name AS product_name,
    products.price AS product_price,
    products.description AS product_description,
    product_images.image_path AS image_path,
    CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name
    FROM products
    LEFT JOIN product_images ON products.id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    WHERE products.id = ?";
    
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

        

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
    <title>商品詳細</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="./assets/js/main.js"></script>
</head>
<body>
    <!-- ヘッダー -->
    <?php include "./components/header.php"; ?>

    <!-- メイン -->
    <main>
    <div class="main_product">
            <div class="product_image">
                <!-- 商品画像 -->
                <?php
                if ($product["image_path"]) {
                    echo '<img src="' . $product["image_path"] . '">';
                } else {
                    echo '<img src="https://placehold.jp/300x300.png">';
                }
                ?>
            </div>

            <div class="product_info">
                <!-- 商品名 -->
                <p><?= $product["product_name"]; ?></p>
                <!-- 都道府県名 -->
                <p><?= $product["prefecture_name"]; ?></p>
                <!-- 商品価格 -->
                <p><?= $product["product_price"]; ?><span class="unit">円</span></p>
                <!-- 商品説明文 -->
                <p><?= $product["product_description"]; ?></p>
                <!-- カートに入れる -->
                <form action="./backend/add_cart.php" method="post">
                    <label for="num">数量：</label>
                    <select name="num" id="num" class="styled-select">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i; ?>"><?= $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <br>
                    <input type="hidden" name="id" value="<?= $product["product_id"]; ?>">
                    <button type="submit" class="styled-button required_signin_link">カートに入れる</button>
                </form>
            </div>
        </div>
    </main>
    
    <!-- フッター -->
    <?php include "./components/footer.php"; ?>
</body>
</html>