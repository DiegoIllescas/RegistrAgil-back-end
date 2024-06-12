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

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    //Error cuando no mandan un json bien formado
    if(!$data) {
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    if(!$dbConn) {
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Login
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($data['correo'], $data['password'])) {
            $query = "SELECT id_usuario, clave, permisos FROM Usuario WHERE correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $res = $stmt->fetch();
                if(password_verify($data['password'], $res['clave'])) {
                    $payload = [
                        'exp' => time() + 10800,
                        'id_usuario' => $res['id_usuario'],
                        'permisos' => $res['permisos']
                    ];
                    $token = genToken($payload, $keypass);
                    
                    echo json_encode(['success' => true, 'permisos' => $res['permisos'] ,'content' => ['correo' => $data['correo'], 'token' => $token]]);
                }else{
                    echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
                }
            }else{
                echo json_encode(['success' => false, 'error' => 'Usuario no registrado']);
            }
            $stmt = null;
        }else {
            echo json_encode(['success' => false, 'error' => 'Faltan parametros']);
        }
    }

    //Comprobar credenciales
    if($_SERVER['REQUEST_METHOD'] === 'GET'){

    }

    //Restablecimiento de contrasena Auth
    if($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        if(isset($data['correo'])) {

            $query = "SELECT id_usuario, lastUpdatePass FROM Usuario WHERE correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            $date = date('Y-m-d');
            $res = $stmt->fetch();
            $diff = round( ((strtotime($date) - strtotime($res['lastUpdatePass'])) / 31556926  ) * 12 );

            if($diff > 0) {
                if($stmt->rowCount() > 0) {
                    
                    if(sendRestorePassword($data['correo'], $res['id_usuario'], $keypass)) {
                        echo json_encode(['success' => true]);
                    }else{
                        echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo, intentelo mas tarde']);
                    }
                }else{
                    echo json_encode(['success' => false, 'error' => 'No existe una cuenta con este correo']);
                }
            }else{
                echo json_encode(['success' => false, 'error' => 'La contraseña actual se actualizó hace menos de 1 mes.']);
            }
            $stmt = null;
        }else{
            echo json_encode(['success' => false, 'error' => 'Faltan parametros']);
        }
    }
    $dbConn = null;
?>