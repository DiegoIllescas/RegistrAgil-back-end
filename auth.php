<?php
    include "config.php";
    include "utils.php";
    header("Content-Type: application/json; charset=utf-8");

    $json = file_get_contents('php://input');
    $data = json_decode($json);

    //Error cuando no mandan un json bien formado
    if(!$data) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    if(!isset($data->correo, $data->clave)) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Faltan parametros']);
        exit();
    }

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    $query = "SELECT correo, clave, permisos FROM Usuario WHERE correo = ?";
    $stmt = $dbConn->prepare($query);
    $stmt->bindParam(1, $data->correo);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $res = $stmt->fetch();
        if(password_verify($data->clave, $res['clave'])) {
            $payload = [
                'exp' => time() + 3600,
                'correo' => $data->correo,
                'permisos' => $res['permisos']
            ];
            $token = genToken($payload, $keypass);
            header("HTTP/1.1 200 OK");
            echo json_encode(['success' => true, 'permisos' => $res['permisos'] ,'content' => ['correo' => $data->correo, 'token' => $token]]);
        }else{
            header("HTTP/1.1 412 Precondition Failed");
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
        }
    }else{
        header("HTTP/1.1 412 Precondition Failed");
        echo json_encode(['success' => false, 'error' => 'Usuario no registrado']);
    }

    $dbConn = null;
?>