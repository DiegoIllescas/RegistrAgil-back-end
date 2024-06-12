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
        echo json_encode(['success' => false, 'error' => 'Sesion expirada']);
        exit();
    }

    //Error si no incluye el Token de Autenticacion
    if($isAuth['status'] == 401) {
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
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }

    //Comprobacion del permiso para estas acciones (Unicamente Admin[1])
    if($adminData['permisos'] != 1) {
        echo json_encode(['success' => false, 'error' => 'No estas autorizado para estas acciones']);
        exit();
    }

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Dar de alta empleado
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($data['correo'], $data['nombre'], $data['apellido_paterno'], $data['apellido_materno'], $data['telefono'], $data['direccion'], $data['permisos'], $data['fotografia'])) {
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
                $img = base64_decode($data['fotografia']);
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
                    echo json_encode(['success' => false, 'error' => 'No se pudo mandar el correo al empleado']);
                }else{
                    echo json_encode(['success' => true, 'message' => 'Cuenta creada con exito']);
                }                
            }else{
                //Ya existe una cuenta con ese correo
                echo json_encode(['success' => false, 'error' => 'Ya existe un usuario con esta cuenta']);
            }
            $stmt = null;
        }else if(isset($data['correo'])) {
            $query = "SELECT Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Usuario.correo, Usuario.telefono, Empleado.direccion, Empleado.departamento, Usuario.permisos, Usuario.fotografia FROM Empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Usuario.correo = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $res = $stmt->fetch();
                $bin = file_get_contents($res['fotografia']);
                $binEncoded = base64_encode($bin);

                $res['fotografia'] = $binEncoded;
                switch ($res['permisos']){
                    case 3:
                        $res['permisos'] = "Recepcionista";
                        break;
                    case 4:
                        $res['permisos'] = "Anfitrión";
                        break;
                }
                echo json_encode(['success' => true, 'content' => $res]);
            }else{
                echo json_encode(['success' => false, 'error' => 'No existe un empleado asociado al correo ingresado']);
            }
            $stmt = null;
        } else {
            echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
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
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }

    //Actualizar datos del Empleado
    if($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if(isset($data['correo'], $data['telefono'], $data['departamento'], $data['permisos'])) {
            $query = "UPDATE Usuario SET telefono = :telefono, permisos = :permisos WHERE correo = :correo";
            $stmt = $dbConn->prepare($query);
            $stmt->bindValue(':telefono', $data['telefono']);
            $stmt->bindValue(':permisos', $data['permisos']);
            $stmt->bindValue(':correo', $data['correo']);
            $stmt->execute();

            $stmt = $dbConn->prepare("SELECT id_usuario FROM Usuario WHERE correo = ?");
            $stmt->bindParam(1, $data['correo']);
            $stmt->execute();
            
            $res = $stmt->fetch();

            $stmt = $dbConn->prepare("UPDATE Empleado SET departamento = :departamento WHERE id_usuario = :id_usuario");
            $stmt->bindValue(':departamento', $data['departamento']);
            $stmt->bindValue(':id_usuario', $res['id_usuario']);
            $stmt->execute();

            if($data['newPassword']) {
                $newPassword = newPassword();
                $stmt = $dbConn->prepare("UPDATE Usuario SET clave = :clave WHERE correo = :correo");
                $stmt->bindValue(':clave', password_hash($newPassword, PASSWORD_DEFAULT));
                $stmt->bindValue(':correo', $data['correo']);
                $stmt->execute();
                
                sendNewPassword($data['correo'], $newPassword);
            }
            
            echo json_encode(["success" => true]);
            /* Iterar cada parametro pasado para actualizarlo en la BD */
        }else{
            echo json_encode(['success' => false, 'error' => 'Falta atributo correo']);
        }
    }
    $dbConn = null;
?>