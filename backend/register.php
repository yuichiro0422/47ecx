<?php
include "../functions/common.php";
// ログイン中にアクセスしてきたらindex.phpに飛ばす
redirectIfAuth();

// 新規ユーザー作成処理

// エラーのフラグ関数　エラーがある場合はtrueを入れる
$isError = false;

// signup.phpからemailとpasswordが送られてきているか調べる

// empty => 変数がnullや空文字の場合trueを返す
// emailの入力チェック
if (empty($_POST["email"])) {
    $isError = true;
    // エラーが発生した場所を示す   1 => メールアドレスのエラー
    $_SESSION["error"] = 1;
    // エラーの内容を示す   1 => フォームの未入力
    $_SESSION["errorCode"] = 1;
// passwordの入力チェック
} elseif (empty($_POST["password"])) {
    $isError = true;
    // エラーが発生した場所を示す   2 => パスワードのエラー
    $_SESSION["error"] = 2;
    $_SESSION["errorCode"] = 1;
// passwordが4~16文字の英数字かチェック
} elseif (!preg_match("/^[a-zA-Z0-9]{4,16}$/", $_POST["password"])) {
    $isError = true;
    // パスワードのフォーマットエラー
    $_SESSION["error"] = 2;
    $_SESSION["errorCode"] = 3;
}

// フラグ関数でエラーの存在チェック
if ($isError) {
    // 入力されていた値をフォームに戻すためにsessionに保存
    $_SESSION["email"] = $_POST["email"];
    $_SESSION["password"] = $_POST["password"];

    // ユーザー作成ページにリダイレクト
    header('Location: ../signup.php');
    exit;
}

// 送られてきたemailとpasswordを変数に格納
$email = $_POST["email"];

// passwordはハッシュ関数にてハッシュ化して格納
$password = password_hash($_POST["password"], PASSWORD_DEFAULT);


// データベースに接続してemailとpasswordを保存
try {
    // データベースに接続
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    
    // SQL文を定義
    // ?の部分はプレースホルダーで実際の値は後でバインドさせる　※SQLインジェクション対策
    $sql = "INSERT INTO users (email, password) VALUES (?, ?)";

    // prepareでSQL文をコンパイルし、$stmtに格納　※SQLインジェクション対策
    $stmt = $dbh->prepare($sql);

    // 変数に格納してある値をプレースホルダーにバインドして
    // executeでSQL文を実行
    $result = $stmt->execute([$email, $password]);

    // sqlの実行結果を確認し失敗(false)なら戻す
    if (!$result) {
        $_SESSION["error"] = 3;
        $_SESSION["errorCode"] = 2;  

        $_SESSION["email"] = $_POST["email"];
        $_SESSION["password"] = $_POST["password"];
    
        header("Location: ../signup.php");
        exit;
    }
    // 成功したらsession[user]にメールアドレスを格納してログイン状態にし、
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        $_SESSION['error']     = 3;
        $_SESSION['errorCode'] = "";
        header("Location: ../signin.php");
        exit;
    }
    
    $id = $result["id"];
    // sessionにIDとメールアドレスを保存してログイン状態に
    $_SESSION["loginId"] = $id;
    $_SESSION["loginEmail"] = $email;
    // index.phpに飛ばす
    header("Location: ../index.php");
    exit;
    
// データベースからエラーが返ってきたときの処理
} catch (PDOException $e) {
    // エラーコード23000が返ってきたらそのメールアドレスは既に登録済み
    // エラーコード23000 => ユニーク制約違反のコード
    if  ($e->getCode() == 23000) {
        // メールアドレスの重複エラー
        $_SESSION["error"] = 1;
        $_SESSION["errorCode"] = 3;       
    } else {
        // データベース接続エラー
        $_SESSION["error"] = 3;
        $_SESSION["errorCode"] = 1;  
    }
    // 入力されていた値をフォームに戻すためにsessionに保存
    $_SESSION["email"] = $_POST["email"];
    $_SESSION["password"] = $_POST["password"];

    header("Location: ../signup.php");
    exit;
}

?>