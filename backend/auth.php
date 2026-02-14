<?php

include "../functions/common.php";
// ログイン中にアクセスしてきたらindex.phpに飛ばす
redirectIfAuth();

// ログイン処理

// エラーのフラグ関数　エラーがある場合はtrueを入れる
$isError = false;

// signin.phpからemailとpasswordが送られてきているか調べる

// emailとpasswordの入力チェック
// empty => 変数がnullや空文字の場合trueを返す
if (empty($_POST["email"])) {
    $isError = true;
    // エラーが発生した場所を示す   1 => メールアドレスのエラー
    $_SESSION["error"] = 1;
    // エラーの内容を示す   1 => フォームの未入力
    $_SESSION["errorCode"] = 1;
} elseif (empty($_POST["password"])) {
    $isError = true;
    // エラーが発生した場所を示す   2 => パスワードのエラー
    $_SESSION["error"] = 2;
    $_SESSION["errorCode"] = 1;
}

// フラグ関数でエラーの存在チェック
if ($isError) {
    // 入力されていた値をフォームに戻すためにsessionに保存
    $_SESSION["email"] = $_POST["email"];
    $_SESSION["password"] = $_POST["password"];

    // サインインページにリダイレクト
    header('Location: ../signin.php');
    exit;
}

// 送られてきたemailとpasswordを変数に格納
$email    = $_POST['email'];
$password = $_POST['password'];

// データベースに接続して認証を行う
try {
    // データベースに接続
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // SQL文を定義
    // ?の部分はプレースホルダーで実際の値は後でバインドさせる　※SQLインジェクション対策
    $sql = "SELECT * FROM users WHERE email = ?";

    // prepareでSQL文をコンパイルし、$stmtに格納　※SQLインジェクション対策
    $stmt = $dbh->prepare($sql);

    // 変数に格納してある値をプレースホルダーにバインドして
    // executeでSQL文を実行
    $stmt->execute([$email]);

    // 実行されたSQLから結果を取得
    // 正しくないメールアドレスが入力されていた場合falseが返ってくる
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // $resultにfalseが返ってきていればsignin.phpに戻す
    if (!$result) {
        // 入力されたメールアドレスが正しくことないを示すコードをsessionに保存
        $_SESSION['error']     = 1;
        $_SESSION['errorCode'] = 2;

        $_SESSION["email"] = $_POST["email"];
        $_SESSION["password"] = $_POST["password"];

        // サインインページにリダイレクト
        header('Location: ../signin.php');
        exit;
    }

    // 送られてきたpasswordとデータベースに格納されているpasswordが一致するか確認
    // password_verify => ハッシュ化されたパスワードとユーザーが入力したpasswordを照合する関数
    if(password_verify($password, $result["password"])) {
        // パスワードが一致して認証されれば
        // メールアドレスを参照にDBからユーザーIDを取り出す
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
    } else {
        // 入力されたパスワードが正しくことないを示すコードをsessionに保存
        $_SESSION['error']     = 2;
        $_SESSION['errorCode'] = 2;
    
        $_SESSION["email"] = $_POST["email"];
        $_SESSION["password"] = $_POST["password"];
        header("Location: ../signin.php");
        exit;
    }

// データベースからエラーが返ってきたときの処理
} catch (PDOException $e) {
    $_SESSION['error']     = 3;
    $_SESSION['errorCode'] = 1;

    $_SESSION["email"] = $_POST["email"];
    $_SESSION["password"] = $_POST["password"];
    header("Location: ../signin.php");
    exit;
}

?>