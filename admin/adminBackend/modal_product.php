<?php
include "../../functions/common.php";

// action（実行する処理）を取得
// 在庫変更     => editStock
// 商品情報編集 => editProduct
// 商品削除     => delete
// 新規商品登録 => register

$action = isset($_GET["action"]) ? $_GET["action"] : null;

// 処理内容が在庫変更、商品情報編集、商品削除の場合
if ($action == "editStock" || $action == "editProduct" || $action == "delete" || $action == "start") {
    // リクエストされたIDを取得 （どの商品に対して処理を行うか）
    $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
}


try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // 処理を行う商品の情報を取得するSQLを定義
    // 処理内容によってDBに実行するSQL文を変える
    if ($action == "editStock") {
    // ----- 在庫変更 -------------------
        $sql = "SELECT
        products.name AS product_name,
        products.stock,
        CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
        product_images.image_path
        FROM products
        LEFT JOIN product_images ON products.id = product_images.product_id
        LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
        WHERE products.id = ?";
        
    } elseif ($action == "editProduct") {
    // ----- 商品情報編集 ----------------
        $sql = "SELECT
        products.name AS product_name,
        products.price,
        products.description,
        prefectures.id AS prefecture_id,
        CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
        product_images.image_path
        FROM products
        LEFT JOIN product_images ON products.id = product_images.product_id
        LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
        WHERE products.id = ?";
        
        // 都道府県のセレクトメニュー作成のため都道府県を全て取得するSQL文を別で定義
        $select_prefecture_sql = "SELECT CONCAT (name, IFNULL(type, '')) AS name, id FROM prefectures";
        
    } elseif ($action == "delete") {
    // ----- 販売停止 ----------------
        $sql = "SELECT
        products.name AS product_name,
        CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
        product_images.image_path
        FROM products
        LEFT JOIN product_images ON products.id = product_images.product_id
        LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
        WHERE products.id = ?";
        
    } elseif ($action == "start") {
        // ----- 販売開始 ----------------
            $sql = "SELECT
            products.name AS product_name,
            products.state,
            CONCAT (prefectures.name, IFNULL(prefectures.type, '')) AS prefecture_name,
            product_images.image_path
            FROM products
            LEFT JOIN product_images ON products.id = product_images.product_id
            LEFT JOIN prefectures ON products.prefecture_id = prefectures.id
            WHERE products.id = ?";
        
    } elseif ($action == "register") {
    // ----- 新規商品登録 ------------
        // 都道府県のセレクトメニュー作成のためのSQL文だけ定義
        $select_prefecture_sql = "SELECT CONCAT (name, IFNULL(prefectures.type, '')) AS name, id FROM prefectures";
        
    } else {
        header("Location: ../admin_product.php");
        exit;
    }

    // 都道府県を全て取得のSQLが定義されていれば実行
    if (isset($select_prefecture_sql)) {
        $stmt = $dbh->query($select_prefecture_sql);
        $prefectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 商品情報取得のSQLを実行
    if (isset($sql)) {
        $stmt = $dbh->prepare($sql);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    echo "エラー:" . $e->getMessage();
}



?>

<!-- モーダルに表示するhtml -->
<?php
if ($action =="editStock") {
?>
    <!-----------------------------------------------------------------
    在庫変更モーダル
    --------------------------------------------------------------------->
    <!-- モーダルヘッダー -->
    <div class="modal_header admin_stockColor">
        <img src="../assets/img/stock.svg" alt="">   
        <h5 class="modal_title">在庫変更</h5>
    </div>
    
    <!-- 処理を行う商品 -->
    <div class="modal_product">
        <img src="<?= $product['image_path']; ?>">
        <div>
            <p class="modal_productPref"><?= $product["prefecture_name"]; ?></p>
            <p class="modal_productName"><?= $product["product_name"]; ?></p>
            <p class="modal_productId">商品ID : <span><?= $id; ?></span></p>
        </div>
    </div>

    <!-- モーダルボディ（変更フォーム） -->
    <div class="modal_stockForm_container">
        <form id="stock" action="./adminBackend/edit_stock.php" method="POST">
            <h6 class="modal_stockForm_columnName">在庫</h6>
            <div class="form_num modal_form_stock">
                <button type="button" class="form_num_minusBtn"><i class="fa-solid fa-minus"></i></button>
                <input type="number" id="modal_inputStock" name="stock" value="<?= $product['stock']; ?>" placeholder="<?= $product['stock']; ?>" min="0" max="999" required>
                <button type="button" class="form_num_plusBtn"><i class="fa-solid fa-plus"></i></button>
            </div>
            <input type="hidden" name="id" value="<?= $id; ?>">
            
            <!--　バックエンドの処理後にリダイレクトするURLを埋め込むinput -->
            <input type="hidden" name="currentUrl" id="modal_currentUrl">
        </form>
    </div>

    <!-- モーダルフッター -->
    <div class="modal-stock-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button form="stock" type="submit" class="btn btn-primary">変更を確定</button>
    </div>
    
<?php
} elseif ($action == "editProduct") {
?>
    <!--------------------------------------------------------------
    商品情報編集モーダル
    ---------------------------------------------------------------->
    <!-- モーダルヘッダー -->
    <div class="modal_header admin_editColor">
        <img src="../assets/img/pen-to-square-solid.svg">   
        <h5 class="modal_title">商品情報編集</h5>
    </div>
    
    <!-- 処理を行う商品 -->
    <div class="modal_product">
        <img src="<?= $product['image_path']; ?>">
        <div>
            <p class="modal_productPref"><?= $product["prefecture_name"]; ?></p>
            <p class="modal_productName"><?= $product["product_name"]; ?></p>
            <p class="modal_productId">商品ID : <span><?= $id; ?></span></p>
        </div>
    </div>
    
    <!--　モーダルボディ（変更フォーム） -->
    <div class="modal_editForm_container">
        <form id="edit" action="./adminBackend/edit_product.php" method="POST">
            
            <!------ 商品画像 --------------->
            <div class="modal_form_imageContainer">
                <h6 class="modal_editForm_columnName">商品画像</h6>
                <div class="modal_form_image" id="modal_from_imgPreview">
                    <img class="modal_form_image" src="<?= $product['image_path']; ?>">
                </div>
            </div>

            <!----- 商品画像URL ------------->
            <div class="modal_form_imagePathContainer">
                <h6 class="modal_editForm_columnName">画像URL</h6>
                <textarea class="modal_form_imagePath" name="image_path" id="modal_imgUrlInput" rows="4" placeholder="URLを入力してください"><?= $product["image_path"]; ?></textarea>
            </div>
            
            <!------ 商品名 ----------------->
            <div class="modal_form_productNameContainer">
                <h6 class="modal_editForm_columnName">商品名</h6>
                <textarea class="modal_form_productName" name="product_name" rows="3" placeholder="商品名を入力してください" required><?= $product["product_name"]; ?></textarea>
            </div>
        
            <!------ 都道府県名 -------------->
            <div class="modal_form_prefectureNameContainer">
                <h6 class="modal_editForm_columnName">都道府県名</h6>
                <select class="modal_form_prefectureName" name="prefecture_id">
                    <?php
                    foreach ($prefectures as $prefecture) {
                        // 初期選択の設定
                        if ($prefecture["id"] == $product["prefecture_id"]) {
                    ?>
                            <option value="<?= $prefecture["id"]; ?>" selected><?= $prefecture["name"]; ?></option>
                    <?php
                        } else {
                    ?>
                            <option value="<?= $prefecture["id"]; ?>"><?= $prefecture["name"]; ?></option>
                    <?php
                        }
                    }
                    ?>
                </select>
            </div>

            <!------ 価格 ------------------->
            <div class="modal_form_priceContainer">
                <h6 class="modal_editForm_columnName">価格（円）</h6>
                <input type="number" class="modal_form_price" name="price" value="<?= $product["price"]; ?>" placeholder="価格を入力してください" required>
            </div>

            <!------ 商品テキスト ------------>
            <div class="modal_form_descriptionContainer">
                <h6 class="modal_editForm_columnName">商品テキスト</h6>
                <textarea class="modal_form_description" name="description" rows="4" placeholder="商品テキストを入力してください" required><?= $product["description"]; ?></textarea>
            </div>
            
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="before_image_path" value="<?= $product["image_path"]; ?>">
            <input type="hidden" name="before_product_name" value="<?= $product["product_name"]; ?>">
            <input type="hidden" name="before_prefecture_id" value="<?= $product["prefecture_id"]; ?>">
            <input type="hidden" name="before_price" value="<?= $product["price"]; ?>">
            <input type="hidden" name="before_description" value="<?= $product["description"]; ?>">

            <!--　バックエンドの処理後にリダイレクトするURLを埋め込むinput -->
            <input type="hidden" name="currentUrl" id="modal_currentUrl">
        </form>
        
        <!-- モーダルフッター -->
        <div class="modal-edit-footer">
            <button form="edit" type="submit" class="btn btn-primary">変更を確定</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
            <button type="button" class="btn btn-outline-success" id="modal_imgPreviewBtn">画像プレビュー</button>
        </div>
    </div>

<?php
} elseif ($action == "delete") {
?>
    <!--------------------------------------------------------------
    商品販売停止モーダル
    ---------------------------------------------------------------->
    <!-- モーダルヘッダー -->
    <div class="modal_header admin_deleteColor">
        <img src="../assets/img/ban-solid.svg">   
        <h5 class="modal_title">販売停止</h5>
    </div>
    
    <!-- 処理を行う商品 -->
    <div class="modal_product">
        <img src="<?= $product['image_path']; ?>">
        <div>
            <p class="modal_productPref"><?= $product["prefecture_name"]; ?></p>
            <p class="modal_productName"><?= $product["product_name"]; ?></p>
            <p class="modal_productId">商品ID : <span><?= $id; ?></span></p>
        </div>
    </div>

    <!-- モーダルボディ -->
    <div class="modal_deleteForm_container">
        <form id="delete" action="./adminBackend/switch_state.php" method="POST">
            <h6>この商品の販売を停止しますか？</h6>
            <p>停止した商品はユーザーの商品ページには表示されません。</p>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="<?= $action ?>">

            <!--　バックエンドの処理後にリダイレクトするURLを埋め込むinput -->
            <input type="hidden" name="currentUrl" id="modal_currentUrl">
        </form>
    </div>

    <!-- モーダルフッター -->
    <div class="modal-delete-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button form="delete" type="submit" class="btn btn-danger">停止</button>
    </div>

<?php
} elseif ($action == "start") {
?>
    <!-- --------------------------------------------------
    商品販売開始モーダル 
    ------------------------------------------------------->
    <!-- モーダルヘッダー -->
    <div class="modal_header admin_startColor">
        <img src="../assets/img/play-solid.svg">   
        <h5 class="modal_title">販売開始</h5>
    </div>
    
    <!-- 処理を行う商品 -->
    <div class="modal_product">
        <img src="<?= $product['image_path']; ?>">
        <div>
            <p class="modal_productPref"><?= $product["prefecture_name"]; ?></p>
            <p class="modal_productName"><?= $product["product_name"]; ?></p>
            <p class="modal_productId">商品ID : <span><?= $id; ?></span></p>
        </div>
    </div>

    <!-- モーダルボディ -->
    <div class="modal_startForm_container">
        <form id="start" action="./adminBackend/switch_state.php" method="POST">
            <h6>この商品の販売を開始しますか？</h6>
            <?php
            if ($product["state"] == "pend") {
                $state = "保留中";
            } elseif ($product["state"] == "stop") {
                $state = "停止中";
            } else {
                $state = "";
            }
            ?>
            <p>現在の状態：<span><?= $state ?></span></p>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="<?= $action ?>">

            <!--　バックエンドの処理後にリダイレクトするURLを埋め込むinput -->
            <input type="hidden" name="currentUrl" id="modal_currentUrl">
        </form>
    </div>

    <!-- モーダルフッター -->
    <div class="modal-start-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button form="start" type="submit" class="btn btn-success">開始</button>
    </div>
<?php
} elseif ($action == "register") {
?>
    <!----------------------------------------------------
    新規商品登録モーダル 
    ------------------------------------------------------->
    <!-- モーダルヘッダー -->
    <div class="modal_header admin_registerColor">
        <img src="../assets/img/circle-plus.svg">   
        <h5 class="modal_title">新規商品登録</h5>
    </div>
    
    <!--　モーダルボディ（変更フォーム） -->
    <div class="modal_editForm_container">
        <form id="register" action="./adminBackend/register_product.php" method="POST">
            
            <!------ 商品画像 --------------->
            <div class="modal_form_imageContainer">
                <h6 class="modal_editForm_columnName">商品画像</h6>
                <div class="modal_form_image" id="modal_from_imgPreview">
                    <img class="modal_form_image">
                </div>
            </div>

            <!----- 商品画像URL ------------->
            <div class="modal_form_imagePathContainer">
                <h6 class="modal_editForm_columnName">画像URL</h6>
                <textarea class="modal_form_imagePath" name="image_path" id="modal_imgUrlInput" rows="4" placeholder="URLを入力してください"></textarea>
            </div>
            
            <!------ 商品名 ----------------->
            <div class="modal_form_productNameContainer">
                <h6 class="modal_editForm_columnName">商品名</h6>
                <textarea class="modal_form_productName" name="product_name" rows="3" placeholder="商品名を入力してください" required></textarea>
            </div>
        
            <!------ 都道府県名 -------------->
            <div class="modal_form_prefectureNameContainer">
                <h6 class="modal_editForm_columnName">都道府県名</h6>
                <select class="modal_form_prefectureName" name="prefecture_id" required>
                        <option value="" disabled selected>都道府県を選択してください</option>
                    <?php
                    foreach ($prefectures as $prefecture) {
                    ?>
                        <option value="<?= $prefecture["id"]; ?>"><?= $prefecture["name"]; ?></option>
                    <?php      
                    }
                    ?>
                </select>
            </div>

            <!------ 価格 ------------------->
            <div class="modal_form_priceContainer">
                <h6 class="modal_editForm_columnName">価格（円）</h6>
                <input type="number" class="modal_form_price" name="price" placeholder="価格を入力してください" required>
            </div>

            <!------ 商品テキスト ------------>
            <div class="modal_form_descriptionContainer">
                <h6 class="modal_editForm_columnName">商品テキスト</h6>
                <textarea class="modal_form_description" name="description" rows="4" placeholder="商品テキストを入力してください" required></textarea>
            </div>

            <!--　バックエンドの処理後にリダイレクトするURLを埋め込むinput -->
            <input type="hidden" name="currentUrl" id="modal_currentUrl">
            
        </form>
        
        <!-- モーダルフッター -->
        <div class="modal-edit-footer">
            <button form="register" type="submit" class="btn btn-primary">商品を登録</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
            <button type="button" class="btn btn-outline-success" id="modal_imgPreviewBtn">画像プレビュー</button>
        </div>
    </div>

<?php
}
?>