<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$FILE = __DIR__ . "/../data/news.json";
$UPLOAD = __DIR__ . "/../uploads/";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $link  = trim($_POST["link"]);
    $img = "/test/img/default-news.png";

    if ($_FILES["image"]["error"] === 0) {
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $name = "news_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $path = $UPLOAD . $name;
            move_uploaded_file($_FILES["image"]["tmp_name"], $path);
            $img = "/test/uploads/" . $name;
        }
    }

    $news = json_decode(file_get_contents($FILE), true) ?? [];
    $news[] = [
        "title" => $title,
        "image" => $img,
        "link"  => $link,
    ];

    file_put_contents($FILE, json_encode($news, JSON_PRETTY_PRINT));
    header("Location: panel.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>News hinzufügen</title>
<style>
body { font-family:Arial;background:#f0e4bb;padding:20px; }
.box { background:white;padding:20px;border-radius:12px;border:2px solid #caa748;max-width:500px;margin:auto; }
input { width:100%;padding:10px;margin-bottom:10px;border-radius:8px;border:2px solid #caa748; }
</style>
</head>
<body>

<div class="box">
<h2>➕ Neue News</h2>

<form method="post" enctype="multipart/form-data">
  <label>Titel:</label>
  <input type="text" name="title" required>

  <label>Link:</label>
  <input type="text" name="link" required>

  <label>Bild:</label>
  <input type="file" name="image" required>

  <button style="width:100%;padding:12px;background:#a97b1e;color:white;border:none;border-radius:10px;">
    Speichern
  </button>
</form>

<p><a href="panel.php">← Zurück</a></p>
</div>

</body>
</html>
