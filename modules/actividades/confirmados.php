<?php

require_once '../../core/auth.php';
require_once '../../config/database.php';

if(!isset($_GET['actividad'])){
exit;
}

$actividad_id=intval($_GET['actividad']);

$stmt=$conn->prepare("
SELECT u.nombre
FROM actividad_participantes ap
JOIN usuarios u ON u.id=ap.id_usuario
WHERE ap.id_actividad=?
AND ap.participo=1
ORDER BY u.nombre
");

$stmt->bind_param("i",$actividad_id);
$stmt->execute();

$res=$stmt->get_result();

while($row=$res->fetch_assoc()){

echo "<div>👤 ".htmlspecialchars($row['nombre'])."</div>";

}