<?php
include "./functions/common.php";

$nameError  = "";
$emailError = "";
$content    = "";
$databaseError  = "";

$errors = getErrorMessage();
$nameError  = $errors["nameError"];
$emailError = $errors["emailError"];
$contentError   = $errors["contentError"];
$databaseError  = $errors["databaseError"];

$name       = getSessionValue("name");
$email      = getSessionValue("email");
$content    = getSessionValue("content");

$name       = null === $name ? "" : $name;
$email      = null === $email ? "" : $email;
$content    = null === $content ? "" : $content;

$hasNameError    = empty($nameError) === false;
$hasEmailError    = empty($emailError) === false;
$hasContentError    = empty($contentError) === false;
$hasDatabaseError    = empty($databaseError) === false;

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お問い合わせ</title>
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

    <?php include "./components/header.php"; ?>

    <main class="demo_form">
        <h2>お問い合わせ</h2>
        <!-- データベースエラーメッセージ -->
        <p>
            <?php
                if ($hasDatabaseError) {
                    echo $databaseError;
                }
            ?>
        </p>
        <form action="./backend/contact.php" method="POST">
            <div>
                <label for="name">氏名</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
                <!-- メールアドレスエラーメッセージ -->
                <p>
                    <?php
                    if ($hasNameError) {
                        echo $nameError;
                    }
                    ?>
                </p>
            </div>
            <div>
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
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
                <label for="content">お問い合わせ内容</label>
                <textarea id="content" name="content" rows="4" value="<?php echo $content; ?>" required></textarea>
                <!-- メールアドレスエラーメッセージ -->
                <p>
                    <?php
                    if ($hasContentError) {
                        echo $contentError;
                    }
                    ?>
                </p>
            </div>
            <button type="submit">送信する</button>
        </form>
    </main>
    
    <?php include "./components/footer.php"; ?>

</body>
</html>