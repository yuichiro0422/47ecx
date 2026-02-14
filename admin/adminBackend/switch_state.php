<?php
// 販売状態変更
include "../../functions/common.php";


// リダイレクトするURL(クエリパラメータ―保持)
$url = $_POST["currentUrl"];

if (empty($_POST["id"]) || empty($_POST["action"])) {
    header("Location: " . $url);
    exit;
}

$id = $_POST["id"];
$action = $_POST["action"];

try {
    connectDB();
    // $dbh = new PDO("mysql:dbname=iw31_ec;host=localhost", "root", "");
    
    // start = 販売開始
    if ($action == "start") {
        $sql = "UPDATE products SET state = 'sale' WHERE id = ?";
    // delete = 販売停止
    } elseif ($action == "delete") {
        $sql = "UPDATE products SET state = 'stop' WHERE id = ?";
    } else {
        header("Location: " . $url);
        exit;
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);

    echo $url;

    header("Location: " . $url);
    exit;
}
catch (PDOException $e) {
    header("Location: " . $url);
    exit;
}

?>