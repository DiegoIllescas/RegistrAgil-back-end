<?php
    include "config.php";
    include "utils.php";
    header("Content-Type: application/json; charset=utf-8");

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
    
    //Lectura de JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    //Obtener datos del token de autenticacion.
    $userData = $isAuth['payload'];

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

    //Crear Junta
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        //Revisar permisos de creacion de Junta (Unicamente Admin y Anfitrion)
        if($userData['permisos'] === 1 || $userData['permisos'] === 4) {
            //Comprobar los atributos obligatorios
            if(isset($data['asunto'], $data['sala'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'], $data['descripcion'], $data['direccion'], $data['invitados'])) {
                
                //Obtener id del anfitrion
                $query = "SELECT id_empleado FROM Empleado WHERE id_usuario = ? AND departamento != 'Recepcion'";
                $stmt = $dbConn->prepare($query);
                $stmt->bindParam(1, $userData['id_usuario']);
                $stmt->execute();

                $res = $stmt->fetch();

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
                    $id_junta = $dbConn->lastInsertId();
                    //Si pudo registrar la junta
                    foreach($data['invitados'] as $invitado) {
                        $query = "SELECT id_usuario FROM Usuario WHERE correo = ? AND permisos = 2";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindParam(1, $invitado['correo']);
                        $stmt->execute();
                        
                        if($stmt->rowCount() == 0) {
                            //No existe en el sistema

                            $query = "INSERT INTO Usuario (correo) VALUE (?)";
                            $stmt = $dbConn->prepare($query);
                            $stmt->bindValue(1, $invitado['correo']);
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

                        sendInvitation($id_junta, $invitado['correo'], $invitado['maxAcom'], $data['asunto']);
                    }
                    echo json_encode(['success' => true]);
                }else{
                    echo json_encode(['success' => false, 'error' => 'No se pudo agendar la junta']);
                }
                $stmt = null;   
            }else{
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(['success' => false, 'error' => 'Faltan atributos']);
            }
        }else{
            header("HTTP/1.1 401 Unauthorized");
            echo json_encode(['success' => false, 'error' => 'No estas autorizado para estas acciones']);
        }
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

    $dbConn = null;
?>