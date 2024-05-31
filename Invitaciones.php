<?php
    include "config.php";
    include "utils.php";
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600'); // 1 hour cache

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $headers = apache_request_headers();

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === "GET") {
        $auth = isAuth($headers, $keypass);

        if($auth['status'] == 200){    
            echo json_encode(["success" => true, "id_Inv" => $auth['payload']['idjunta'], 'maxAcom' => $auth['payload']['maxAcom'] ]);
        }else{
            if($auth['status'] == 432) {
                echo json_encode(['success' => false, 'error' => 'Sesion expirada']);
            }else{
                echo json_encode(['success' => false, 'error' => 'Sin autorizacion']);
            }
            
        }

    }
?>