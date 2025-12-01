<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$ordersDir = __DIR__ . "/../orders";
$file = $_GET["file"] ?? "";
$path = $ordersDir . "/" . $file;

if (!preg_match("/^[a-zA-Z0-9._-]+$/", $file)) die("Ungültiger Dateiname.");
if (!file_exists($path)) die("Datei nicht gefunden.");

unlink($path);

header("Location: panel.php?deleted=1");
exit;
