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
    $adminData = $isAuth['payload'];

    //Lectura de JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    //Error cuando no mandan un json bien formado
    if(!$data) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    //Comprobacion del permiso para estas acciones (Unicamente Admin[1])
    if($adminData['permisos'] != 1) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(['success' => false, 'error' => 'No estas autorizado para estas acciones']);
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
        if(isset($data['correo'], $data['nombre'], $data['apellido_paterno'], $data['apellido_materno'], $data['telefono'], $data['direccion'], $data['permisos'], $data['foto'])) {
            //Comprobamos que no exista el correo en el sistema
            $query = "SELECT * FROM Usuario WHERE correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            if($stmt->rowCount() === 0) {
                //Obtenemos el valor de la Empresa que lo hereda del admin
                $query = "SELECT empresa FROM Usuario WHERE id_usuario = ?";
                $stmt = $dbConn->prepare($query);
                $stmt->bindParam(1, $adminData['id_usuario']);
                $stmt->execute();

                $res = $stmt->fetch();

                //Generacion de contrasena para el empleado
                $newPassword = newPassword();

                //Preparamos para insertar
                $query = "INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, telefono, permisos, clave) VALUE (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(1, $data['correo']);
                $stmt->bindValue(2, $data['nombre']);
                $stmt->bindValue(3, $data['apellido_paterno']);
                $stmt->bindValue(4, $data['apellido_materno']);
                $stmt->bindValue(5, $res['empresa']);
                $stmt->bindValue(6, $data['telefono']);
                $stmt->bindValue(7, $data['permisos']);
                $stmt->bindValue(8, password_hash($newPassword, PASSWORD_DEFAULT));
                $stmt->execute();

                //Insertamos ahora en Empleados
                $newId = $dbConn->lastInsertId();

                //Guardar imagen en el sistema de archivos
                $img = base64_decode($data['foto']);
                $url = "./img/".$newId.".jpg";
                file_put_contents($url, $img);

                $query = "UPDATE Usuario SET fotografia = :foto WHERE id_usuario = :id";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(':foto', $url);
                $stmt->bindParam(':id', $newId);
                $stmt->execute();

                $departamento = 'Recepcion';
                if(isset($data['departamento']))
                    $departamento = $data['departamento'];

                $query = "INSERT INTO Empleado (id_usuario, departamento, direccion) VALUE (?, ?, ?)";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(1, $newId);
                $stmt->bindValue(2, $departamento);
                $stmt->bindValue(3, $data['direccion']);
                $stmt->execute();

                /* Mandar correo al Empleado con su clave para entrar al sistema */
                if(!sendPassword($data['correo'], $newPassword)){
                    //No pudo mandar el correo al empleado

                    //Si se implementa modo transaccional simplemente iria un rollback
                    $query = "DELETE FROM Usuario WHERE id_usuario = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $newId);
                    $stmt->execute();

                    unlink($url);

                    header("HTTP/1.1 503 Service Unavailable");
                    echo json_encode(['success' => false, 'error' => 'No se pudo mandar el correo al empleado']);
                }else{
                    echo json_encode(['success' => true, 'message' => 'Cuenta creada con exito']);
                }                
            }else{
                //Ya existe una cuenta con ese correo
                header("HTTP/1.1 412 Precondition Failed");
                echo json_encode(['success' => false, 'error' => 'Ya existe un usuario con esta cuenta']);
            }
            $stmt = null;
        }else {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
        }
    }

    //Consultar Empleado
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        if(isset($data['correo'])) {
            $query = "SELECT Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Usuario.correo, Usuario.telefono, Empleado.direccion, Empleado.departamento, Usuario.permisos FROM Empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Usuario.correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $res = $stmt->fetch();
                echo json_encode(['success' => true, 'content' => $res]);
            }else{
                header("HTTP/1.1 412 Precondition Failed");
                echo json_encode(['success' => false, 'error' => 'No existe un empleado asociado al correo ingresado']);
            }
            $stmt = null;
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }

    //Dar de baja Empleado
    if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        //Validar que el json tenga el correo del empleado a eliminar
        if(isset($data['correo'])) {
            //validar que el usuario exista
            $query = "SELECT * FROM Usuario WHERE correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                //validar que sea un empleado
                $res = $stmt->fetch();
                if($res['permisos'] === 4 || $res['permisos'] === 3) {
                    //Hard DELETE del Empleado
                    $query = "DELETE FROM Usuario WHERE correo = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $data['correo']);
                    if($stmt->execute()){
                        unlink("./img/".$res['id_usuario'].".jpg");
                        echo json_encode(['success' => true]);
                    }else{
                        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar al empleado']);
                    }
                }else{
                    echo json_encode(['success' => false, 'error' => 'El usuario no es un empleado']);
                }
            }else{
                echo json_encode(['success' => false, 'error' => 'No existe empleado con ese correo']);
            }
            $stmt = null;
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }

    //Actualizar datos del Empleado
    if($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if(isset($data['correo'])) {
            $query = "UPDATE Usuario SET ";
            /* Iterar cada parametro pasado para actualizarlo en la BD */
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }
    $dbConn = null;
?>