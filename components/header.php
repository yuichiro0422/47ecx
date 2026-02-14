<header class="bootstrap-scope">

    <!-- ヘッダーロゴ -->
    <div class="header-logo">
        <a href="./index.php"><img src="./assets/img/47ECX-logo-green.png" alt="ロゴ"></a>
    </div>

    <!-- ヘッダー検索ボックス -->
    <div class="header-search">
        <form method="GET" id="form1" action="./products.php">
            <input id="sbox1" name="search" type="text" placeholder="キーワードを入力">
            <button id="sbtn3" type="submit">
            <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
    </div>

    <!-- ヘッダーアイコン -->
    <div class="header-icon">
        <!-- カートアイコン -->
        <a href="./cart.php" class="required_signin_link">
            <i class="fas fa-cart-shopping"></i>
        </a>

        <!-- ユーザーアイコン -->
        <a href="<?php echo isAuth() ? './backend/signout.php' : './signin.php'; ?>" class="header-sign-link">
            <i class="fas fa-user"></i>
        </a>

        <!-- adminアイコン -->
        <a href="./admin/admin_product.php">
            <i class="fa-solid fa-wrench"></i>
        </a>
    </div>

    <div id="nav-wrapper" class="nav-wrapper">
        <div class="hamburger" id="js-hamburger">
            <span class="hamburger__line hamburger__line--1"></span>
            <span class="hamburger__line hamburger__line--2"></span>
            <span class="hamburger__line hamburger__line--3"></span>
        </div>
        <nav class="sp-nav">
            <ul class="nav-list">
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./products.php">商品</a></li>
                <li><a href="./cart.php" class="required_signin_link">カート</a></li>
                <li><a href="./contact.php">お問い合わせ</a></li>
                <li><a href="./order_history.php" class="required_signin_link">注文履歴</a></li>
                <li>
                    <div>
                        <?php
                        if (isAuth()) {
                            echo "<a href='./backend/signout.php'>サインアウト</a>";
                        } else {
                            echo "<a href='./signin.php'>サインイン</a>";
                        }
                        ?>
                    </div>
                </li>
            </ul>
        </nav>
        <div class="black-bg" id="js-black-bg"></div>
    </div>

    <?php
    if (isAuth()) {
        echo '<input type="hidden" id="Authenticated">';
    }
    ?>
</header>