<?php
session_start();
// ここには、共通の処理を書く

// PHPの関数
// function 関数名(引数1, 引数2, ...) { 処理 }

function getSessionValue($key) {
    // セッションに値が入っているか確認
    if (isset($_SESSION[$key])) {
        // セッションから値を取り出す
        $value = $_SESSION[$key];
        // セッションから値を削除する
        unset($_SESSION[$key]);
        // 取り出した値を返す
        return $value;
    }
    // セッションに値が入っていない場合はnullを返す
    return null;
}

// sessionにuserの値が入っているか調べる
// 入っていればログイン状態、入っていなければ非ログイン状態
function isAuth() {
    if (isset($_SESSION["loginId"]) && isset($_SESSION["loginEmail"])) {
        return true;
    }
    return false;
}

// ログイン状態かどうかを調べる関数
// ログインが必要な処理の前に呼び出され、ログイン状態でなければsignin.phpに飛ばす
function redirectIfUnauth() {
    if (!isAuth()) {
        $host = $_SERVER['HTTP_HOST'];
        $url = "http://{$host}/signin.php";
        header("Location: $url");
        exit;
    }
}

// 非ログイン状態かどうかを調べる関数
// signin.phpやsignup.php等にアクセスしたときに呼び出され、
// 既にログイン状態であればindex.phpに飛ばす
function redirectIfAuth() {
    if (isAuth()) {
        $host = $_SERVER['HTTP_HOST'];
        $url = "http://{$host}/index.php";
        header("Location: $url");
        exit;
    }
}

// sessionからデータを取り出しその値を判定し、エラーメッセージを作成する関数
// error => エラーの場所を示す
// errorCode => エラーの種類を表す

function getErrorMessage() {

    $emailError    = "";
    $passwordError = "";
    $databaseError  = "";
    $nameError = "";
    $contentError = "";

    // sessionにエラーの場所を示すコードと内容を示すコードが保存されているか確認
    if (isset($_SESSION['error']) && isset($_SESSION['errorCode'])) {
        // sessionからコード取り出し
        $error     = $_SESSION['error'];
        $errorCode = $_SESSION['errorCode'];
        // 取り出したコードからエラーの場所と内容を確認
        if (1 === $error) {
            if (1 === $errorCode) {
                $emailError = "メールアドレスが入力されていません";
            } elseif (2 === $errorCode) {
                $emailError = "メールアドレスが間違っています";
            } elseif (3 === $errorCode) {
                $emailError = "このメールアドレスは既に使用されています";
            } else {
                $emailError = "Email:エラーが発生しました";
            }
        } elseif (2 === $error) {
            if (1 === $errorCode) {
                $passwordError = "パスワードが入力されていません";
            } elseif (2 === $errorCode) {
                $passwordError = "パスワードが間違っています";
            } elseif (3 === $errorCode) {
                $passwordError = "パスワードは4~16文字の英数字でお願いします";
            } else {
                $passwordError = "Password:エラーが発生しました";
            }
        } elseif (3 === $error) {
            if (1 === $errorCode) {
                $databaseError = "データベースの接続に失敗しました";
            } elseif (2 === $errorCode) {
                $databaseError = "データベースへの保存に失敗しました";
            } else {
                $databaseError = "Database:エラーが発生しました";
            }
        } elseif (4 === $error) {
            if (1 === $errorCode) {
                $nameError = "氏名が入力されていません";
            } else {
                $nameError = "name:エラーが発生しました";
            }
        } elseif (5 === $error) {
            if (1 === $errorCode) {
                $contentError = "お問い合わせ内容が入力されていません";
            } else {
                $contentError = "content:エラーが発生しました";
            }
        }
        // セッションから削除
        unset($_SESSION["error"]);
        unset($_SESSION["errorCode"]);
    }

    return [
        "emailError"    => $emailError,
        "passwordError" => $passwordError,
        "databaseError"  => $databaseError,
        "nameError" => $nameError,
        "contentError" => $contentError,

    ];
}


// データベース接続
function connectDB() {
    global $dbh;
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    // 入力内容
    // new PDO("mysql:dbname=データベース名;host=ホスト名", "ユーザー名", "パスワード");

    //　サーバー環境用
    // $dbh = new PDO("mysql:dbname=ss441712_iw31ec;host=localhost", "ユーザー名", "パスワード");

    // ローカル環境用
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");

    // AWS環境
    $dbh = new PDO("mysql:dbname={$env['DB_NAME']};host={$env['DB_HOST']};charset=utf8mb4", $env["DB_USER"], $env["DB_PASS"]);
}
