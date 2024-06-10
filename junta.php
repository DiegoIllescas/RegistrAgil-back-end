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

    //Error cuando no mandan un json bien formado

    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        header("HTTP/1.1 503 Service Unavailable");
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    //Crear Junta
    if($_SERVER['REQUEST_METHOD'] === 'POST') {

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(!$data) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
            exit();
        }

        //Revisar permisos de creacion de Junta (Unicamente Admin y Anfitrion)
        if($userData['permisos'] === 1 || $userData['permisos'] === 4) {
            //Comprobar los atributos obligatorios
            if(isset($data['asunto'], $data['sala'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'], $data['descripcion'], $data['direccion'], $data['invitados'])) {
                
                //Obtener id del anfitrion
                $query = "SELECT Empleado.id_empleado, Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Usuario.empresa, Usuario.correo, Usuario.telefono FROM Empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Empleado.id_usuario = ? AND departamento != 'Recepcion'";
                $stmt = $dbConn->prepare($query);
                $stmt->bindParam(1, $userData['id_usuario']);
                $stmt->execute();

                $res = $stmt->fetch();

                $anfitrion = $res['nombre']." ".$res['apellido_paterno']." ".$res['apellido_materno'];
                $empresa = $res['empresa'];
                $anfitrionCorreo = $res['correo'];
                $telefono = $res['telefono'];

                //Damos de alta la junta
                $query = "INSERT INTO Junta (id_anfitrion, asunto, sala, fecha, hora_inicio, hora_fin, descripcion, direccion) VALUE (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(1, $res['id_empleado']);
                $stmt->bindValue(2, $data['asunto']);
                $stmt->bindValue(3, $data['sala']);
                $stmt->bindValue(4, date('Y-m-d', strtotime($data['fecha'])));
                $stmt->bindValue(5, $data['hora_inicio']);
                $stmt->bindValue(6, $data['hora_fin']);
                $stmt->bindValue(7, $data['descripcion']);
                $stmt->bindValue(8, $data['direccion']);
                if($stmt->execute()) {
                    $flag = true;
                    $id_junta = $dbConn->lastInsertId();
                    //Si pudo registrar la junta
                    foreach($data['invitados'] as $invitado) {
                        $query = "SELECT id_usuario FROM Usuario WHERE correo = ? AND permisos = 2";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindParam(1, $invitado['correo']);
                        $stmt->execute();
                        
                        if($stmt->rowCount() == 0) {
                            //No existe en el sistema

                            $query = "INSERT INTO Usuario (correo, permisos) VALUE (?, ?)";
                            $stmt = $dbConn->prepare($query);
                            $stmt->bindValue(1, $invitado['correo']);
                            $stmt->bindValue(2, 2);
                            if($stmt->execute()) {
                                $query = "INSERT INTO Invitado (id_usuario) VALUE (?)";
                                $stmt = $dbConn->prepare($query);
                                $stmt->bindValue(1, $dbConn->lastInsertId());
                                $stmt->execute();
                                $id_invitado = $dbConn->lastInsertId();
                            }else{
                                //Nose bro cuando ya existe el correo pero no es invitado, caso imposible para nuestro diseno
                            }
                        } else {
                            //Existe en el sistema
                            $res = $stmt->fetch();
                            $query = "SELECT id_invitado FROM Invitado WHERE id_usuario = ?";
                            $stmt = $dbConn->prepare($query);
                            $stmt->bindParam(1, $res['id_usuario']);
                            $stmt->execute();

                            $res = $stmt->fetch();
                            $id_invitado = $res['id_invitado'];
                        }

                        $query = "INSERT INTO InvitadosPorJunta (id_junta, id_invitado, estado) VALUE (?, ?, 'Pendiente')";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(1, $id_junta);
                        $stmt->bindValue(2, $id_invitado);
                        $stmt->execute();

                        $idQR = $dbConn->lastInsertId();

                        $content = [
                            "anfitrion" => $anfitrion, 
                            "asunto" => $data['asunto'],
                            "sala" => $data['sala'],
                            "fecha" => $data['fecha'],
                            "hora_inicio" => $data['hora_inicio'],
                            "hora_fin" => $data['hora_fin'],
                            "descripcion" => $data['descripcion'],
                            "direccion" => $data['direccion'],
                            "empresa" => $empresa,
                            "anfitrionCorreo" => $anfitrionCorreo,
                            "telefono" => $telefono
                        ];

                        $flag = $flag && sendInvitation($idQR, $invitado['correo'], $invitado['acompañantes'], $content, $keypass);
                    }
                    echo json_encode(['success' => true, 'id_reunion' => $idQR, 'flag' => $flag]);
                }else{
                    echo json_encode(['success' => false, 'error' => 'No se pudo agendar la junta']);
                }
                $stmt = null;   
            }else if(isset($data['mes'], $data['year'])) {
                if($userData['permisos'] === 1) {
                    $query = "SELECT CONCAT(Usuario.nombre, ' ', Usuario.apellido_paterno) AS anfitrion, Junta.asunto, Junta.sala, Junta.fecha, Junta.hora_inicio, Junta.hora_fin, Junta.descripcion, Junta.direccion  FROM Junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE MONTH(Junta.fecha) = :mes AND YEAR(Junta.fecha) = :yr";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindValue(':mes', $data['mes']);
                    $stmt->bindValue(':yr', $data['year']);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0){
                        $res = $stmt->fetchAll();
                        echo json_encode(['success' => true, 'juntas' => $res]);
                    }else{
                        echo json_encode(['success' => true, 'juntas' => []]);
                    }
                }else{
                    $query = "SELECT CONCAT(Usuario.nombre, ' ', Usuario.apellido_paterno) AS anfitrion, Junta.asunto, Junta.sala, Junta.fecha, Junta.hora_inicio, Junta.hora_fin, Junta.descripcion, Junta.direccion  FROM Junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE MONTH(Junta.fecha) = :mes AND YEAR(Junta.fecha) = :yr AND Usuario.id_usuario = :user";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindValue(':mes', $data['mes']);
                    $stmt->bindValue(':yr', $data['year']);
                    $stmt->bindValue(':user', $userData['id_usuario']);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0){
                        $res = $stmt->fetchAll();
                        echo json_encode(['success' => true, 'juntas' => $res]);
                    }else{
                        echo json_encode(['success' => true, 'juntas' => []]);
                    }
                }
            }else{
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
            }
        }else{
            header("HTTP/1.1 401 Unauthorized");
            echo json_encode(['success' => false, 'error' => 'No estas autorizado para estas acciones']);
        }
    }

    //Consultar Juntas
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        $date = date('Y-m-d');
        if($userData['permisos'] == 1) {
            $query = "SELECT Junta.id_junta as id, Junta.fecha, Junta.hora_inicio, Junta.hora_fin, Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Junta.asunto, Junta.sala, Junta.descripcion FROM Junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Junta.fecha >= ? ORDER BY Junta.fecha";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $date);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $juntas = $stmt->fetchAll();
                foreach($juntas as &$junta) {
                    $query = "SELECT CONCAT(Usuario.nombre, ' ', Usuario.apellido_paterno, ' ', Usuario.apellido_materno) as nombre, Usuario.correo FROM InvitadosPorJunta INNER JOIN Invitado ON InvitadosPorJunta.id_invitado = Invitado.id_invitado INNER JOIN Usuario ON Invitado.id_usuario = Usuario.id_usuario WHERE InvitadosPorJunta.id_junta = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $junta['id_junta']);
                    $stmt->execute();

                    unset($junta['id_junta']);

                    if($stmt->rowCount() > 0) {
                        $invitados = $stmt->fetchAll();
                        $junta['invitados'] = $invitados;
                    }else{
                        $junta['invitados'] = [];
                    }
                }

                echo json_encode(['success' => true, 'juntas' => $juntas]);

            }
        }else{
            $query = "SELECT Junta.id_junta as id, Junta.fecha, Junta.hora_inicio, Junta.hora_fin, Usuario.nombre, Usuario.apellido_paterno, Usuario.apellido_materno, Junta.asunto, Junta.sala, Junta.descripcion FROM Junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE Junta.fecha >= ? AND Usuario.id_usuario = ? ORDER BY Junta.fecha";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $date);
            $stmt->bindParam(2, $userData['id_usuario']);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $juntas = $stmt->fetchAll();
                foreach($juntas as &$junta) {
                    $query = "SELECT CONCAT(Usuario.nombre, ' ', Usuario.apellido_paterno, ' ', Usuario.apellido_materno) as nombre, Usuario.correo FROM InvitadosPorJunta INNER JOIN Invitado ON InvitadosPorJunta.id_invitado = Invitado.id_invitado INNER JOIN Usuario ON Invitado.id_usuario = Usuario.id_usuario WHERE InvitadosPorJunta.id_junta = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $junta['id']);
                    $stmt->execute();

                    if($stmt->rowCount() > 0) {
                        $invitados = $stmt->fetchAll();
                        $junta['invitados'] = $invitados;
                    }else{
                        $junta['invitados'] = [];
                    }
                }

                echo json_encode(['success' => true, 'juntas' => $juntas]);

            }
        }
    }

    //Eliminar(Cancelar) Junta
    if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(!$data) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
            exit();
        }

        if(isset($data['id_junta'])) {
            $query = "DELETE FROM Junta WHERE id_junta = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $data['id_junta']);
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'La junta fue cancelada con exito']);
            }else{
                echo json_encode(['success' => false, 'error' => 'No se pudo cancelar la junta']);
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
        }
    }

    //Editar Junta
    if($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if(!$data) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
            exit();
        }

        if(isset($data['id_junta'], $data['asunto'], $data['descripcion'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'], $data['sala'])) {
            $query = "UPDATE Junta SET asunto = :asunto, descripcion = :descripcion, fecha = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin, sala = :sala WHERE id_junta = :id";
            $stmt = $dbConn->prepare($query);
            $stmt->bindValue(':asunto', $data['asunto']);
            $stmt->bindValue(':descripcion', $data['descripcion']);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':hora_inicio', $data['hora_inicio']);
            $stmt->bindValue(':hora_fin', $data['hora_fin']);
            $stmt->bindValue(':sala', $data['sala']);
            $stmt->bindValue(':id', $data['id_junta']);
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'La junta fue editada con exito']);
            }else{
                echo json_encode(['success' => false, 'error' => 'No se pudo editar la junta']);
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
        }
    }

    $dbConn = null;
?>