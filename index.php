<?php
// トップページ

include "./functions/common.php";


// データベース接続
try {
    connectDB();

    // 都道府県一覧表示用
    // 都道府県情報をすべて取得
    $sql = "SELECT * FROM prefectures";
    $stmt = $dbh->query($sql);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 都道府県を地域ごとにグループ化
    // region_idをキーとして配列にまとめ、同じ地域の都道府県を配列に格納
    $prefectures = [];
    foreach ($list as $row) {
        $regionId = $row["region_id"];
        if (!isset($prefectures[$regionId])) {
            $prefectures[$regionId] = [];
        }
        $prefectures[$regionId][] = $row;
    }


    // 人気商品取得
    $sql = "SELECT
    order_products.product_id,
    sum(num) AS total_num,
    products.name AS product_name,
    products.price,
    product_images.image_path,
    prefectures.id AS prefecture_id,
    prefectures.name AS prefecture_name
    FROM order_products
    LEFT JOIN products ON order_products.product_id = products.id
    LEFT JOIN product_images ON order_products.product_id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    WHERE products.state = 'sale' AND stock > 0
    GROUP BY product_id
    ORDER BY total_num DESC, product_id ASC
    LIMIT 5";
    $stmt = $dbh->query($sql);
    $popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 新着商品取得
    $sql = "SELECT 
    products.id AS product_id,
    products.name AS product_name,
    products.price,
    product_images.image_path,
    prefectures.name AS prefecture_name,
    products.created_at
    FROM products
    LEFT JOIN product_images ON products.id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    WHERE products.state = 'sale' AND stock > 0
    ORDER BY created_at DESC
    LIMIT 5";
    $stmt = $dbh->query($sql);
    $newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // おすすめ商品（ランダム）
    $sql = "SELECT 
    products.id AS product_id,
    products.name AS product_name,
    products.price,
    product_images.image_path,
    prefectures.name AS prefecture_name,
    products.created_at
    FROM products
    LEFT JOIN product_images ON products.id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
    WHERE products.state = 'sale' AND stock > 0
    ORDER BY RAND()
    LIMIT 5";
    $stmt = $dbh->query($sql);
    $recommendProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/japan-map-js@1.0.1/dist/jpmap.min.js"></script>
    <script type="text/javascript" src="dist/jpmap.min.js"></script>
    <script src="./assets/js/main.js"></script>
    <script src="./assets/js/function.js"></script>
</head>
<body>
    <!-- ヘッダー -->
    <?php include "./components/header.php"; ?>

    <!-- サブメニュー -->
    <div class="sub_menu">
        <ul>
            <li><a href="#main-prefecture">都道府県から選ぶ</a></li>
            <li><a href="#main-popular">人気商品</a></li>
            <li><a href="#main-new">新着商品</a></li>
            <li><a href="#main-recomend">おすすめ商品</a></li>
        </ul>
    </div>
    
    <!-- メイン -->
    <main>
        <div class="slider-box">
            <button class="prev-btn">〈</button>
            <div class="slider-container">
                <div class="slider">
                    <div class="slider-images">
                        <img src="./assets/img/banner01.png" alt="">
                        <img src="./assets/img/banner02.png" alt="">
                        <img src="./assets/img/banner03.png" alt="">
                    </div>
                </div>
            </div>
            <button class="next-btn">〉</button>
        </div>

    <div>
        <h5 id="main-prefecture">都道府県から選ぶ</h5>
        <?php
        $areaColors = [
            1 => "#7f7eda", 2 => "#759ef4", 3 => "#759ef4", 4 => "#759ef4", 5 => "#759ef4", 6 => "#759ef4", 7 => "#759ef4",
            8 => "#7ecfea", 9 => "#7ecfea", 10 => "#7ecfea", 11 => "#7ecfea", 12 => "#7ecfea", 13 => "#7ecfea", 14 => "#7ecfea",
            15 => "#7cdc92", 16 => "#7cdc92", 17 => "#7cdc92", 18 => "#7cdc92", 19 => "#7cdc92", 20 => "#7cdc92", 21 => "#7cdc92",
            22 => "#7cdc92", 23 => "#7cdc92", 24 => "#ffe966", 25 => "#ffe966", 26 => "#ffe966", 27 => "#ffe966", 28 => "#ffe966",
            29 => "#ffe966", 30 => "#ffe966", 31 => "#ffcc66", 32 => "#ffcc66", 33 => "#ffcc66", 34 => "#ffcc66", 35 => "#ffcc66",
            36 => "#fb9466", 37 => "#fb9466", 38 => "#fb9466", 39 => "#fb9466", 40 => "#ff9999", 41 => "#ff9999", 42 => "#ff9999",
            43 => "#ff9999", 44 => "#ff9999", 45 => "#ff9999", 46 => "#ff9999", 47 => "#eb98ff"
        ];
        ?>

        <div class="main_top_mapContainer">
            <ul class="main_top_map_regionList">
                <?php foreach ($prefectures as $region) { ?>
                    <li>
                        <?php
                            $regionColor = $areaColors[$region[0]['id']];
                        ?>
                        <p class="main_top_map_prefectureName" style="position: relative; display: inline-block;">
                            <?= $region[0]["region"]; ?>エリア
                            <span class="underline" style="position: absolute; bottom: -5px; left: 0; width: 230px; height: 5px; background-color: <?= $regionColor; ?>;"></span>
                        </p>
                        <ul class="main_top_map_prefectureList">
                            <?php foreach ($region as $prefecture) { ?>
                                <li><a href="./products.php?prefId=<?= $prefecture['id']; ?>"><?= $prefecture["name"]; ?></a></li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
            <?php include "./components/map.php"; ?>
        </div>
    </div>
        <div class="main_top_products">
            <h5 id="main-popular">人気商品</h5>
            <ul class="main_top_productList">
                <?php foreach ($popularProducts as $product) { ?>
                    <li>
                        <a href="./product.php?id=<?= $product["product_id"]; ?>">
                            <?php
                            if ($product["image_path"]) {
                                echo '<img src="' . $product["image_path"] . '">';
                            } else {
                                echo '<img src="https://placehold.jp/300x300.png">';
                            }
                            ?>
                            <div class="main_flex">
                                <p class="main_product_name"><?= $product["product_name"]; ?></p>
                                <p class="main_product_prefecture"><?= $product["prefecture_name"]; ?></p>
                            </div>
                            <p class="main_product_price"><?= $product["price"]; ?><span class="unit">円</span></p>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="main_top_products">
            <h5 id="main-new">新着商品</h5>
            <ul class="main_top_productList">
                <?php foreach ($newProducts as $product) { ?>
                    <li>
                        <a href="./product.php?id=<?= $product["product_id"]; ?>">
                            <?php
                            if ($product["image_path"]) {
                                echo '<img src="' . $product["image_path"] . '">';
                            } else {
                                echo '<img src="https://placehold.jp/300x300.png">';
                            }
                            ?>
                            <div class="main_flex">
                                <p class="main_product_name"><?= $product["product_name"]; ?></p>
                                <p class="main_product_prefecture"><?= $product["prefecture_name"]; ?></p>
                            </div>
                            <p class="main_product_price"><?= $product["price"]; ?><span class="unit">円</span></p>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="main_top_products">
            <h5 id="main-recomend">おすすめ商品</h5>
            <ul class="main_top_productList">
                <?php foreach ($recommendProducts as $product) { ?>
                    <li>
                        <a href="./product.php?id=<?= $product["product_id"]; ?>">
                            <?php
                            if ($product["image_path"]) {
                                echo '<img src="' . $product["image_path"] . '">';
                            } else {
                                echo '<img src="https://placehold.jp/300x300.png">';
                            }
                            ?>
                            <div class="main_flex">
                                <p class="main_product_name"><?= $product["product_name"]; ?></p>
                                <p class="main_product_prefecture"><?= $product["prefecture_name"]; ?></p>
                            </div>
                            <p class="main_product_price"><?= $product["price"]; ?><span class="unit">円</span></p>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </main>

    <!-- フッター -->
    <?php include "./components/footer.php"; ?>


</body>
</html>