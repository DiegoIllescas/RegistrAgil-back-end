<?php
    include "config.php";
    include "utils.php";
    header("Content-Type: application/json; charset=utf-8");

    //Lectura de los headers de la peticion
    $headers = apache_request_headers();
    $isAuth = isAuth($headers, $keypass);
    
    //Error si el Token de Sesion expiro
    if($isAuth == 432) {
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
    $data = json_decode($json);

    //Error cuando no mandan un json bien formado
    if(!$data) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Dar de alta empleado
    if($_SERVER['REQUEST_METHOD'] === 'POST') {

    }

    //Consultar Empleado
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        if(isset($data->correo)) {
            $query = "SELECT Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Usuario.correo, Usuario.telefono, Empleado.direccion, Empleado.departamento, Usuario.permisos FROM Empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Usuario.correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data->correo);
            $stmt->execute();

            if($stmt->rowCount() > 0) {

            }else{

            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }

    //Dar de baja Empleado
    if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
    }

    //Actualizar datos del Empleado
    if($_SERVER['REQUEST_METHOD'] === 'PUT') {
        
    }
    $dbConn = null;
?>