<?php
    include "config.php";
    include "utils.php";
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600'); // 1 hour cache

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $headers = apache_request_headers();

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

    if($_SERVER['REQUEST_METHOD'] === "PUT" ) {
        
    }

?>