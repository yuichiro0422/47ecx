<?php
// 管理者：商品管理ページ
include "../functions/common.php";


// ----- フィルタリング処理 -----
// フィルタリング条件が指定されていれば（URLにパラメータがあれば）、それに応じてWHERE文を定義していく

// プレースフォルダ(key)と、バインドする値(value)を格納するリスト宣言
$params = [];

// -- 検索ボックス --
// URLにsearchパラメータがあるか確認
if (!empty($_GET["search"])) {
    // ID検索用のプレースフォルダ
    $params[":searchId"] = $_GET["search"];
    // 商品名検索用のプレースフォルダ（文字列検索）
    $params[":searchName"] = "%" . $_GET["search"] . "%";
}


//  -- 都道府県フィルタリング --
// URLにprefectureパラメータがあるか確認
if (!empty($_GET["pref"])) {
    // IN句を使って都道府県のリストを渡して絞り込む
    // WHERE prefectures.name IN (:prefecture0, :prefecture1, ...)  選択されている都道府県の数ぶん
    
    // プレースフォルダ―を格納するリスト宣言
    $prefPlaceholders = [];

    // URLから取得した都道府県(文字列)をリストに変換    例："東京,大阪,北海道" => [東京, 大阪, 北海道]
    $prefArray = explode(",", $_GET["pref"]);
    foreach($prefArray as $index => $pref) {
        // プレースフォルダ―をリストに格納
        array_push($prefPlaceholders, ":prefecture$index");
        
        // プレースフォルダ―(key)とバインドする都道府県(value)をリストに格納
        $params[":prefecture$index"] = $pref;
    }
    // プレースフォルダ―のリストをSQL文に埋め込むためにカンマ区切りで文字列に変換
    $prefPlaceholders = implode(", ", $prefPlaceholders);
}


// -- 詳細検索 --
// 詳細検索の全項目の、URLパラメータ、プレースフォルダ、SQL文をリストに格納
$detailFilteringParams = [
    [
        "urlParam" => "price-min",
        "placeholder" => ":priceMin",
        "sql" => "price >= :priceMin"
    ],
    [
        "urlParam" => "price-max",
        "placeholder" => ":priceMax",
        "sql" => "price <= :priceMax"
    ],
    [
        "urlParam" => "stock-min",
        "placeholder" => ":stockMin",
        "sql" => "stock >= :stockMin"
    ],
    [
        "urlParam" => "stock-max",
        "placeholder" => ":stockMax",
        "sql" => "stock <= :stockMax"
    ],
    [
        "urlParam" => "date-min",
        "placeholder" => ":dateMin",
        "sql" => "DATE(products.created_at) >= :dateMin"
    ],
    [
        "urlParam" => "date-max",
        "placeholder" => ":dateMax",
        "sql" => "DATE(products.created_at) <= :dateMax"
    ],
    [
        "urlParam" => "word",
        "placeholder" => ":word",
        "sql" => "products.name LIKE :word OR products.description LIKE :word"
    ]
];

// 各項目ごとに定義したWHERE文を格納するリスト宣言
$detailFilterSql= [];

// 詳細検索項目のリストの処理
foreach($detailFilteringParams as $param) {
    if (!empty($_GET[$param["urlParam"]])) {
        $detailFilterSql[] = $param["sql"];
        if (preg_match("/\bLIKE\b/i", $param["sql"])) {
            $params[$param["placeholder"]] = "%" . $_GET[$param["urlParam"]] . "%";
        } else {
            $params[$param["placeholder"]] = $_GET[$param["urlParam"]];
        }
    }
}

// テーブル並び替え
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

if(!empty($_GET["sort"])) {
    // id順
    if ($_GET["sort"] == "id") {
        $sortSql = " ORDER BY product_id $order";
    // 登録順
    } elseif ($_GET["sort"] == "reg") {
        $sortSql = " ORDER BY created_at $order"; 
    // 価格順
    } elseif ($_GET["sort"] == "price") {
        $sortSql = " ORDER BY price $order";
    // 在庫数順
    } elseif ($_GET["sort"] == "stock") {
        $sortSql = " ORDER BY stock $order";
    // 販売状態
    } elseif ($_GET["sort"] == "state") {
        $sortSql = " ORDER BY FIELD(state, 'stop', 'pend', 'sale') $order";
    // 都道府県
    } elseif ($_GET["sort"] == "pref") {
        $sortSql = " ORDER BY prefecture_id $order";
    }
} else {
    $sortSql = " ORDER BY FIELD(state, 'stop', 'pend', 'sale') $order";
}

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // 都道府県チェックボックス用
    $sql = "SELECT * FROM prefectures";
    $stmt = $dbh->query($sql);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $prefectures = [];
    foreach ($list as $row) {
        $regionId = $row["region_id"];
        if (!isset($prefectures[$regionId])) {
            $prefectures[$regionId] = [];
        }
        $prefectures[$regionId][] = $row;
    }

    // 商品
    $sql = "SELECT
    products.id AS product_id,
    products.name AS product_name,
    products.price,
    products.stock,
    products.state,
    DATE_FORMAT(products.created_at, '%Y/%m/%d %H:%i') AS created_at,
    DATE_FORMAT(products.update_at, '%Y/%m/%d %H:%i') AS update_at,
    product_images.image_path As image_path,
    prefectures.id AS prefecture_id,
    CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name
    FROM products
    LEFT JOIN product_images ON products.id = product_images.product_id
    LEFT JOIN prefectures ON products.prefecture_id = prefectures.id";

    // 検索ボックスが入力されているか
    if (!empty($_GET["search"])) {
        $sql .= " WHERE products.id = :searchId OR products.name LIKE :searchName";
    }

    // 都道府県のフィルタリング条件が指定されているか判定
    if (!empty($prefPlaceholders)) {
        // sqlにほかのフィルタリング条件が指定されているか判定 (sqlにWHEREがあるか)
        if (preg_match("/\bWHERE\b/i", $sql)) {
            // true => 既にWHEREが宣言されていればANDでWHERE文を追加
            $sql .= " AND prefectures.name IN ($prefPlaceholders)";
        } else {
            // false => されていなければWHEREを宣言してWHERE文を追加
            $sql .= " WHERE prefectures.name IN ($prefPlaceholders)";
        }
    }
    // 詳細検索条件が指定されているか判定
    if (!empty($detailFilterSql)) {
        // sqlにほかのフィルタリング条件が指定されているか判定 (sqlにWHEREがあるか)
        if (preg_match("/\bWHERE\b/i", $sql)) {
            // true => 既にWHEREが宣言されていればANDでWHERE文を追加
            $sql .= " AND " . implode(" AND ", $detailFilterSql);
        } else {
            // false => されていなければWHEREを宣言してWHERE文を追加
            $sql .= " WHERE " . implode(" AND ", $detailFilterSql);
        }
    }

    // ORDER BY
    $sql .= $sortSql;
    
    // 都道府県 or 詳細検索が指定されてるか判定
    if (preg_match("/\bWHERE\b/i", $sql)) {
        // true => プレースフォルダ―にバインドして実行
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
    } else {
        // false => そのまま実行
        $stmt = $dbh->query($sql);
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ページネーション機能

    // 1ページに表示する商品の数を指定
    $chunk = 20;

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

    // 在庫変更などの処理後リダイレクトする際のURL
    // クエリパラメータを保持することで処理後も絞り込みや並び替えがそのまま
    // 現在のURLを取得
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $currentUrl = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

} catch (PDOException $e) {
    echo $e->getMessage();
    echo $sql;
}



?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者：商品管理</title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/5e7b90add7.js" crossorigin="anonymous"></script>
    <script src="../assets/js/function.js"></script>
</head>
<body>
    <div class="admin_body">
        <!-- ナビゲーション -->
        <?php include "../components/admin_navigation.php"; ?>

        <div class="adminProduct_container">
            <div class="adminProduct_title">
                <img src="../assets/img/product.svg">
                <h2>商品管理</h2>
            </div>
            <div class="adminProduct_header">
                
                <!-- 検索ボックス -->
                <div class="adminProduct_searchBox">
                    <form id="searchbox" action="" method="GET">
                        <input id="adminSearchBoxInput" type="text" name="search" placeholder="商品名・商品IDで検索" value="<?= isset($_GET["search"]) ? htmlspecialchars($_GET["search"]) : "" ?>">
                        <button id="adminSearchBoxSubmitBtn" form="searchbox" type="submit"><i class="fa-solid fa-magnifying-glass" style="color: #006699;"></i></button>
                        <button class="displayNone" id="adminSearchBoxResetBtn" form="searchbox" type="reset"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                </div>
                <button type="button" class="adminProduct_registerBtn admin_registerColor openModal" data-action="register" data-modal-size="modal-lg" data-bs-toggle="modal" data-bs-target="#modal">
                    <img src="../assets/img/circle-plus.svg">
                    <p>新規商品登録</p>
                </button>
            </div>
            <!-------- フィルタリング -------------------------->
            
            <!-- 都道府県別フィルタリング -->
            <div class="adminProduct_filter_container">
                <div class="adminProduct_filter_prefecture">
                    <div>
                        <ul class="adminProduct_filter_region">
                            <?php
                            $i = 0;
                            foreach ($prefectures as $region) {
                            ?>
                                <li class="adminProduct_filter_region_element">
                                    <form id="filterPrefecture" action="" method="GET">
                                        <p><?= $region[0]["region"]; ?>エリア</p>
                                        <ul>
                                            <?php
                                            foreach ($region as $prefecture) {
                                            ?>
                                                <li class="adminProduct_filter_prefecture_element">
                                                    <input type="checkbox" name="pref" value="<?= $prefecture["name"] ?>" id="filter_prefectureCheckbox<?= $prefecture["id"]; ?>" class="filter_prefectureCheckbox" data-regionId="<?= $prefecture["region_id"]; ?>" data-prefectureId="<?= $prefecture["id"]; ?>">
                                                    <label for="filter_prefectureCheckbox<?= $prefecture["id"]; ?>"><?= $prefecture["name"] ?></label>
                                                </li>
                                            <?php
                                            }
                                            ?>
                                        </ul>
                                    </form>
                                </li>
                            <?php
                            $i ++;
                            if ($i == 3) {
                                echo "</ul>";
                                echo "<ul class='adminProduct_filter_region'>";
                            }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="adminProduct_detailFilter_container">
                <button class="adminProduct_detailFilteringModal_openBtn openModal" id="adminProduct_detailFilteringModal_openBtn" data-bs-toggle="modal" data-bs-target="#modal">
                    <img src="../assets/img/sliders.svg">
                    <p>詳細検索</p>
                </button>
                <ul class="adminProduct_detailFilter_settingList">
                    <li class="adminProduct_detailFilter_settingList_element displayNone" id="adminProductDetailFilterPrice" data-detailSearch="price">
                        <p class="adminProduct_detailFilter_settingList_element_value">
                            <span class="adminProduct_detailFilter_settingList_element_key">価格(円)</span>
                            <?= isset($_GET["price-min"]) ? htmlspecialchars($_GET["price-min"]) : "下限なし" ?>
                            <span class="adminProduct_detailFilter_settingList_element_tilde">~</span>
                            <?= isset($_GET["price-max"]) ? htmlspecialchars($_GET["price-max"]) : "上限なし" ?>
                        </p>
                        <button class="adminProduct_detailFilter_settingList_element_dltBtn"><i class="fa-solid fa-xmark" style="color: #000000;"></i></button>
                    </li>
                    <li class="adminProduct_detailFilter_settingList_element displayNone" id="adminProductDetailFilterStock" data-detailSearch="stock">
                        <p class="adminProduct_detailFilter_settingList_element_value">
                            <span class="adminProduct_detailFilter_settingList_element_key">在庫数</span>
                            <?= isset($_GET["stock-min"]) ? htmlspecialchars($_GET["stock-min"]) : "下限なし" ?>
                            <span class="adminProduct_detailFilter_settingList_element_tilde">~</span>
                            <?= isset($_GET["stock-max"]) ? htmlspecialchars($_GET["stock-max"]) : "上限なし" ?>
                        </p>
                        <button class="adminProduct_detailFilter_settingList_element_dltBtn"><i class="fa-solid fa-xmark" style="color: #000000;"></i></button>
                    </li>
                    <li class="adminProduct_detailFilter_settingList_element displayNone" id="adminProductDetailFilterDate" data-detailSearch="date">
                        <p class="adminProduct_detailFilter_settingList_element_value">
                            <span class="adminProduct_detailFilter_settingList_element_key">登録日</span>
                            <?= isset($_GET["date-min"]) ? htmlspecialchars($_GET["date-min"]) : "下限なし" ?>
                            <span class="adminProduct_detailFilter_settingList_element_tilde">~</span>
                            <?= isset($_GET["date-max"]) ? htmlspecialchars($_GET["date-max"]) : "上限なし" ?>
                        </p>
                        <button class="adminProduct_detailFilter_settingList_element_dltBtn"><i class="fa-solid fa-xmark" style="color: #000000;"></i></button>
                    </li>
                    <li class="adminProduct_detailFilter_settingList_element displayNone" id="adminProductDetailFilterWord" data-detailSearch="word">
                        <p class="adminProduct_detailFilter_settingList_element_value">
                            <span class="adminProduct_detailFilter_settingList_element_key">フリーワード</span>
                            <?= isset($_GET["word"]) ? htmlspecialchars($_GET["word"]) : "指定なし" ?>
                        </p>
                        <button class="adminProduct_detailFilter_settingList_element_dltBtn"><i class="fa-solid fa-xmark" style="color: #000000;"></i></button>
                    </li>
                </ul>
            </div>

            <div class="admin_tableMenu">
                <!-- 表示中の件数とページネーション -->
                <div class="adminProduct_pageNation_container">
                    <?php
                    // 開始インデックスが1より多ければprevボタン表示
                    if (1 < $startIndex) {
                        echo '<button class="adminProduct_prevBtn"><i class="fa-solid fa-chevron-left"></i></button>';
                    }

                    // 〇件中〇件～〇件表示
                    echo "<p><span>" . $products_len . "</span>件中<span>" . $startIndex . "</span>~<span>" . $endIndex . "</span>件表示</p>";

                    // 終了インデックスが商品の最大数未満であればnextボタン表示
                    if ($endIndex < $products_len) {
                        echo '<button class="adminProduct_nextBtn"><i class="fa-solid fa-chevron-right"></i></button>';
                    }
                    ?>
                </div>
                <!-- 並び替え -->
                <div class="adminProduct_tableOrderMenu">
                    <div class="adminProduct_tableSortSelector_container">
                        <p>並べ替え</p>
                        <select name="sort" id="tableSortSelector" class="adminProduct_tableSortSelector">
                            <option value="state">販売状態</option>
                            <option value="id">ID</option>
                            <option value="reg">登録日</option>
                            <option value="price">価格</option>
                            <option value="stock">在庫数</option>
                            <option value="pref">都道府県</option>
                        </select>
                    </div>
                    <div class="admin_tableOrderChangeBtn_container">
                        <button class="admin_tableOrderChangeBtn" name="order" value="asc">
                            <img src="../assets/img/sort-asc.svg">
                            <span>昇順</span>
                        </button>
                        <button class="admin_tableOrderChangeBtn admin_tableOrderChangeBtn_selected" name="order" value="desc">
                            <img src="../assets/img/sort-desc.svg">
                            <span>降順</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="admin_tableContainer">
                <table class="admin_table">
                    <tr>
                        <th>ID</th>
                        <th>商品(画像,都道府県,名前)</th>
                        <th>価格(円)</th>
                        <th>在庫</th>
                        <th>登録日時</th>
                        <th>最終更新日時</th>
                        <th>ステータス</th>
                        <th>管理メニュー</th>
                    </tr>
                    <tbody id="admin_tableBody">
                        <?php
                        foreach ($products as $product) {
                        ?>
                        <tr>
                            <td class="adminProduct_id"><?= $product["product_id"]; ?></td>
                            <td class="adminProduct_product">
                                <div>
                                    <img class="adminProduct_img" src='<?= $product["image_path"];?>'>
                                    <div>
                                        <p class="adminProduct_prefectureName"><?= $product["prefecture_name"]; ?></p>
                                        <p class="adminProduct_productName"><?= $product["product_name"]; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="adminProduct_price"><?= $product["price"]; ?></td>
                            <?php
                                if ($product["stock"] > 0) {
                                    echo '<td class="adminProduct_stock">' . $product["stock"] . '</td>';
                                } else {
                                    echo '<td class="adminProduct_stock sold_out">' . $product["stock"] . '</td>';
                                }
                            ?>
                            <td class="adminProduct_createdAt"><?= $product["created_at"]; ?></td>
                            <td class="adminProduct_updateAt"><?= $product["update_at"]; ?></td>
                            <?php
                            if ($product["state"] == "sale") {
                                echo '<td class="adminProduct_state"><p class="adminProduct_state_public"><i class="fa-regular fa-circle-check"></i>販売中</p></td>';
                            } else if ($product["state"] == "pend") {
                                echo '<td class="adminProduct_state"><p class="adminProduct_state_pend"><i class="fa-solid fa-triangle-exclamation"></i>保留中</p></td>';
                            } else if ($product["state"] == "stop") {
                                echo '<td class="adminProduct_state"><p class="adminProduct_state_stop"><i class="fa-solid fa-ban"></i>停止中</p></td>';
                            }
                            ?>
                            <!-- <div><i class="fa-regular fa-circle-stop" style="color: #f34444;"></i><p>保留中</p></div> -->
                            <!-- 在庫変更、商品編集、商品削除の３つのボタンを用意 -->
                            <!-- 商品のidを格納する変数と、処理内容を判別するためのaction変数を埋め込む-->
                            <td class="adminProduct_adminmenu admin_table_adminmenu"> 
                                <div>
                                    <button type="button" class="admin_table_adminmenuBtn admin_stockColor openModal" data-id="<?= $product['product_id']?>" data-action="editStock" data-bs-toggle="modal" data-bs-target="#modal">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </button>
                                    <button type="button" class="admin_table_adminmenuBtn admin_editColor openModal" data-id="<?= $product['product_id']?>" data-action="editProduct" data-modal-size="modal-lg" data-bs-toggle="modal" data-bs-target="#modal">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php
                                    if ($product["state"] == "pend" || $product["state"] == "stop") {
                                        ?>
                                        <button type="button" class="admin_table_adminmenuBtn admin_startColor openModal" data-id="<?= $product['product_id']?>" data-action="start" data-bs-toggle="modal" data-bs-target="#modal">
                                            <i class="fa-solid fa-play"></i>
                                        </button>
                                        <?php
                                    } else {
                                        ?>
                                        <button type="button" class="admin_table_adminmenuBtn admin_deleteColor openModal" data-id="<?= $product['product_id']?>" data-action="delete" data-bs-toggle="modal" data-bs-target="#modal">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                        <?php
                                    }
                                    ?>
                                    
                                </div> 
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                
            </div>
            <!-- 表示中の件数とページネーション -->
            <div class="adminProduct_pageNation_container_bottom">
                <?php
                // 開始インデックスが1より多ければprevボタン表示
                if (1 < $startIndex) {
                    echo '<button class="adminProduct_prevBtn"><i class="fa-solid fa-chevron-left"></i></button>';
                }

                // 〇件中〇件～〇件表示
                echo "<p><span>" . $products_len . "</span>件中<span>" . $startIndex . "</span>~<span>" . $endIndex . "</span>件表示</p>";

                // 終了インデックスが商品の最大数未満であればnextボタン表示
                if ($endIndex < $products_len) {
                    echo '<button class="adminProduct_nextBtn"><i class="fa-solid fa-chevron-right"></i></button>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- モーダル -->
    <div class="modal fade" id="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="p-0 modal-body" id="modalBody">
                    <!-- ここにリクエストを送ったphpファイルの処理結果が入る -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- 処理後にリダイレクトするURL(hiddenで隠す) -->
    <input type="hidden" id="currentUrl" value="<?= $currentUrl ?>">

<!------ javaScript ------------->
    <script>
        // リダイレクト用のURLをinput["hidden"]から取得
        const currentUrl = document.getElementById("currentUrl");

    // ---- openModalクラスを持ったボタンを取得して、クリックしたときにモーダルが開くイベントを付与-----           
        document.querySelectorAll(".openModal").forEach(button => {
            button.addEventListener("click", function() {
                
            // ----- 操作内容によってモーダルのサイズをクラス名によって変更する処理 ---------
                const modalDialog = document.querySelector(".modal-dialog");

                // モーダルサイズを変更するクラス名が格納されている変数を取得。なければfalse挿入
                const modalSize = this.getAttribute("data-modal-size") || false;
                
                // modalのサイズクラスを初期化　※サイズクラス以外は消さないように注意
                modalDialog.className = "modal-dialog modal-dialog-centered";

                // サイズクラスが取得できていれば追加
                if (modalSize) {
                    modalDialog.classList.add(modalSize);
                }
            // --------------------------------------------------------------------

            // ----- モーダルに表示する内容を別のphpファイルから取得して表示する処理 -------
                const modalBody = document.getElementById("modalBody");
                modalBody.innerHTML = ""; // モーダルの内容を初期化
                // buttonに格納してあるidとactionを取得
                const id = this.getAttribute("data-id");
                const action = this.getAttribute("data-action");

                let path = "";
                if(action !== null) {
                    path = "./adminBackend/modal_product.php?id=" + id + "&action=" + action;
                } else {
                    path = "./adminBackend/modal_detailFiltering.php";
                }
                
                // modal.phpにリクエストを送り、取得したデータをmodalBodyに挿入
                // 変数（id, action）をリクエストに埋め込む
                fetch(path)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error("HTTPエラー" + response.status);
                        }
                        return response.text();
                    })
                    .then(data => {
                        modalBody.innerHTML = data; // モーダル内に取得したデータを挿入
                    })
                    .catch(error => {
                        console.error("エラー:", error);
                        modalBody.innerHTML = "エラーが発生しました";
                    });
            // ------------------------------------------------------------------------  
            });
        });
    // -----------------------------------------

    
    
    // ---- フィルタリング ------------------------------------------------------------------
        // 検索ボックス
        // 検索ボックスリセットボタン
        const searchboxForm = document.getElementById("searchbox");
        searchboxForm.addEventListener("reset", function() {
            // urlのsearchパラメータ削除とフォームのリセット
            const url = new URL(window.location.href);
            const searchbox = this.querySelector("input[name]");
            url.searchParams.delete(searchbox.name);
            searchbox.value = "";
            url.searchParams.set("page", 1);
            window.location.href = url;
        });
        
        function searchBoxResetBtnView() {
            const searchBoxResetBtn = document.getElementById("adminSearchBoxResetBtn");
            const url = new URL(window.location.href);
            if (url.searchParams.has("search")) {
                searchBoxResetBtn.classList.remove("displayNone");
            } else {
                searchBoxResetBtn.classList.add("displayNone");
            }
        }
    
        // 都道府県フィルタリング
        const filterPrefectureForm = document.getElementById("filterPrefecture");
        const prefectureCheckboxes = document.querySelectorAll(".filter_prefectureCheckbox");
        const regionCheckboxes = document.querySelectorAll(".filter_regionCheckbox");
        
        
        // checkboxのチェック状態をsessionに保存する関数
        function keepCheckedPrefectureSession() {
            // 各都道府県のチェック状態を格納する配列
            const keepStoragePrefectures = [];
            const keepStorageRegions = [];
            prefectureCheckboxes.forEach(prefecture => {
                if(prefecture.checked) {
                    keepStoragePrefectures.push(true);
                } else {
                    keepStoragePrefectures.push(false);
                }
            });
            regionCheckboxes.forEach(region => {
                if(region.checked) {
                    keepStorageRegions.push(true);
                } else {
                    keepStorageRegions.push(false);
                }
            });
            // 配列のままだとsessionに保存できないので、jsonに変換して保存
            sessionStorage.setItem("checkedPrefectures", JSON.stringify(keepStoragePrefectures));
            sessionStorage.setItem("checkedRegions", JSON.stringify(keepStorageRegions));
        }

        // sessionからcheckboxのチェック状態の取り出す関数
        function getCheckedPrefectureSession() {
            // 取り出したjsonデータを配列に変換する
            const getStoragePrefectures = JSON.parse(sessionStorage.getItem("checkedPrefectures"));
            const getStorageRegions = JSON.parse(sessionStorage.getItem("checkedRegions"));

            if (getStoragePrefectures) {
                prefectureCheckboxes.forEach((prefecture, index) => {
                    prefecture.checked = getStoragePrefectures[index];
                });
                regionCheckboxes.forEach((region, index) => {
                    region.checked = getStorageRegions[index];
                });
            }
        }

        // チェックされている都道府県をGETで送る関数
        function filterPrefecture() {
            // チェックボックスの状態をsessionに保存する関数を実行
            keepCheckedPrefectureSession();
                
            // チェックがついているチェックボックスを配列に格納
            const checkedPrefectures = Array.from(document.querySelectorAll("input[name='pref']:checked"))
                .map(input => input.value);
            const url = new URL(window.location.href);  
            if (checkedPrefectures.length) {
                url.searchParams.set("pref", checkedPrefectures.join(","));
                url.searchParams.set("page", 1);
                window.location.href = url;
            } else {
                url.searchParams.delete("pref");
                url.searchParams.set("page", 1);
                window.location.href = url;
            }
        }
        
        // 都道府県チェックボックスにchangeイベントを付与
        prefectureCheckboxes.forEach(prefecture => {
            prefecture.addEventListener("change", () => {
                filterPrefecture();
            });
        });

        // // 地方チェックボックスが変化したときのイベント設定
        // regionCheckboxes.forEach(region => {
        //     region.addEventListener("change", () => {
        //         const regionClass = region.getAttribute("data-regionId");
        //         const changeRegionPrefectures = Array.from(prefectureCheckboxes).filter((prefecture) => prefecture.getAttribute("data-regionId") === regionClass);
        //         if(region.checked) {
        //             changeRegionPrefectures.forEach(prefecture => {
        //                 prefecture.checked = true;
        //             });
        //         } else {
        //             changeRegionPrefectures.forEach(prefecture => {
        //                 prefecture.checked = false;
        //             });
        //         }
        //         filterPrefecture();
        //     });
        // });

        // 値が指定されている詳細検索の条件を表示する機能
        function settingDetailSearchItems() {
            const url = new URL(window.location.href);
            url.searchParams.forEach((value, key) => {
                if ("price-min" == key || "price-max" == key) {
                    document.getElementById("adminProductDetailFilterPrice").classList.remove("displayNone");
                } if ("stock-min" == key || "stock-max" == key) {
                    document.getElementById("adminProductDetailFilterStock").classList.remove("displayNone");
                } if ("date-min" == key || "date-max" == key) {
                    document.getElementById("adminProductDetailFilterDate").classList.remove("displayNone");
                } if ("word" == key) {
                    document.getElementById("adminProductDetailFilterWord").classList.remove("displayNone");
                }
            });
        }
        // ✕ボタンでその検索条件を解除する機能
        document.querySelectorAll(".adminProduct_detailFilter_settingList_element_dltBtn").forEach(btn => {
            btn.addEventListener("click", function() {
                const url = new URL(window.location.href);
                const dataDetailSearch = btn.parentElement.getAttribute("data-detailSearch");
                if (dataDetailSearch == "price") {
                    url.searchParams.delete("price-min");
                    url.searchParams.delete("price-max");
                    url.searchParams.set("page", 1);
                    window.location.href = url;
                } else if (dataDetailSearch == "stock") {
                    url.searchParams.delete("stock-min");
                    url.searchParams.delete("stock-max");
                    url.searchParams.set("page", 1);
                    window.location.href = url;
                } else if (dataDetailSearch == "date") {
                    url.searchParams.delete("date-min");
                    url.searchParams.delete("date-max");
                    url.searchParams.set("page", 1);
                    window.location.href = url;
                } else if (dataDetailSearch == "word") {
                    url.searchParams.delete("word");
                    url.searchParams.set("page", 1);
                    window.location.href = url;
                }
            });
        });

        
        // テーブルの並べ替え機能
        const tableSortSelector = document.getElementById("tableSortSelector");
        tableSortSelector.addEventListener("change", function()  {
            const url = new URL(window.location.href);
            url.searchParams.set(this.name, this.value);
            url.searchParams.set("page", 1);
            window.location.href = url;
        });
        function selectedSortSelector() {
            const url = new URL(window.location.href);
            if (url.searchParams.has(tableSortSelector.name)) {
                tableSortSelector.value = url.searchParams.get(tableSortSelector.name);
            }
        }

        
        // テーブル昇順降順切り替え機能
        const orderChangeBtns = document.querySelectorAll(".admin_tableOrderChangeBtn");
        orderChangeBtns.forEach(button => {
            button.addEventListener("click", () => {
                orderChangeBtns.forEach(btn => btn.classList.remove("admin_tableOrderChangeBtn_selected"));
                button.classList.add("admin_tableOrderChangeBtn_selected");
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
                        btn.classList.add("admin_tableOrderChangeBtn_selected");
                    } else {
                        btn.classList.remove("admin_tableOrderChangeBtn_selected");
                    }
                });
            }
        }

        // ページネーション
        // prev(前のページ)
        if (document.querySelectorAll(".adminProduct_prevBtn")) {
            document.querySelectorAll(".adminProduct_prevBtn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set("page", Number("<?php echo $page; ?>") - 1);
                    window.location.href = url;
                });
            });
        }
        // next(次のページ)
        if (document.querySelectorAll(".adminProduct_nextBtn")) {
            document.querySelectorAll(".adminProduct_nextBtn").forEach(btn => {
                btn.addEventListener("click", () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set("page", Number("<?php echo $page; ?>") + 1);
                    window.location.href = url;
                });
            });
        }
        

    // ------ モーダルが表示された後、表示したモーダルに対して行う処理 -------------------------------------
        const modal = document.getElementById("modal");
        
        // モーダルが表示された後に発火するイベント
        modal.addEventListener("shown.bs.modal", function () {

            if(document.getElementById("filter_detail")) {
            // ----- 詳細検索モーダル ----------------------------------------

                const filterDetailForm = document.getElementById("filter_detail");

                // 詳細検索フォームの送信が行われる時（詳細検索ボタンが押された時）発火
                filterDetailForm.addEventListener("submit", function(event) {
                    event.preventDefault();
                    const url = new URL(window.location.href);

                    // 詳細検索フォームのname属性を持つinputを全て取得
                    // inputに値が入力されていなければ、そのinputは送信しない
                    const inputs = this.querySelectorAll("input[name]");
                    inputs.forEach(input => {
                        if(input.value.trim()) {
                            console.log(input.value);
                            url.searchParams.set(input.name, input.value);
                        } else {
                            url.searchParams.delete(input.name);
                        }
                    });
                    url.searchParams.set("page", 1);
                    window.location.href = url;
                });

                // 詳細検索formリセットイベント
                filterDetailForm.addEventListener("reset", function() {
                    // urlの詳細検索パラメータ削除とフォームのリセット
                    // const url = new URL(window.location.href);
                    const inputs = this.querySelectorAll("input[name]");
                    inputs.forEach(input => {
                        // url.searchParams.delete(input.name);
                        input.value = "";
                    });
                    // window.location.href = url;
                });
            // ----------------------------------------------------------------

            } else if (document.getElementById("stock")) {
            // -----在庫変更モーダル ---------------------------------------------------------

                // 在庫のinput取得
                const defaultStock = document.getElementById("modal_inputStock");
                // 変更前のvalueを取得
                const defaultStockValue = document.getElementById("modal_inputStock").value;

                // プラスボタンとマイナスボタンを取得
                const plusBtn = document.querySelector(".form_num_plusBtn");
                const minusBtn = document.querySelector(".form_num_minusBtn");

                // バックエンドの処理後にリダイレクトするURLをフォームに埋め込む
                const modalCurrentUrl = document.getElementById("modal_currentUrl");
                modalCurrentUrl.value = currentUrl.value;

                // それぞれボタンが押された時の処理
                plusBtn.addEventListener("click", function() {
                    // 999以上の時はプラス処理はしない
                    if (defaultStock.value < 999) {
                        // 現在のvalueにブラス１
                        defaultStock.value = Number(defaultStock.value) + 1;
                    }
                });
                minusBtn.addEventListener("click", function() {
                    // 0以下の時はマイナス処理はしない
                    if (defaultStock.value > 0) {
                        // 現在のvalueにマイナス１
                        defaultStock.value = Number(defaultStock.value) - 1;
                    }
                });
                
                // inputからフォーカスが外れたら発火
                defaultStock.addEventListener("blur", function() {
                    // inputが空の状態なら変更前の値を挿入
                    if (this.value == "") {
                        this.value = defaultStockValue;
                    // 999以上なら999を挿入
                    } else if (this.value > 999) {
                        this.value = 999;
                    // 0以下なら0を挿入
                    } else if (this.value < 0) {
                        this.value = 0;
                    }
                });
            // ----------------------------------------------------------------
            
            } else if (document.getElementById("edit")) {
            // ----- 商品情報編集モーダル --------------------------------------
            
                const imgPreviewBtn = document.getElementById("modal_imgPreviewBtn");
                const imgPreview = document.getElementById("modal_from_imgPreview");
                const imgUrlInput = document.getElementById("modal_imgUrlInput");

                // バックエンドの処理後にリダイレクトするURLをフォームに埋め込む
                const modalCurrentUrl = document.getElementById("modal_currentUrl");
                modalCurrentUrl.value = currentUrl.value;

                imgPreviewBtn.addEventListener("click", () => {
                    const imgUrl = imgUrlInput.value.trim();
                    const img = new Image();
                    img.onload = () => {
                        imgPreview.innerHTML = "";
                        imgPreview.appendChild(img);
                    }
                    img.src = imgUrl;
                    img.classList.add("modal_form_image");
                });  
            // ---------------------------------------------------------------

            } else if(document.getElementById("delete") || document.getElementById("start")) {
            // ----- 商品販売状態変更モーダル --------------------------------------

                // バックエンドの処理後にリダイレクトするURLをフォームに埋め込む
                const modalCurrentUrl = document.getElementById("modal_currentUrl");
                modalCurrentUrl.value = currentUrl.value;

            // ----------------------------------------------------------------

            } else if(document.getElementById("register")) {
            // ----- 新規商品登録モーダル --------------------------------------

                const imgPreviewBtn = document.getElementById("modal_imgPreviewBtn");
                const imgPreview = document.getElementById("modal_from_imgPreview");
                const imgUrlInput = document.getElementById("modal_imgUrlInput");

                // バックエンドの処理後にリダイレクトするURLをフォームに埋め込む
                const modalCurrentUrl = document.getElementById("modal_currentUrl");
                modalCurrentUrl.value = currentUrl.value;

                imgPreviewBtn.addEventListener("click", () => {
                    const imgUrl = imgUrlInput.value.trim();
                    const img = new Image();
                    img.onload = () => {
                        imgPreview.innerHTML = "";
                        imgPreview.appendChild(img);
                    }
                    img.src = imgUrl;
                    img.classList.add("modal_form_image");
                });  
            // ----------------------------------------------------------------

            }
        });

        // 詳細検索モーダルのinputに入力情報保持の処理
        document.getElementById("adminProduct_detailFilteringModal_openBtn").addEventListener("click", () => {
            setTimeout(() => {
                const filterDetailForm = document.getElementById("filter_detail");
                const url = new URL(window.location.href);

                filterDetailForm.querySelectorAll("input[name]").forEach(input => {
                    if(url.searchParams.has(input.name)) {
                        input.value = url.searchParams.get(input.name);
                    }
                });
            }, 100);
        });

        // ページ読み込みイベント
        document.addEventListener("DOMContentLoaded", () => {
            getCheckedPrefectureSession();
            selectedSortSelector();
            selectedOrderChangeBtn();
            settingDetailSearchItems();
            searchBoxResetBtnView();
            

            // const url = new URL(window.location.href);
            // console.log(url);
        });
        
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>