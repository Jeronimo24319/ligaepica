<?php
require_once '../../core/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$grupo_activo = $_SESSION['grupo_activo'] ?? 0;
$usuario_id   = $_SESSION['id'] ?? 0;

$total = 0;
$ultimo = null;

if ($grupo_activo && $usuario_id) {

    /* MENSAJES GRUPALES NO LEÍDOS */

    $stmtGrupal = $conn->prepare("
        SELECT COUNT(am.id)
        FROM actividad_mensajes am
        LEFT JOIN actividad_mensajes_leidos aml
        ON aml.mensaje_id = am.id
        AND aml.usuario_id = ?
        WHERE am.grupo_id = ?
        AND am.id_usuario != ?
        AND aml.id IS NULL
    ");

    $stmtGrupal->bind_param("iii",$usuario_id,$grupo_activo,$usuario_id);
    $stmtGrupal->execute();

    $noLeidosGrupal = $stmtGrupal->get_result()->fetch_row()[0] ?? 0;


    /* MENSAJES PRIVADOS NO LEÍDOS */

    $stmtPrivado = $conn->prepare("
        SELECT COUNT(mp.id)
        FROM mensajes_privados mp
        JOIN conversaciones_privadas c ON mp.conversacion_id = c.id
        LEFT JOIN mensajes_privados_leidos ml
        ON ml.mensaje_id = mp.id
        AND ml.usuario_id = ?
        WHERE c.grupo_id = ?
        AND mp.remitente_id != ?
        AND ml.id IS NULL
    ");

    $stmtPrivado->bind_param("iii",$usuario_id,$grupo_activo,$usuario_id);
    $stmtPrivado->execute();

    $noLeidosPrivado = $stmtPrivado->get_result()->fetch_row()[0] ?? 0;

    $total = $noLeidosGrupal + $noLeidosPrivado;


    /* ÚLTIMO MENSAJE GRUPAL */

    $stmtUltimoGrupal = $conn->prepare("
        SELECT am.id,u.nombre,am.mensaje
        FROM actividad_mensajes am
        JOIN usuarios u ON u.id = am.id_usuario
        LEFT JOIN actividad_mensajes_leidos aml
        ON aml.mensaje_id = am.id
        AND aml.usuario_id = ?
        WHERE am.grupo_id = ?
        AND am.id_usuario != ?
        AND aml.id IS NULL
        ORDER BY am.id DESC
        LIMIT 1
    ");

    $stmtUltimoGrupal->bind_param("iii",$usuario_id,$grupo_activo,$usuario_id);
    $stmtUltimoGrupal->execute();

    $resGrupal = $stmtUltimoGrupal->get_result()->fetch_assoc();


    /* ÚLTIMO MENSAJE PRIVADO */

    $stmtUltimoPrivado = $conn->prepare("
        SELECT mp.id,u.nombre,mp.mensaje
        FROM mensajes_privados mp
        JOIN conversaciones_privadas c ON mp.conversacion_id = c.id
        JOIN usuarios u ON u.id = mp.remitente_id
        LEFT JOIN mensajes_privados_leidos ml
        ON ml.mensaje_id = mp.id
        AND ml.usuario_id = ?
        WHERE c.grupo_id = ?
        AND mp.remitente_id != ?
        AND ml.id IS NULL
        ORDER BY mp.id DESC
        LIMIT 1
    ");

    $stmtUltimoPrivado->bind_param("iii",$usuario_id,$grupo_activo,$usuario_id);
    $stmtUltimoPrivado->execute();

    $resPrivado = $stmtUltimoPrivado->get_result()->fetch_assoc();


    /* DECIDIR EL MÁS RECIENTE */

    if ($resGrupal && $resPrivado) {

        if ($resGrupal['id'] > $resPrivado['id']) {

            $ultimo = [
                'nombre'=>$resGrupal['nombre'],
                'mensaje'=>$resGrupal['mensaje']
            ];

        } else {

            $ultimo = [
                'nombre'=>$resPrivado['nombre'],
                'mensaje'=>$resPrivado['mensaje']
            ];

        }

    }

    elseif ($resGrupal) {

        $ultimo = [
            'nombre'=>$resGrupal['nombre'],
            'mensaje'=>$resGrupal['mensaje']
        ];

    }

    elseif ($resPrivado) {

        $ultimo = [
            'nombre'=>$resPrivado['nombre'],
            'mensaje'=>$resPrivado['mensaje']
        ];

    }

}

echo json_encode([
    'total'=>$total,
    'ultimo'=>$ultimo
]);