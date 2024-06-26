<?php
    include "config.php";
    include "utils.php";
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600'); // 1 hour cache

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
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
    
    $userData = $isAuth['payload'];

    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Obtener datos de usuario "Ver Perfil"
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "SELECT nombre, apellido_paterno, apellido_materno, correo, fotografia FROM Usuario WHERE id_usuario = ?";
        $stmt = $dbConn->prepare($query);
        $stmt->bindParam(1, $userData['id_usuario']);
        $stmt->execute();

        $res = $stmt->fetch();
        $bin = file_get_contents($res['fotografia']);
        $binEncoded = base64_encode($bin);

        $res['fotografia'] = $binEncoded;

        echo json_encode(['success' => true, 'content' => $res]);
        $stmt = null;
    }
    
    //cambiar contrasna o foto
    if($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(isset($data['clave'])) {
            //Validar si la contraseña no es la que ya esta en la base:
            $query = "SELECT clave FROM Usuario WHERE id_usuario = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $userData['id_usuario']);
            $stmt->execute();
            
            $res = $stmt->fetch();
            $date = date('Y-m-d');

            if(!password_verify($data['clave'], $res['clave'])) {
                //Cambiar contraseña
                
                $query = "UPDATE Usuario SET clave = :clave, lastUpdatePass = :fecha WHERE id_usuario = :id_usuario";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(':clave', password_hash($data['clave'], PASSWORD_DEFAULT));
                $stmt->bindValue(':fecha', $date);
                $stmt->bindValue(':id_usuario', $userData['id_usuario']);
                if($stmt->execute()) {
                    echo json_encode(['success' => true]);
                }else{
                    echo json_encode(['success' => false, 'error' => 'No se pudo actualizar tu contraseña']);
                }
            }else{
                echo json_encode(['success' => false, 'error' => 'La contraseña nueva no puede ser igual a la contraseña actual.']);
            }
            
            
        }

    }
    $dbConn = null;
?>