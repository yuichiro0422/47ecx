<?php
// 商品一覧ページ

include "./functions/common.php";   // 必須

// 都道府県別表示用
// 都道府県が選択されていればurlから取得できる。
// 選択されていなければnullが入る。
// iw31_ec/products.php?prefId=東京 => "東京"
$prefectureId = isset($_GET["prefId"]) ? $_GET["prefId"] : null;

// -- 検索ボックス --
// URLにsearchパラメータがあるか確認
if (!empty($_GET["search"])) {
    // ID検索用のプレースフォルダ
    $params[":searchId"] = $_GET["search"];
    // 商品名検索用のプレースフォルダ（文字列検索）
    $params[":searchName"] = "%" . $_GET["search"] . "%";
}

// 商品並び替え機能
// 昇順 降順指定
if (!empty($_GET["order"])) {
    if ($_GET["order"] === "asc") {
        $order = "ASC";
    } else {
        $order = "DESC";
    }
} else {
    $order = "DESC";
}
// 並び順指定
if(!empty($_GET["sort"])) {
    if ($_GET["sort"] == "reg") {
        // 新しい順
        $sortSql = " ORDER BY products.created_at $order"; 
    } elseif ($_GET["sort"] == "price") {
        // 価格順
        $sortSql = " ORDER BY price $order";
    } elseif ($_GET["sort"] == "pref") {
        // 都道府県順
        $sortSql = " ORDER BY prefecture_id $order";
    }
} else {
    $sortSql = " ORDER BY product_id $order";
}


// 商品テーブルと商品画像テーブルを結合してデータベースから取得
// 取得する項目
// -商品ID
// -商品名
// -商品価格
// -商品詳細テキスト
// -商品画像パス
// -都道府県名
// -都道府県の区分「都」「道」「府」「県」


try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    
    if (!empty($_GET["search"])) {

        $word = "%" . $_GET["search"] . "%";

        // 商品情報取得SQL
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
        WHERE products.name LIKE ? OR products.description LIKE ? AND
        products.state = 'sale'";

        // ORDER BY
        $sql .= $sortSql;
    
        $stmt = $dbh->prepare($sql);
        $stmt->execute([$word, $word]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
    } elseif ($prefectureId) {
        // 都道府県が取得できていれば（選択されていれば）
        // WHEREでその都道府県の商品だけDBから取得
        
        // 選択された都道府県取得SQL
        $sql = "SELECT CONCAT (name, IFNuLL(type, '')) AS name FROM prefectures WHERE id = ?";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([$prefectureId]);
        $select_prefecture = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 商品情報取得SQL
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
        WHERE prefectures.id = ? AND
        products.state = 'sale'";

        // ORDER BY
        $sql .= $sortSql;
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute([$prefectureId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // 全商品取得
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
        WHERE products.state = 'sale'";
        
        // ORDER BY
        $sql .= $sortSql;

        $stmt = $dbh->query($sql);
        // カラム名をKeyとした配列で返ってくる
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     
} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}

// ページネーション機能

// 1ページに表示する商品の数を指定
$chunk = 24;

// データベースから取得した商品の数を取得
$products_len = count($products);
?>
<!-- URL取得 -->
<script>const url = new URL(window.location.href);</script>
<?php

// URLのpageパラメータが整数ならば格納。それ以外(小数点、文字)ならばfalseが格納。
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);

// pageがfalse又は0以下の場合URLのpageパラメータに1を格納してリダイレクト
if ($page === false || $page <= 0) {
    ?>
    <script>
        url.searchParams.set("page", 1);
        window.location.href = url;              
    </script>
    <?php
    exit;
}

// 開始インデックス（ページの最初のアイテムのインデックス）
$startIndex = min($chunk * $page - ($chunk - 1), $products_len);

// 終了インデックス（ページの最後のアイテムのインデックス）
// 全体の最大数と比較して超えないようにする
$endIndex = min($chunk * $page, $products_len);

// 開始インデックスが取得した商品するよりも多いかつ
// 商品が1以上の時、リダイレクト
if ($products_len < $startIndex && 0 < $products_len) {
    ?>
    <script>
        url.searchParams.set("page", 1);
        window.location.href = url;              
    </script>
    <?php
}

// 商品の数が$chunkより多いとき
if ($chunk < $products_len) {
    // 商品配列を30刻みで分割する
    $chunks = array_chunk($products, $chunk);

    // page変数を元に(配列のindexを参照するから-1処理)products変数に商品を格納
    $products = $chunks[$page - 1];
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品</title>
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
    <main class="main_main_products">
        <h2>商品一覧</h2>
        <div class="products_main">

            <!-- 都道府県選択メニュー -->
            <div class="main_prefectures">
                <ul>
                    <li class="region">
                        <span class="region-name">北海道・東北<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=1"><img src="./assets/img/prefecture01.png"><span class="prefecture-list_text">北海道</span></a></li>
                            <li><a href="./products.php?prefId=2"><img src="./assets/img/prefecture02.png"><span class="prefecture-list_text">青森</span></a></li>
                            <li><a href="./products.php?prefId=3"><img src="./assets/img/prefecture04.png"><span class="prefecture-list_text">岩手</span></a></li>
                            <li><a href="./products.php?prefId=4"><img src="./assets/img/prefecture05.png"><span class="prefecture-list_text">宮城</span></a></li>
                            <li><a href="./products.php?prefId=5"><img src="./assets/img/prefecture03.png"><span class="prefecture-list_text">秋田</span></a></li>
                            <li><a href="./products.php?prefId=6"><img src="./assets/img/prefecture07.png"><span class="prefecture-list_text">山形</span></a></li>
                            <li><a href="./products.php?prefId=7"><img src="./assets/img/prefecture06.png"><span class="prefecture-list_text">福島</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">関東<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=8"><img src="./assets/img/prefecture08.png"><span class="prefecture-list_text">茨城</span></a></li>
                            <li><a href="./products.php?prefId=9"><img src="./assets/img/prefecture09.png"><span class="prefecture-list_text">栃木</span></a></li>
                            <li><a href="./products.php?prefId=10"><img src="./assets/img/prefecture10.png"><span class="prefecture-list_text">群馬</span></a></li>
                            <li><a href="./products.php?prefId=11"><img src="./assets/img/prefecture11.png"><span class="prefecture-list_text">埼玉</span></a></li>
                            <li><a href="./products.php?prefId=12"><img src="./assets/img/prefecture14.png"><span class="prefecture-list_text">千葉</span></a></li>
                            <li><a href="./products.php?prefId=13"><img src="./assets/img/prefecture12.png"><span class="prefecture-list_text">東京</span></a></li>
                            <li><a href="./products.php?prefId=14"><img src="./assets/img/prefecture13.png"><span class="prefecture-list_text">神奈川</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">中部<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=15"><img src="./assets/img/prefecture15.png"><span class="prefecture-list_text">新潟</span></a></li>
                            <li><a href="./products.php?prefId=16"><img src="./assets/img/prefecture16.png"><span class="prefecture-list_text">富山</span></a></li>
                            <li><a href="./products.php?prefId=17"><img src="./assets/img/prefecture17.png"><span class="prefecture-list_text">石川</span></a></li>
                            <li><a href="./products.php?prefId=18"><img src="./assets/img/prefecture18.png"><span class="prefecture-list_text">福井</span></a></li>
                            <li><a href="./products.php?prefId=19"><img src="./assets/img/prefecture21.png"><span class="prefecture-list_text">山梨</span></a></li>
                            <li><a href="./products.php?prefId=20"><img src="./assets/img/prefecture20.png"><span class="prefecture-list_text">長野</span></a></li>
                            <li><a href="./products.php?prefId=21"><img src="./assets/img/prefecture19.png"><span class="prefecture-list_text">岐阜</span></a></li>
                            <li><a href="./products.php?prefId=22"><img src="./assets/img/prefecture22.png"><span class="prefecture-list_text">静岡</span></a></li>
                            <li><a href="./products.php?prefId=23"><img src="./assets/img/prefecture23.png"><span class="prefecture-list_text">愛知</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">近畿<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=24"><img src="./assets/img/prefecture27.png"><span class="prefecture-list_text">三重</span></a></li>
                            <li><a href="./products.php?prefId=25"><img src="./assets/img/prefecture26.png"><span class="prefecture-list_text">滋賀</span></a></li>
                            <li><a href="./products.php?prefId=26"><img src="./assets/img/prefecture24.png"><span class="prefecture-list_text">京都</span></a></li>
                            <li><a href="./products.php?prefId=27"><img src="./assets/img/prefecture28.png"><span class="prefecture-list_text">大阪</span></a></li>
                            <li><a href="./products.php?prefId=28"><img src="./assets/img/prefecture25.png"><span class="prefecture-list_text">兵庫</span></a></li>
                            <li><a href="./products.php?prefId=29"><img src="./assets/img/prefecture29.png"><span class="prefecture-list_text">奈良</span></a></li>
                            <li><a href="./products.php?prefId=30"><img src="./assets/img/prefecture30.png"><span class="prefecture-list_text">和歌山</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">中国<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=31"><img src="./assets/img/prefecture31.png"><span class="prefecture-list_text">鳥取</span></a></li>
                            <li><a href="./products.php?prefId=32"><img src="./assets/img/prefecture32.png"><span class="prefecture-list_text">島根</span></a></li>
                            <li><a href="./products.php?prefId=33"><img src="./assets/img/prefecture34.png"><span class="prefecture-list_text">岡山</span></a></li>
                            <li><a href="./products.php?prefId=34"><img src="./assets/img/prefecture35.png"><span class="prefecture-list_text">広島</span></a></li>
                            <li><a href="./products.php?prefId=35"><img src="./assets/img/prefecture33.png"><span class="prefecture-list_text">山口</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">四国<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=36"><img src="./assets/img/prefecture37.png"><span class="prefecture-list_text">徳島</span></a></li>
                            <li><a href="./products.php?prefId=37"><img src="./assets/img/prefecture36.png"><span class="prefecture-list_text">香川</span></a></li>
                            <li><a href="./products.php?prefId=38"><img src="./assets/img/prefecture38.png"><span class="prefecture-list_text">愛媛</span></a></li>
                            <li><a href="./products.php?prefId=39"><img src="./assets/img/prefecture39.png"><span class="prefecture-list_text">高知</span></a></li>
                        </ul>
                    </li>
                    <li class="region">
                        <span class="region-name">九州・沖縄<span class="toggle-icon">▶</span></span>
                        <ul class="prefecture-list">
                            <li><a href="./products.php?prefId=40"><img src="./assets/img/prefecture40.png"><span class="prefecture-list_text">福岡</span></a></li>
                            <li><a href="./products.php?prefId=41"><img src="./assets/img/prefecture44.png"><span class="prefecture-list_text">佐賀</span></a></li>
                            <li><a href="./products.php?prefId=42"><img src="./assets/img/prefecture45.png"><span class="prefecture-list_text">長崎</span></a></li>
                            <li><a href="./products.php?prefId=43"><img src="./assets/img/prefecture42.png"><span class="prefecture-list_text">熊本</span></a></li>
                            <li><a href="./products.php?prefId=44"><img src="./assets/img/prefecture41.png"><span class="prefecture-list_text">大分</span></a></li>
                            <li><a href="./products.php?prefId=45"><img src="./assets/img/prefecture43.png"><span class="prefecture-list_text">宮崎</span></a></li>
                            <li><a href="./products.php?prefId=46"><img src="./assets/img/prefecture46.png"><span class="prefecture-list_text">鹿児島</span></a></li>
                            <li><a href="./products.php?prefId=47"><img src="./assets/img/prefecture47.png"><span class="prefecture-list_text">沖縄</span></a></li>
                        </ul>
                    </li> 
                </ul>
            </div>

            <!-- 商品一覧表示エリア -->
            <div class="main_products">

                <!-- <div class="selected_prefecture_icon"><img src="./assets/img/prefectureIcon1.png" alt=""></div> -->
                <div class="products_topArea">
                    <?php
                    // 都道府県が選択されている場合、その都道府県名表示
                    if (!empty($prefectureId)) {
                        echo '<div class="selected_prefecture_icon"><img src="./assets/img/prefectureIcon' .$prefectureId . '.png" alt=""></div>';
                    }
                    ?>
                    <!-- 並び替え機能 -->
                    <div class="products_orderMenu">
                        <div class="products_sortSelector_container">
                            <p>並べ替え</p>
                            <select name="sort" id="productsSortSelector" class="products_sortSelector">
                                <option value="reg">新しい順</option>
                                <option value="price">価格順</option>
                                <option value="pref">都道府県ごとに</option>
                            </select>
                        </div>
                        <div class="products_orderChangeBtn_container">
                            <button class="products_orderChangeBtn" name="order" value="asc">
                                <img src="./assets/img/sort-asc.svg">
                                <span>昇順</span>
                            </button>
                            <button class="products_orderChangeBtn products_orderChangeBtn_selected" name="order" value="desc">
                                <img src="./assets/img/sort-desc.svg">
                                <span>降順</span>
                            </button>
                        </div>
                    </div>
                </div>
                <ul>
                    <?php
                    foreach ($products as $product) {
                    ?>
                    <li>
                        <a href="./product.php?id=<?= $product["product_id"]; ?>">
                            <?php
                            if ($product["image_path"]) {
                                echo '<img src="' . $product["image_path"] . '">';
                            } else {
                                echo '<img src="https://placehold.jp/300x300.png">';
                            }
                            ?>
                            <div>
                                <p><?= $product["product_name"]; ?></p>
                                <p><?= $product["prefecture_name"]; ?></p>
                                <p><?= $product["product_price"]; ?><span class="unit">円</span></p>
                            </div>
                        </a>
                    </li>
                    
                    <?php
                    }
                    ?>
                </ul>

                <!-- 表示中の件数とページネーション -->
                <div class="product_pageNation_container">
                    <?php
                    // 開始インデックスが1より多ければprevボタン表示
                    if (1 < $startIndex) {
                        echo '<button class="product_prevBtn"><i class="fa-solid fa-chevron-left"></i></button>';
                    }

                    // 〇件中〇件～〇件表示
                    echo "<p><span>" . $products_len . "</span>件中<span>" . $startIndex . "</span>~<span>" . $endIndex . "</span>件表示</p>";

                    // 終了インデックスが商品の最大数未満であればnextボタン表示
                    if ($endIndex < $products_len) {
                        echo '<button class="product_nextBtn"><i class="fa-solid fa-chevron-right"></i></button>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <!-- フッター -->
    <?php include "./components/footer.php"; ?>

    <script>
        // テーブルの並べ替え機能
        const productsSortSelector = document.getElementById("productsSortSelector");
        productsSortSelector.addEventListener("change", function()  {
            const url = new URL(window.location.href);
            url.searchParams.set(this.name, this.value);
            url.searchParams.set("page", 1);
            window.location.href = url;
        });
        function selectedSortSelector() {
            const url = new URL(window.location.href);
            if (url.searchParams.has(productsSortSelector.name)) {
                productsSortSelector.value = url.searchParams.get(productsSortSelector.name);
            }
        }

        // テーブル昇順降順切り替え機能
        const orderChangeBtns = document.querySelectorAll(".products_orderChangeBtn");
        orderChangeBtns.forEach(button => {
            button.addEventListener("click", () => {
                orderChangeBtns.forEach(btn => btn.classList.remove("products_orderChangeBtn_selected"));
                button.classList.add("products_orderChangeBtn_selected");
                const url = new URL(window.location.href);
                url.searchParams.set(button.name, button.value);
                url.searchParams.set("page", 1);
                window.location.href = url;
            });
        });
        function selectedOrderChangeBtn() {
            const url = new URL(window.location.href);
            if (url.searchParams.has(orderChangeBtns[0].name)) {
                orderChangeBtns.forEach(btn => {
                    if(btn.value == url.searchParams.get(orderChangeBtns[0].name)) {
                        btn.classList.add("products_orderChangeBtn_selected");
                    } else {
                        btn.classList.remove("products_orderChangeBtn_selected");
                    }
                });
            }
        }

        // ページネーション
        // prev(前のページ)
        if (document.querySelectorAll(".product_prevBtn")) {
            document.querySelectorAll(".product_prevBtn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set("page", Number("<?php echo $page; ?>") - 1);
                    window.location.href = url;
                });
            });
        }
        // next(次のページ)
        if (document.querySelectorAll(".product_nextBtn")) {
            document.querySelectorAll(".product_nextBtn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set("page", Number("<?php echo $page; ?>") + 1);
                    window.location.href = url;
                });
            });
        }

        // ページ読み込みイベント
        document.addEventListener("DOMContentLoaded", () => {
            selectedSortSelector();
            selectedOrderChangeBtn();
        });
    </script>
</body>
</html>