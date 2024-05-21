<?php
    include "config.php";
    include "utils.php";
    header("Content-Type: application/json; charset=utf-8");

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Lectura de los headers de la peticion
    $headers = apache_request_headers();
    $isAuth = isAuth($headers, $keypass);
    
    //Error si el Token de Sesion expiro
    if($isAuth == 308) {
        header("HTTP/1.1 308 Session Expired");
        echo json_encode(['success' => false, 'error' => 'Sesion expirada']);
        exit();
    }

    //Error si no incluye el Token de Autenticacion
    if($isAuth == 401) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(['success' => false, 'error' => 'No estas logueado']);
        exit();
    }
    
    //Lectura de JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    //Error cuando no mandan un json bien formado
    if(!$data) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    //Crear Junta
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        
    }

    //Consultar Juntas o Juntas
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        
    }

    //Eliminar(Cancelar) Junta
    if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
    }

    //Editar Junta
    if($_SERVER['REQUEST_METHOD'] === 'PUT') {
        
    }
?>