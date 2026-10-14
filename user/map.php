<?php
// ================= DB CONNECTION =================
$conn = new mysqli("localhost","root","","hospitals_db");
$conn->set_charset("utf8mb4");

if($conn->connect_error){
    die("DB Error");
}

// ================= START SESSION AND GET USER ID =================
session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// ================= LOG ACTIVITY FUNCTION =================
function logActivity($userId, $action, $description, $conn) {
    if (!$userId) return; // اذا ما في مستخدم مسجل دخول ما نسجلش
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $userId, $action, $description);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare statement: " . $conn->error);
    }
}

// ================= تسجيل زيارة المستخدم للصفحة =================
if ($userId) {
    logActivity($userId, 'visit_map_page', 'قام المستخدم بزيارة صفحة خريطة المستشفيات', $conn);
}

// ================= FETCH HOSPITALS =================
$data = [];
$result = $conn->query("SELECT * FROM hospitals");
while($row = $result->fetch_assoc()){
    $data[] = [
        "name" => $row["hospital_name"],
        "area" => $row["area"],
        "lat"  => (float)$row["latitude"],
        "lng"  => (float)$row["longitude"],
        "description" => $row["description"]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Nearby Hospitals GPS</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}
body{background:#f4f7f6;padding:20px;direction:rtl;text-align:right}
h2{text-align:center;margin-bottom:15px;color:#2c3e50}
.map-section{width:100%;height:450px;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.1);margin-bottom:20px;border:2px solid #fff}
#map{width:100%;height:100%}
.slider-container{background:#fff;border-radius:12px;padding:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.slider-wrapper{display:flex;gap:15px;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:10px}
.slider-wrapper::-webkit-scrollbar{display:none}
.hospital-card{
    min-width:260px;background:#fff;border-radius:14px;padding:16px;
    border:1px solid #e9e9e9;box-shadow:0 10px 18px rgba(0,0,0,.06);
    scroll-snap-align:start;cursor:pointer;position:relative;transition:.3s
}
.hospital-card:hover{transform:translateY(-5px);border-color:#e74c3c}
.hospital-card::before{content:"🏥";position:absolute;top:12px;left:14px;font-size:1.6rem;opacity:.2}
.hospital-card h4{margin-bottom:6px;color:#1e2a38;font-size:1.1rem}
.hospital-card p{font-size:.85rem;color:#7a8b9a;margin-bottom:4px}
.distance{margin-top:6px;font-weight:600;color:#27ae60}
.notice{color:#999;font-size:.9rem;margin-bottom:10px}
.custom-hospital-marker{background:transparent;border:none}
</style>
</head>

<body>

<h2>📍 Available Hospitals Map</h2>
<p class="notice" id="status">⏳ Determining your location...</p>

<div class="map-section"><div id="map"></div></div>

<div class="slider-container">
    <div class="slider-wrapper" id="cards"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ================= DATA FROM PHP =================
const HOSPITALS = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;

// ================= ICON =================
const hospitalIcon = L.divIcon({
    className:'custom-hospital-marker',
    html:`<div style="
        background:#fff;border:2px solid #e74c3c;border-radius:50%;
        width:40px;height:40px;display:flex;align-items:center;
        justify-content:center;font-size:22px;
        box-shadow:0 4px 8px rgba(0,0,0,.3)">🏥</div>`,
    iconSize:[40,40],iconAnchor:[20,40],popupAnchor:[0,-45]
});

// ================= DISTANCE =================
function calcDistance(lat1, lon1, lat2, lon2){
    const R=6371;
    const dLat=(lat2-lat1)*Math.PI/180;
    const dLon=(lon2-lon1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2+
        Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*
        Math.sin(dLon/2)**2;
    return R*(2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)));
}

// ================= MAP =================
const map=L.map("map").setView([15.63,32.51],12);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
let markersLayer=L.layerGroup().addTo(map);

// ================= GPS =================
navigator.geolocation.getCurrentPosition(pos=>{
    document.getElementById("status").textContent="✅ Location determined";
    const uLat=pos.coords.latitude;
    const uLng=pos.coords.longitude;

    L.circle([uLat,uLng],{radius:3000,color:"#3498db",fillOpacity:.1}).addTo(map);
    L.marker([uLat,uLng]).addTo(map).bindPopup("📍 موقعك الحالي");
    map.setView([uLat,uLng],12);

    const results = HOSPITALS.map(h=>({...h,dist:calcDistance(uLat,uLng,h.lat,h.lng)}))
                             .sort((a,b)=>a.dist-b.dist);
    render(results,false);
},()=>{
    document.getElementById("status").textContent="⚠️ Location unavailable";
    render(HOSPITALS,true);
});

// ================= RENDER =================
function render(list,isVirtual){
    const cards=document.getElementById("cards");
    cards.innerHTML="";
    markersLayer.clearLayers();

    list.forEach(h=>{
        const m=L.marker([h.lat,h.lng],{icon:hospitalIcon})
            .addTo(markersLayer)
            .bindPopup(`<b>${h.name}</b><br>${h.area}`);

        const c=document.createElement("div");
        c.className="hospital-card";
        c.innerHTML=`
            <h4>${h.name}</h4>
            <p>${h.area}</p>
            <p>${h.description || ""}</p>
            ${!isVirtual && h.dist ? `<div class="distance">📏 ${h.dist.toFixed(2)} km</div>` : ""}
        `;
        c.onclick=()=>{map.setView([h.lat,h.lng],15);m.openPopup();}
        cards.appendChild(c);
    });
}
</script>

</body>
</html>