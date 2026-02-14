<?php

// 共通で使用する関数を使用できるようにファイルを読み込む
include "./functions/common.php";

// ログイン状態を確認する関数
// ログイン済みであればindex.phpに飛ばす
redirectIfAuth();

$emailError    = "";
$passwordError = "";
$databaseError = "";

// エラーメッセージ作成関数実行
$errors = getErrorMessage();
$emailError    = $errors['emailError'];
$passwordError = $errors['passwordError'];
$databaseError  = $errors['databaseError'];

// エラー時に入力sされていた値をformに戻すためにsessionから取得
$email    = getSessionValue('email');
$password = getSessionValue('password');

// sessionのformのnullだったときエラー回避のための空文字代入
$email    = null === $email ? "" : $email;
$password = null === $password ? "" : $password;

// htmlでエラーメッセージ表示のためのフラグ変数
// sessionからエラーメッセージが取得できていればtrue
$hasEmailError    = empty($emailError) === false;
$hasPasswordError = empty($passwordError) === false;
$hasDatabaseError  = empty($databaseError) === false;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー作成</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
</head>
<body>
    <?php include "./components/header.php"; ?>

    <main class="main_form">
        <h2><span class="main_form_sign">アカウント作成</span></h2>
        <!-- データベースエラーメッセージ -->
        <p>
            <?php
                if ($hasDatabaseError) {
                    echo $databaseError;
                }
            ?>
        </p>
        <!-- emailとpasswordをregister.phpに送る -->
        <form action="./backend/register.php" method="post">
            <div>
                <label for="email">メールアドレス</label>
                <input type="email" name="email" id="email" value="<?php echo $email; ?>" placeholder="hal@example.com" required>
                <!-- メールアドレスエラーメッセージ -->
                <p>
                    <?php
                    if ($hasEmailError) {
                        echo $emailError;
                    }
                    ?>
                </p>
            </div>
            <div>
                <label for="password">パスワード</label>
                <input type="password" name="password" id="password"
                minlength="4" maxlength="16" pattern="[a-zA-Z0-9]+"
                value="<?php echo $password; ?>" placeholder="4~16字の英数字" required>
                <!-- パスワードエラーメッセージ -->
                <p>
                    <?php
                    if ($hasPasswordError) {
                        echo $passwordError;
                    }
                    ?>
                </p>
            </div>
            <button type="submit">アカウント作成</button>
        </form>
    </main>
    
</body>
</html>