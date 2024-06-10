<?php
    include "config.php";
    include "utils.php";
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Methods: GET, PUT');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600'); // 1 hour cache

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    date_default_timezone_set('America/Mexico_City');
    //Lectura de los headers de la peticion
    $headers = apache_request_headers();
    $isAuth = isAuth($headers, $keypass);
    
    //Error si el Token de Sesion expiro
    if($isAuth['status'] == 432) {
        header("HTTP/1.1 308 Session Expired");
        echo json_encode(['success' => false, 'error' => 'Sesion expirada']);
        exit();
    }

    //Error si no incluye el Token de Autenticacion
    if($isAuth['status'] == 401) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(['success' => false, 'error' => 'No estas logueado']);
        exit();
    }

    //Obtener datos del token de autenticacion.
    $userData = $isAuth['payload'];

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        if($userData['permisos'] == 1) {
            $date = date('Y-m-d');
            $query = "SELECT CONCAT(a.nombre, ' ', a.apellido_paterno) AS invitado, a.telefono, a.correo, Junta.sala, CONCAT(b.nombre, ' ', b.apellido_paterno) AS encargado, Junta.asunto, InvitadosPorJunta.entrada, InvitadosPorJunta.salida, a.fotografia, Automovil.placa as placaAuto, Automovil.color as colorAuto, Automovil.modelo as modeloAuto FROM InvitadosPorJunta INNER JOIN Invitado ON InvitadosPorJunta.id_invitado = Invitado.id_invitado INNER JOIN Usuario AS a ON Invitado.id_usuario = a.id_usuario INNER JOIN Junta ON InvitadosPorJunta.id_junta = Junta.id_junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario AS b ON Empleado.id_usuario = b.id_usuario LEFT JOIN Automovil ON InvitadosPorJunta.id_automovil = Automovil.id_automovil WHERE Junta.fecha = ? ;";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $date);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $res = $stmt->fetchAll();
                foreach ($res as &$dato) {
                    $bin = file_get_contents($dato['fotografia']);
                    $binEncoded = base64_encode($bin);
                    $dato['fotografia'] = $binEncoded;
                }
                echo json_encode(['success' => true, 'datos' => $res]);
            }else{
                echo json_encode(['success' => true, 'datos' => []]);
            }
            $stmt = null;
        }else {
            header("HTTP/1.1 401 Unauthorized");
            echo json_encode(['success' => false, 'error' => 'No estas logueado']);
        }
    }
?>