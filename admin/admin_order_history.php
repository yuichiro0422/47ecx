<?php
// 管理者：注文管理ページ
include "../functions/common.php";


// プレースフォルダ(key)と、バインドする値(value)を格納するリスト宣言
$params = [];

// // -- 検索ボックス --
// // URLにsearchパラメータがあるか確認
// if (!empty($_GET["search"])) {
//     // ID検索用のプレースフォルダ
//     $params[":searchId"] = $_GET["search"];
//     // 商品名検索用のプレースフォルダ（文字列検索）
//     $params[":searchName"] = "%" . $_GET["search"] . "%";
// }

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
    // 都道府県順
    } elseif ($_GET["sort"] == "pref") {
        $sortSql = " ORDER BY prefecture_id $order";
    }
} else {
    $sortSql = " ORDER BY order_id $order";
}

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    
    $sql = "SELECT
    orders.id AS order_id,
    orders.user_id,
    orders.total_price,
    DATE_FORMAT(orders.created_at, '%Y/%m/%d %H:%i') AS order_date,
    order_products.product_id,
    order_products.price,
    order_products.num,
    products.name AS product_name
    FROM orders
    LEFT JOIN order_products ON orders.id = order_products.order_id
    LEFT JOIN products ON order_products.product_id = products.id";

    // ORDER BY
    $sql .= $sortSql;

    $stmt = $dbh->query($sql);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo $e->getMessage();
    echo $sql;
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
                <h2>注文管理</h2>
            </div>
            <!-- <div class="adminProduct_header">
                
                <div class="adminProduct_searchBox">
                    <form id="searchbox" action="" method="GET">
                        <input type="text" name="search" placeholder="商品名・商品IDで検索" value="<?= isset($_GET["search"]) ? htmlspecialchars($_GET["search"]) : "" ?>">
                        <button form="searchbox" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <button type="button" class="adminProduct_registerBtn admin_registerColor openModal" data-action="register" data-modal-size="modal-lg" data-bs-toggle="modal" data-bs-target="#modal">
                    <img src="../assets/img/circle-plus.svg">
                    <p>新規商品登録</p>
                </button>
            </div> -->
            
            <!-------- フィルタリング -------------------------->
            
            <!-- 都道府県別フィルタリング -->
            
            
            <div class="adminProduct_tableOrderMenu">
                <div class="adminProduct_tableSortSelector_container">
                    <p>並べ替え</p>
                    <select name="sort" id="tableSortSelector" class="adminProduct_tableSortSelector">
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
            
            <div class="admin_tableContainer">
                <table class="admin_table">
                    <tr>
                        <th>ID</th>
                        <th>注文者</th>
                        <th>注文金額(円)</th>
                        <th>注文日時</th>
                        <th>注文商品(商品ID,商品名,価格,個数)</th>
                    </tr>
                    <tbody id="admin_tableBody">
                        <?php
                        foreach ($orders as $order) {
                        ?>
                        <tr>
                            <td class="adminOrderHistory_id"><?= $order[0]["order_id"]; ?></td>
                            <td class="adminOrderHistory_userId"><?= $order[0]["user_id"]; ?></td>
                            <td class="adminOrderHistory_totalPrice"><?= number_format($order[0]["total_price"]); ?></td>
                            <td class="adminOrderHistory_date"><?= $order[0]["order_date"]; ?></td>
                            <td class="adminOrderHistory_products">
                                <ul>
                                    <?php
                                    // 注文ごとに商品リストを繰り返しで出力
                                    foreach ($order as $product) {
                                    ?>
                                    <li>
                                        <p class="adminOrderHistory_productId"><?= $product["product_id"]; ?></p>
                                        <p class="adminOrderHistory_productName"><?= $product["product_name"]; ?></p>
                                        <p class="adminOrderHistory_productPrice"><?= number_format($product["price"]); ?></p>
                                        <p class="adminOrderHistory_productNum"><span>✖</span><?= $product["num"]; ?></p>
                                    </li>   
                                    <?php } ?>
                                </ul>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
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

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>