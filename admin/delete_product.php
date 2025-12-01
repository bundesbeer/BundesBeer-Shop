<?php
require "config.php";

if (!($_SESSION["logged_in"] ?? false)) {
    header("Location: login.php");
    exit;
}

$dataFile = __DIR__ . "/../data/products.json";
$uploadDir = __DIR__ . "/../uploads/";

$id = $_GET["id"] ?? "";

if ($id === "") {
    die("Kein Produkt angegeben.");
}

// Produkte laden
$products = json_decode(file_get_contents($dataFile), true);

// Produkt suchen
$index = array_search($id, array_column($products, "id"));

if ($index === false) {
    die("Produkt nicht gefunden.");
}

// Bildpfad holen
$imgPath = $products[$index]["img"] ?? "";

// Produkt löschen
unset($products[$index]);

// JSON speichern
file_put_contents($dataFile, json_encode(array_values($products), JSON_PRETTY_PRINT));

/* --------------------------
   BILD AUS ORDNER LÖSCHEN
--------------------------- */

// nur löschen, wenn:
// - Bild existiert
// - im uploads Ordner liegt
// - nicht default.png ist
if ($imgPath !== "" && 
    strpos($imgPath, "/test/uploads/") === 0 &&
    basename($imgPath) !== "default.png") {

    $fullPath = $uploadDir . basename($imgPath);

    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

header("Location: panel.php?deleted=1");
exit;
