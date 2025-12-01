<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$dataFile = __DIR__ . "/../data/products.json";
$uploadDir = __DIR__ . "/../uploads/";


            $sizes = array_filter(array_map('trim', explode(",", $_POST["sizes"] ?? "")));
            $colors = array_filter(array_map('trim', explode(",", $_POST["colors"] ?? "")));


$products = json_decode(file_get_contents($dataFile), true);
$id = $_GET["id"] ?? "";

foreach ($products as &$p) {
    if ($p["id"] === $id) {
        $found = $p;
        break;
    }
}

$CATEGORIES = [
    "custom" => "🎨 Personalisierte Produkte",
    "meme"   => "😂 Meme Shirts",
    "merch"  => "🍺 Regelmäßiger Merch"
];


if (!isset($found)) die("Produkt nicht gefunden!");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($products as &$p) {
        if ($p["id"] === $id) {

            // Name & Preis ändern
            $p["name"] = $_POST["name"];
            $p["price"] = floatval($_POST["price"]);
            $p["sizes"] = $sizes;
            $p["colors"] = $colors;
            $p[$i]["category"] = $_POST["category"];


            // Bild ersetzt?
            if (isset($_FILES["img"]) && $_FILES["img"]["error"] === 0) {

                $ext = strtolower(pathinfo($_FILES["img"]["name"], PATHINFO_EXTENSION));

                if (in_array($ext, ["jpg","jpeg","png","webp"])) {

                    $fileName = $id . "." . $ext;
                    $target = $uploadDir . $fileName;

                    move_uploaded_file($_FILES["img"]["tmp_name"], $target);

                    $p["img"] = "/test/uploads/" . $fileName;
                }
            }
        }
    }

    file_put_contents($dataFile, json_encode($products, JSON_PRETTY_PRINT));
    header("Location: panel.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Produkt bearbeiten</title>
<style>
body{font-family:Arial;background:#f0e4bb;padding:20px;}
.box{background:white;padding:20px;border-radius:12px;border:2px solid #caa748;max-width:500px;margin:auto;}
input{width:100%;padding:10px;margin-bottom:10px;border-radius:8px;border:2px solid #caa748;}
button{padding:12px;background:#a97b1e;color:white;border:none;border-radius:10px;width:100%;}
img{max-width:100%;border-radius:10px;margin-bottom:10px;}
</style>
</head>
<body>

<div class="box">
<h2>✏️ Produkt bearbeiten</h2>

<img src="<?php echo $found["img"]; ?>">

<form method="post" enctype="multipart/form-data">

    <input name="name" value="<?php echo $found["name"]; ?>" required>

    <input name="price" type="number" step="0.01" 
           value="<?php echo $found["price"]; ?>" required>

    <label>Größen (kommagetrennt):</label>
    <input name="sizes" 
           value="<?php echo isset($found["sizes"]) ? implode(',', $found["sizes"]) : ''; ?>">

    <label>Farben (kommagetrennt):</label>
    <input name="colors" 
           value="<?php echo isset($found["colors"]) ? implode(',', $found["colors"]) : ''; ?>">

    <label>Neues Bild hochladen (optional):</label>
    <input type="file" name="img" accept="image/*">

    <label>Kategorie:</label>
    <select name="category" required>
        <?php foreach($CATEGORIES as $key=>$label): ?>
            <option value="<?= $key ?>" <?= $product["category"]==$key?"selected":"" ?>>
                <?= $label ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <button>Speichern</button>
</form>


<p><a href="panel.php">← Zurück</a></p>
</div>

</body>
</html>
