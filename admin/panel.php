<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }


$ordersDir = __DIR__ . "/../orders";
$files = array_reverse(glob($ordersDir."/*.txt"));

$dataFile = __DIR__ . "/../data/products.json";
$products = json_decode(file_get_contents($dataFile), true);
?>
<!doctype html>
<html>
<head>
<title>Admin Panel – Bundesbeer</title>
<meta charset="utf-8">
<style>
body{font-family:Arial;background:#f0e4bb;margin:0;padding:20px;}
.table{background:white;border-radius:12px;padding:20px;border:2px solid #caa748;}
a{color:#a97b1e;text-decoration:none;}
.logout{float:right;}
</style>
</head>
<body>

<a class="logout" href="logout.php">Logout</a>


<h1>🧺 Bundesbeer Admin Panel</h1>

<div class="table">
<h2>📦 Bestellungen</h2>

<?php if(empty($files)): ?>
<p>Keine Bestellungen vorhanden.</p>
<?php else: ?>

<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%;">
<tr>
  <th>Datei</th>
  <th>Datum</th>
  <th>Aktion</th>
  <th>Löschen</th>
</tr>

<?php foreach($files as $f): 
    $base = basename($f);
    $time = date("d.m.Y H:i", filemtime($f));
?>
<tr>
  <td><?php echo $base; ?></td>
  <td><?php echo $time; ?></td>
  <td><a href="view.php?file=<?php echo urlencode($base); ?>">Ansehen</a></td>
  <td>
    <a style="color:red" href="delete.php?file=<?php echo urlencode($base); ?>"
       onclick="return confirm('Diese Bestellung wirklich löschen?');">
       ❌ Löschen
    </a>
  </td>
</tr>
<?php endforeach; ?>

</table>

<?php endif; ?>
</div>

<div class="table">

<h2>📦 Produkte verwalten</h2>
<p><a href="add_product.php">➕ Neues Produkt</a></p>

<?php
// Kategorien gruppieren
$groups = [
    "custom" => [],
    "meme"   => [],
    "merch"  => [],
];

foreach ($products as $p) {
    $cat = $p["category"] ?? "merch"; // fallback falls nicht gesetzt
    if (!isset($groups[$cat])) $groups[$cat] = [];
    $groups[$cat][] = $p;
}

// Kategorie-Namen
$catTitles = [
    "custom" => "🎨 Personalisierte Produkte",
    "meme"   => "😂 Meme Shirts",
    "merch"  => "🍺 Regelmäßiger Merch",
];
?>

<?php foreach ($groups as $catKey => $items): ?>
    <h3 style="margin-top:30px;"><?php echo $catTitles[$catKey]; ?></h3>

    <?php if (empty($items)): ?>
        <p><i>Keine Produkte in dieser Kategorie.</i></p>
    <?php else: ?>

    <table border="1" cellpadding="8" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
    <tr>
        <th>Kategorie</th>
        <th>Name</th>
        <th>Preis</th>
        <th>Bild</th>
        <th>Größen</th>
        <th>Farben</th>
        <th>Bearbeiten</th>
        <th>Löschen</th>
    </tr>

    <?php foreach($items as $p): ?>
    <tr>
      <td>[<?php echo strtoupper($p["category"] ?? "MERCH"); ?>]</td>
      <td><?php echo $p["name"]; ?></td>
      <td><?php echo number_format($p["price"],2); ?> €</td>
      <td><img src="<?php echo $p["img"]; ?>" style="width:60px;border-radius:8px;"></td>
      <td><?php echo implode(", ", $p["sizes"] ?? []); ?></td>
      <td><?php echo implode(", ", $p["colors"] ?? []); ?></td>
      <td><a href="edit_product.php?id=<?php echo $p["id"]; ?>">✏️ Bearbeiten</a></td>
      <td>
        <a style="color:red;" 
           href="delete_product.php?id=<?php echo $p["id"]; ?>" 
           onclick="return confirm('Produkt wirklich löschen?');">
           🗑️ Löschen
        </a>
      </td>
    </tr>
    <?php endforeach; ?>

    </table>

    <?php endif; ?>

<?php endforeach; ?>





</table>
</div>

<?php
$newsFile = __DIR__ . "/../data/news.json";
$news = json_decode(file_get_contents($newsFile), true) ?? [];
?>
<div class="table">

<h2>📰 News verwalten</h2>
<p><a href="add_news.php">➕ Neue News</a></p>

<table border="1" cellpadding="8" style="width:100%;border-collapse:collapse;">
<tr>
  <th>Titel</th>
  <th>Bild</th>
  <th>Link</th>
  <th>Bearbeiten</th>
  <th>Löschen</th>
</tr>

<?php foreach($news as $i=>$n): ?>
<tr>
  <td><?php echo htmlspecialchars($n["title"]); ?></td>

  <td>
    <img src="<?php echo $n["image"]; ?>" style="width:80px;border-radius:8px;">
  </td>

  <td><?php echo htmlspecialchars($n["link"]); ?></td>

  <!-- ⭐ NEU: BEARBEITEN BUTTON -->
  <td>
    <a href="edit_news.php?id=<?php echo $i; ?>">
        ✏️ Bearbeiten
    </a>
  </td>

  <td>
    <a style="color:red;" 
       href="delete_news.php?id=<?php echo $i; ?>" 
       onclick="return confirm('News wirklich löschen?');">
       🗑️ Löschen
    </a>
  </td>
</tr>
<?php endforeach; ?>

</table>

</div>

</body>
</html>



