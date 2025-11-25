<?php
/* =============================================================
   Bundesbeer – Merch Shop (Bundesbeer Bundles)
   Komplett in 1 Datei (HTML + CSS + PHP + JS)
   ============================================================= */

/* -------------------------------
   KONFIGURATION (ÄNDERN!)
   ------------------------------- */

// PayPal Friends Adresse
$PAYPAL_ADDRESS = "https://www.paypal.com/paypalme/calyndris";

// Admin-E-Mail (Benachrichtigung)
$ADMIN_EMAIL = "bundesbeer.dc@gmail.com";

// Bestellordner
$ORDERS_DIR = __DIR__ . "/orders";


// -------------------------------
// SERVER-SEITIGER PRODUKTKATALOG
// -------------------------------
$CATALOG = [

  "beerli-bundle1" => [
    "name" => "Bierli Shirt + Kappe (Bundle)",
    "price" => 27.00,
    "type"  => "simple"
  ],

  "beerli-bundle2" => [
    "name" => "Bierli Hoodie + Shirt + Kappe (Bundle)",
    "price" => 52.00,
    "type"  => "simple"
  ],

  "beerli-bundle3" => [
    "name" => "Bierli Jogginghose + Hoodie + Shirt + Kappe (Bundle)",
    "price" => 72.00,
    "type"  => "simple"
  ],
];


/* -------------------------------
   BESTELLVERARBEITUNG
   ------------------------------- */

$successMsg = $errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["checkout_flag"])) {

    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $discord = trim($_POST["discord"] ?? "");
    $cartRaw = $_POST["cart"] ?? "[]";

    if ($name==="" || $email==="" || $discord==="" || $address==="") {
        $errorMsg = "Bitte alle Pflichtfelder ausfüllen.";
    } else {

        $items = json_decode($cartRaw, true);
        if (!is_array($items) || count($items)==0) {
            $errorMsg = "Dein Warenkorb ist leer.";
        } else {

            $lines = [];
            $subtotal = 0;

            foreach ($items as $it) {

                $id  = $it["id"] ?? "";
                $qty = intval($it["qty"] ?? 1);

                if (!isset($CATALOG[$id])) {
                    $errorMsg = "Ungültiger Artikel entdeckt.";
                    break;
                }

                $prod = $CATALOG[$id];
                $nameFull = $prod["name"];
                $price = $prod["price"];
                $sum = $qty * $price;
                $subtotal += $sum;

                $lines[] = "{$qty}x {$nameFull} — " . number_format($sum,2) . " €";
            }

            if ($errorMsg === "") {

                if (!is_dir($ORDERS_DIR)) mkdir($ORDERS_DIR);

                $orderId = "BB" . date("YmdHis") . rand(100,999);
                $file = $ORDERS_DIR . "/order_{$orderId}.txt";

                $content = [];
                $content[] = "=== Bundesbeer Bestellung ===";
                $content[] = "Bestell-ID: $orderId";
                $content[] = "Zeit: " . date("Y-m-d H:i:s");
                $content[] = "";
                $content[] = "Kunde: $name";
                $content[] = "E-Mail: $email";
                $content[] = "Discord: $discord";
                $content[] = "Adresse:";
                $content[] = $address;
                $content[] = "";
                $content[] = "Produkte:";
                foreach ($lines as $l) $content[] = $l;
                $content[] = "";
                $content[] = "Summe: " . number_format($subtotal,2) . " €";
                $content[] = "";
                $content[] = "Zahlung per PayPal Friends an:";
                $content[] = $PAYPAL_ADDRESS;
                $content[] = "";

                file_put_contents($file, implode("\n", $content));

                // Admin-Mail schicken
                @mail(
                    $ADMIN_EMAIL,
                    "Neue Bestellung (Bezahlt) – $orderId",
                    implode("\n", $content),
                    "Content-Type: text/plain; charset=UTF-8"
                );

                $successMsg = "Danke! Deine Bestellung ($orderId) wurde gespeichert. Wir melden uns.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Bundesbeer – Merch Bundles</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
body {
    margin:0;
    font-family:Arial, sans-serif;
    background:#FAE7A5;
    color:#2b1d05;
}
header {
    background:#F2C14E;
    padding:15px;
    border-bottom:3px solid #d29e17;
}
.container { max-width:1100px; margin:auto; padding:15px; }
.logo { font-size:32px; }
.card {
    background:white;
    border-radius:14px;
    padding:15px;
    border:2px solid #ebca75;
}
.btn {
    padding:10px 14px;
    border:none;
    border-radius:12px;
    cursor:pointer;
}
.btn-primary { background:#d29e17; color:white; }
.btn-dark { background:#2b1d05; color:white; }
.btn-danger { background:#c0392b; color:white; }
.product-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:15px; }
.field { width:100%; padding:10px; border:2px solid #ebca75; border-radius:10px; margin-bottom:10px; }
.drawer {
    position:fixed; inset:0; background:rgba(0,0,0,.6);
    display:none; justify-content:flex-end;
}
.drawer.open { display:flex; }
.drawer-panel {
    background:white; width:420px; height:100%; padding:20px; overflow:auto;
}
</style>
</head>
<body>
<header>
  <div class="container">
    <span class="logo">🍺</span>
    <strong style="font-size:20px">Bundesbeer Merch – Bierli Bundles</strong>

    <button class="btn btn-dark" style="float:right" id="openCart">
      🧺 Warenkorb <span id="cartCount">0</span>
    </button>
  </div>
</header>

<div class="container">

<h2>Bierli Bundle Angebote</h2>
<p>Hochwertige Bundesbeer-Merch Sets zu Fixpreisen.</p>

<div class="product-grid" id="shop"></div>

</div>


<!-- WARENKORB / CHECKOUT -->
<div class="drawer" id="drawer">
  <div class="drawer-panel">

      <h2>🧺 Dein Warenkorb</h2>
      <div id="cartList"></div>

      <hr>

      <h3>Lieferdaten</h3>

      <form method="post" id="checkoutForm">
        <input type="hidden" name="checkout_flag" value="1">
        <input type="hidden" name="cart" id="cartPayload">

        <input class="field" required name="name" placeholder="Vollständiger Name *">
        <input class="field" required name="email" type="email" placeholder="E-Mail *">
        <input class="field" required name="discord" placeholder="Discord (z.B. @name) *">
        <textarea class="field" required name="address" placeholder="Adresse (Straße, PLZ Ort, Land) *"></textarea>

        <div class="card" style="background:#fff8d2">
          <strong>Zahlung per PayPal Friends:</strong><br>
          Sende den Gesamtbetrag an:<br>
          <strong><?php echo $PAYPAL_ADDRESS; ?></strong><br><br>
          Danach auf „Ich habe überwiesen“ klicken.
        </div>

        <button class="btn btn-primary" style="width:100%; margin-top:10px">
          Ich habe überwiesen ✔
        </button>

      </form>

      <button class="btn btn-danger" style="width:100%; margin-top:10px" id="closeCartBtn">Schließen</button>

  </div>
</div>
<script>
// ===================
// PRODUKTKATALOG (Client)
// ===================
const PRODUCTS = [

  { id:"beerli-bundle1", name:"Bierli Shirt + Kappe (Bundle)", price:27.00,
    img:"https://via.placeholder.com/400x250?text=Bundle+1" },

  { id:"beerli-bundle2", name:"Bierli Hoodie + Shirt + Kappe (Bundle)", price:52.00,
    img:"https://via.placeholder.com/400x250?text=Bundle+2" },

  { id:"beerli-bundle3", name:"Bierli Jogginghose + Hoodie + Shirt + Kappe (Bundle)", price:72.00,
    img:"https://via.placeholder.com/400x250?text=Bundle+3" },

];

let cart = JSON.parse(localStorage.getItem("bb_cart") || "[]");

function saveCart(){
  localStorage.setItem("bb_cart", JSON.stringify(cart));
  renderCart();
}

function money(n){ return n.toFixed(2)+" €"; }


// ===================
// SHOP-ANSICHT
// ===================
const shop = document.getElementById("shop");
PRODUCTS.forEach(p=>{
  const c = document.createElement("div");
  c.className = "card";
  c.innerHTML = `
    <img src="${p.img}" style="width:100%;border-radius:10px">
    <h3>${p.name}</h3>
    <p><strong>${money(p.price)}</strong></p>
    <button class="btn btn-dark" data-id="${p.id}">➕ In den Warenkorb</button>
  `;
  shop.appendChild(c);
});

shop.addEventListener("click", e=>{
  if (!e.target.dataset.id) return;
  const id = e.target.dataset.id;
  const prod = PRODUCTS.find(p=>p.id===id);
  const found = cart.find(i=>i.id===id);
  if (found) found.qty++;
  else cart.push({id:prod.id, name:prod.name, price:prod.price, qty:1});
  saveCart();
  openDrawer();
});


// ===================
// WARENKORB
// ===================
const drawer = document.getElementById("drawer");
const cartList = document.getElementById("cartList");
const cartCount = document.getElementById("cartCount");

function openDrawer(){ drawer.classList.add("open"); }
function closeDrawer(){ drawer.classList.remove("open"); }

document.getElementById("openCart").onclick = openDrawer;
document.getElementById("closeCartBtn").onclick = closeDrawer;

function renderCart(){
  cartCount.textContent = cart.reduce((a,b)=>a+b.qty,0);

  if (cart.length===0){
    cartList.innerHTML = "<p>Warenkorb ist leer.</p>";
    return;
  }

  cartList.innerHTML = cart.map((it,i)=>`
    <div class="card" style="margin-bottom:10px">
      <strong>${it.name}</strong><br>
      ${money(it.price)} / Stück<br>
      Menge: 
      <button onclick="changeQty(${i},-1)">–</button>
      ${it.qty}
      <button onclick="changeQty(${i},1)">+</button>
    </div>
  `).join("");
}

function changeQty(i,delta){
  cart[i].qty += delta;
  if (cart[i].qty<=0) cart.splice(i,1);
  saveCart();
}


// ===================
// CHECKOUT
// ===================
document.getElementById("checkoutForm").addEventListener("submit", ()=>{
  document.getElementById("cartPayload").value = JSON.stringify(cart);
  cart = [];
  saveCart();
  closeDrawer();
});

renderCart();
</script>


<?php if ($successMsg): ?>
<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);
background:#2ecc71;color:white;padding:10px 20px;border-radius:10px;">
  <?php echo $successMsg; ?>
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);
background:#e74c3c;color:white;padding:10px 20px;border-radius:10px;">
  <?php echo $errorMsg; ?>
</div>
<?php endif; ?>

</body>
</html>
