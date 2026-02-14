<?php
// お問い合わせ　バックエンド
include "../functions/common.php";


session_start();
// エラーのフラグ関数　エラーがある場合はtrueを入れる
$isError = false;

// formから正しく送信されているかチェック

if (empty($_POST["name"])) {
    $isError = true;
    $_SESSION["error"] = 4;
    $_SESSION["errorCode"] = 1;
} elseif (empty($_POST["email"])) {
    $isError = true;
    $_SESSION["error"] = 1;
    $_SESSION["errorCode"] = 1;
} elseif (empty($_POST["content"])) {
    $isError = true;
    $_SESSION["error"] = 5;
    $_SESSION["errorCode"] = 1;
}

// フラグ関数でエラーの存在チェック
if ($isError) {
    // 入力されていた値をフォームに戻すためにsessionに保存
    $_SESSION["email"] = $_POST["email"];
    $_SESSION["name"] = $_POST["name"];
    $_SESSION["content"] = $_POST["content"];

    // お問い合わせフォームにリダイレクト
    header('Location: ../contact.php');
    exit;
}

// データベースに接続してフォーム内容を保存
try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    $sql = "INSERT INTO contacts (name, email, content) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($sql);
    $result = $stmt->execute([$_POST["name"], $_POST["email"], $_POST["content"]]);

    if (!$result) {
        $_SESSION["error"] = 3;
        $_SESSION["errorCode"] = 2;

        $_SESSION["email"] = $_POST["email"];
        $_SESSION["name"] = $_POST["name"];
        $_SESSION["content"] = $_POST["content"];

        header("Location: ../contact.php");
        exit;
    }

    header("Location: ../index.php");
    exit;
} catch (PDOException $e) {
    $_SESSION["error"] = 3;
    $_SESSION["errorCode"] = 1;

    $_SESSION["email"] = $_POST["email"];
    $_SESSION["name"] = $_POST["name"];
    $_SESSION["content"] = $_POST["content"];

    header("Location: ../contact.php");
    exit;
}

?>