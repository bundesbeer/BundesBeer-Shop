<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$ordersDir = __DIR__ . "/../orders";

$file = $_GET["file"] ?? "";
$path = $ordersDir . "/" . $file;

if (!preg_match("/^[a-zA-Z0-9._-]+$/", $file)) die("Ungültiger Dateiname.");
if (!file_exists($path)) die("Datei nicht gefunden.");

$content = file_get_contents($path);
?>
<!doctype html>
<html>
<head>
<title>Bestellung ansehen – <?php echo htmlspecialchars($file); ?></title>
<meta charset="utf-8">
<style>
body{font-family:Arial;background:#f0e4bb;padding:20px;}
.box{background:white;padding:20px;border-radius:12px;border:2px solid #caa748;white-space:pre-wrap;}
a{color:#a97b1e;}
</style>
</head>
<body>

<h2>📄 Bestellung: <?php echo htmlspecialchars($file); ?></h2>
<p><a href="panel.php">← Zurück</a></p>

<div class="box">
<?php echo htmlspecialchars($content); ?>
</div>

</body>
</html>
