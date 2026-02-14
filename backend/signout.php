<?php
// ログアウト処理

include "../functions/common.php";
redirectIfUnAuth();

if (isset($_SESSION["loginId"])) {
    unset($_SESSION["loginId"]);
    unset($_SESSION["loginEmail"]);
    header("Location: ../index.php");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
?>