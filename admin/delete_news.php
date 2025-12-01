<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$FILE   = __DIR__ . "/../data/news.json";

if (!isset($_GET["id"])) { header("Location: panel.php"); exit; }

$id = intval($_GET["id"]);
$news = json_decode(file_get_contents($FILE), true) ?? [];

if (isset($news[$id])) {

    // Bild löschen (falls in uploads)
    if (strpos($news[$id]["image"], "/test/uploads/") === 0) {

        $path = __DIR__ . "/.." . $news[$id]["image"];
        if (file_exists($path)) unlink($path);
    }

    array_splice($news, $id, 1);
    file_put_contents($FILE, json_encode($news, JSON_PRETTY_PRINT));
}

header("Location: panel.php");
exit;
