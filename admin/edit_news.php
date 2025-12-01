<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$newsFile = __DIR__ . "/../data/news.json";
$uploadDir = __DIR__ . "/../uploads/";
$news = json_decode(file_get_contents($newsFile), true);

$id = intval($_GET["id"]);
$item = $news[$id];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"]);
    $link  = trim($_POST["link"]);
    $imgPath = $item["image"];

    // Neues Bild?
    if ($_FILES["image"]["error"] === 0) {
        // altes löschen
        if (file_exists(__DIR__ . "/.." . $imgPath)) {
            unlink(__DIR__ . "/.." . $imgPath);
        }

        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $fileName = "news_" . time() . "." . $ext;
        $target = $uploadDir . $fileName;

        move_uploaded_file($_FILES["image"]["tmp_name"], $target);
        $imgPath = "/test/uploads/" . $fileName;
    }

    $news[$id] = [
        "title" => $title,
        "image" => $imgPath,
        "link" => $link
    ];

    file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT));

    header("Location: panel.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>News bearbeiten</title>
<style>
body{font-family:Arial;background:#f0e4bb;padding:20px;}
.box{background:white;padding:20px;border-radius:12px;border:2px solid #caa748;max-width:500px;margin:auto;}
input{width:100%;padding:10px;margin-bottom:10px;border-radius:8px;border:2px solid #caa748;}
button{padding:12px;background:#a97b1e;color:white;border:none;border-radius:10px;width:100%;}
</style>
</head>
<body>

<div class="box">
<h2>✏️ News bearbeiten</h2>

<form method="post" enctype="multipart/form-data">

    <label>Titel:</label>
    <input name="title" value="<?= htmlspecialchars($item["title"]) ?>" required>

    <label>Link:</label>
    <input name="link" value="<?= htmlspecialchars($item["link"]) ?>" required>

    <label>Aktuelles Bild:</label><br>
    <img src="<?= $item["image"] ?>" style="width:100%;border-radius:10px;"><br><br>

    <label>Neues Bild (optional):</label>
    <input type="file" name="image">

    <button>Änderungen speichern</button>
</form>

<p><a href="panel.php">← Zurück</a></p>
</div>

</body>
</html>
