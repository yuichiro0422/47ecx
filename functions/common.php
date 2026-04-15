<?php
// 共通関数をまとめたファイル

session_start();


// セッションに保存されている値を取得し、同時に削除する関数
// 指定したキーの値が存在すれば取得して削除し、なければnullを返す
// 主にエラーメッセージなど「一度だけ表示したいデータ」の取得に使用する
function getSessionValue($key) {
    // セッションに値が入っているか確認
    if (isset($_SESSION[$key])) {

        // 値が入って入れば取り出してセッションから削除
        $value = $_SESSION[$key]; // 値を取り出す
        unset($_SESSION[$key]); // セッションから削除

        // 取り出した値を返す
        return $value;
    }
    // セッションに値が入っていない場合はnullを返す
    return null;
}


// セッション情報をもとにログイン状態かどうかを判定する関数
// loginIdとloginEmailの両方がセッションに保存されていればログイン状態とみなす
function isAuth() {
    if (isset($_SESSION["loginId"]) && isset($_SESSION["loginEmail"])) {
        return true;
    }
    return false;
}


// ログイン状態でない場合にsignin.php(ログインページ)にリダイレクトする関数
// 認証が必要なページの先頭で呼び出し、未ログインユーザーのアクセスを制限する
function redirectIfUnauth() {
    if (!isAuth()) {

        //　現在アクセスしているURLのホスト部分を取得
        $host = $_SERVER['HTTP_HOST'];

        // signin.phpにリダイレクトするURLを作成
        $url = "http://{$host}/signin.php";

        header("Location: $url"); // リダイレクト
        exit;
    }
}


// redirectIfUnauth()の対義関数で、ログイン状態の場合にsignin.php(ログインページ)にリダイレクトする関数
// ログインページなどで呼び出し、既にログインしているユーザーのアクセスを制限する
function redirectIfAuth() {
    if (isAuth()) {
        $host = $_SERVER['HTTP_HOST'];
        $url = "http://{$host}/index.php";
        header("Location: $url");
        exit;
    }
}


// セッションに保存されたエラーコードをもとに、表示用のエラーメッセージを返す関数
// errorとerrorCodeの値からエラー内容を判定して、対応するエラーメッセージを返す
// error => エラーの場所を示す
// errorCode => エラーの種類を表す
function getErrorMessage() {

    // エラーメッセージを格納する変数を初期化
    $emailError    = "";
    $passwordError = "";
    $databaseError  = "";
    $nameError = "";
    $contentError = "";

    // セッションにエラーコードが保存されているか確認
    if (isset($_SESSION['error']) && isset($_SESSION['errorCode'])) {

        // セッションからエラーコードを取り出す
        $error     = $_SESSION['error'];
        $errorCode = $_SESSION['errorCode'];

        // 取り出したコードからエラーの場所と内容を判定して、対応するエラーメッセージを変数に格納
        // errorが1のときはメールアドレスのエラー
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

        // errorが2のときはパスワードのエラー
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

        // errorが3のときはデータベースのエラー
        } elseif (3 === $error) {
            if (1 === $errorCode) {
                $databaseError = "データベースの接続に失敗しました";
            } elseif (2 === $errorCode) {
                $databaseError = "データベースへの保存に失敗しました";
            } else {
                $databaseError = "Database:エラーが発生しました";
            }

        // errorが4のときは氏名のエラー
        } elseif (4 === $error) {
            if (1 === $errorCode) {
                $nameError = "氏名が入力されていません";
            } else {
                $nameError = "name:エラーが発生しました";
            }

        // errorが5のときはお問い合わせ内容のエラー
        } elseif (5 === $error) {
            if (1 === $errorCode) {
                $contentError = "お問い合わせ内容が入力されていません";
            } else {
                $contentError = "content:エラーが発生しました";
            }
        }

        // エラーメッセージを取得した後は、セッションからエラーコードを削除しておく
        unset($_SESSION["error"]);
        unset($_SESSION["errorCode"]);
    }

    // エラーメッセージは複数ある可能性があるため、配列にまとめて返す
    return [
        "emailError"    => $emailError,
        "passwordError" => $passwordError,
        "databaseError"  => $databaseError,
        "nameError" => $nameError,
        "contentError" => $contentError,

    ];
}


// データベースに接続する関数
// データベース接続に必要な情報は.envファイルに記載しておき、parse_ini_file関数で読み込む
// データベース接続に成功したら、PDOオブジェクトをグローバル変数$dbhに格納する
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
