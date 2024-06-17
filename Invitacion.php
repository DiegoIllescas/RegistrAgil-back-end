<?php
    include "config.php";
    include "utils.php";
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Methods: GET, PUT');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600'); // 1 hour cache
    
    date_default_timezone_set('America/Mexico_City');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $headers = apache_request_headers();

    if($_SERVER['REQUEST_METHOD'] === "GET") {
        $auth = isAuth($headers, $keypass);

        if($auth['status'] == 200){    
            echo json_encode(["success" => true, 'maxAcom' => $auth['payload']['maxAcom'] ]);
        }else{
            if($auth['status'] == 432) {
                echo json_encode(['success' => false, 'error' => 'Sesion expirada']);
            }else{
                echo json_encode(['success' => false, 'error' => 'Sin autorizacion']);
            }
            
        }
        exit(0);
    }

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
    $userData = $isAuth['payload'];

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    //Error cuando no mandan un json bien formado
    if(!$data) {
        echo json_encode(['success' => false, 'error' => 'Falta el JSON']);
        exit();
    }
    //Establecimiento de la conexion a la BD
    $dbConn = connect($db);

    //Si no se pudo conectar a la base
    if(!$dbConn) {
        echo json_encode(['success' => false, 'error' => 'Servicio no disponible']);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === "PUT" ) {
        $query = "SELECT estado FROM InvitadosPorJunta WHERE id_qr = ?";
        $stmt = $dbConn->prepare($query);
        $stmt->bindParam(1, $userData['idjunta']);
        $stmt->execute();

        if($stmt->fetch()['estado'] == 'Confirmada') {
            echo json_encode(['success' => false, 'error' => 'Ya has llenado este formulario']);
            exit();
        }
        //Llena sus datos personales y dispositivos, auto y acompanantes
        if(isset($data['nombre'], $data['apellido_paterno'], $data['apellido_materno'], $data['telefono'], $data['empresa'], $data['fotografia'], $data['documento'])) {
            //Encontrar id de usuario para actualizarlo
            $query = "SELECT Usuario.id_usuario as id, Usuario.correo FROM Usuario INNER JOIN Invitado ON Usuario.id_usuario = Invitado.id_usuario INNER JOIN InvitadosPorJunta ON Invitado.id_invitado = InvitadosPorJunta.id_invitado WHERE InvitadosPorJunta.id_qr = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $userData['idjunta']);
            $stmt->execute();

            $res = $stmt->fetch();
            $id = $res['id'];
            $correo = $res['correo'];
            $img = base64_decode($data['fotografia']);
            $url = "./img/".$id.".jpg";
            file_put_contents($url, $img);

            $newPass = newPassword();
            

            $query = "UPDATE Usuario  SET nombre = :nombre, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno, telefono = :telefono, empresa = :empresa, fotografia = CONCAT('./img/',Usuario.id_usuario,'.jpg'), Usuario.clave = :clave WHERE id_usuario = :id_usuario";
            $stmt = $dbConn->prepare($query);
            $stmt->bindValue(':nombre', $data['nombre']);
            $stmt->bindValue(':apellido_paterno', $data['apellido_paterno']);
            $stmt->bindValue(':apellido_materno', $data['apellido_materno']);
            $stmt->bindValue(':telefono', $data['telefono']);
            $stmt->bindValue(':empresa', $data['empresa']);
            $stmt->bindValue(':clave', password_hash($newPass, PASSWORD_DEFAULT));
            $stmt->bindValue(':id_usuario', $id);
            
            $stmt->execute();

            $query = "UPDATE Invitado SET tipo_identificacion = :tipo_identificacion WHERE id_usuario = :id_usuario";
            $stmt = $dbConn->prepare($query);
            $stmt->bindValue(':tipo_identificacion', $data['documento']);
            $stmt->bindValue(':id_usuario', $id);
            
            $stmt->execute();


            if(isset($data['automovil'])) {
                //Verificar si ya existe:
                $query = "SELECT id_automovil FROM Automovil WHERE placa = ?";
                $stmt = $dbConn->prepare($query);
                $stmt->bindParam(1, $data['automovil']['placa']);
                $stmt->execute();

                $idAuto = 0;
                if($stmt->rowCount() > 0) {
                    $idAuto = $stmt->fetch()['id_automovil'];
                }else{
                    $query = "INSERT INTO Automovil (placa, color, modelo) VALUE (?, ?, ?)";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindValue(1, $data['automovil']['placa']);
                    $stmt->bindValue(2, $data['automovil']['color']);
                    $stmt->bindValue(3, $data['automovil']['modelo']);
                    $stmt->execute();
                    $idAuto = $dbConn->lastInsertId();
                }

                $query = "UPDATE InvitadosPorJunta SET id_automovil = :id_auto WHERE id_qr = :qr";
                $stmt = $dbConn->prepare($query);
                $stmt->bindValue(':id_auto', $idAuto);
                $stmt->bindValue(':qr', $userData['idjunta']);
                $stmt->execute();
            }

            $query = "SELECT Junta.id_junta, Junta.fecha, Junta.hora_fin, Junta.hora_inicio, Junta.sala, Junta.direccion, InvitadosPorJunta.id_invitado, CONCAT(Usuario.nombre, ' ', Usuario.apellido_paterno, ' ', Usuario.apellido_materno) as anfitrion, Usuario.empresa FROM InvitadosPorJunta INNER JOIN Junta ON InvitadosPorJunta.id_junta = Junta.id_junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario ON Empleado.id_usuario = Usuario.id_usuario WHERE InvitadosPorJunta.id_qr = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $userData['idjunta']);
            $stmt->execute();

            $res = $stmt->fetch();
            $idjunta = $res['id_junta'];
            $fecha = $res['fecha'];
            $hora = $res['hora_fin'];
            $horai = $res['hora_inicio'];
            $sala = $res['sala'];
            $direccion = $res['direccion'];
            $anfitrion = $res['anfitrion'];
            $empresa = $res['empresa'];
            $idOriginnvitado = $res['id_invitado'];

            if(isset($data['acompañantes'])) {
                foreach ($data['acompañantes'] as &$invitado) {
                    //Comprobar que no esten ya registrados
                    $query = "SELECT Invitado.id_invitado FROM Invitado INNER JOIN Usuario ON Invitado.id_usuario = Usuario.id_usuario WHERE Usuario.correo = ? AND Usuario.permisos = 2";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $invitado['correo']);
                    $stmt->execute();

                    if($stmt->rowCount() > 0) {
                        $idInvitado =  $stmt->fetch()['id_invitado'];
                        $query = "INSERT INTO InvitadosPorJunta (id_junta, id_invitado, estado, invitado_por) VALUE (:id_junta, :id_invitado, 'Pendiente', :invitado_por)";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(':id_junta', $idjunta);
                        $stmt->bindValue(':id_invitado', $idInvitado);
                        $stmt->bindValue(':invitado_por', $idOriginnvitado);
                        $stmt->execute();

                        $idQR = $dbConn->lastInsertId();
                    }else{
                        $query = "INSERT INTO Usuario (correo, permisos) VALUE (?, 2)";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(1, $invitado['correo']);
                        $stmt->execute();

                        $idUserInvitado = $dbConn->lastInsertId();

                        $query = "INSERT INTO Invitado (id_usuario) VALUE (?)";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(1, $idUserInvitado);
                        $stmt->execute();

                        $idInvitado = $dbConn->lastInsertId();

                        $query = "INSERT INTO InvitadosPorJunta (id_junta, id_invitado, estado, invitado_por) VALUE (:id_junta, :id_invitado, 'Pendiente', :invitado_por)";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(':id_junta', $idjunta);
                        $stmt->bindValue(':id_invitado', $idInvitado);
                        $stmt->bindValue(':invitado_por', $idOriginnvitado);
                        $stmt->execute();

                        $idQR = $dbConn->lastInsertId();
                    }

                    $query = "SELECT CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', a.apellido_materno) as anfitrion, Junta.asunto, Junta.sala, Junta.fecha, Junta.hora_inicio, Junta.hora_fin, Junta.descripcion, Junta.direccion, a.empresa, a.correo as anfitrionCorreo, a.telefono FROM Junta INNER JOIN Empleado ON Junta.id_anfitrion = Empleado.id_empleado INNER JOIN Usuario as a ON Empleado.id_usuario = a.id_usuario WHERE Junta.id_junta = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $idjunta);
                    $stmt->execute();

                    $content = $stmt->fetch();

                    sendInvitation($idQR, $invitado['correo'], 0, $content, $keypass);
                }
            }

            if(isset($data['dispositivos'])) {
                foreach($data['dispositivos'] as &$dispositivo) {
                    //Comprobar que no existan
                    $query = "SELECT id_dispositivo FROM Dispositivo WHERE no_serie = ?";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindParam(1, $dispositivo['serie']);
                    $stmt->execute();

                    if($stmt->rowCount() > 0) {
                        $idDisp = $stmt->fetch()['id_dispositivo'];
                    }else{
                        $query = "INSERT INTO Dispositivo (modelo, no_serie) VALUE (?, ?)";
                        $stmt = $dbConn->prepare($query);
                        $stmt->bindValue(1, $dispositivo['modelo']);
                        $stmt->bindValue(2, $dispositivo['serie']);
                        $stmt->execute();

                        $idDisp = $dbConn->lastInsertId();
                    }

                    $query = "INSERT INTO DispositivosPorReunion (id_qr, id_dispositivo) VALUE (?, ?)";
                    $stmt = $dbConn->prepare($query);
                    $stmt->bindValue(1, $userData['idjunta']);
                    $stmt->bindValue(2, $idDisp);
                    $stmt->execute();
                }
            }

            //Update estatus
            $query = "UPDATE InvitadosPorJunta SET estado = 'Confirmada' WHERE id_qr = ?";
            $stmt = $dbConn->prepare($query);
            $stmt->bindParam(1, $userData['idjunta']);
            $stmt->execute();

            //Generar QR
            genQR($userData['idjunta'], $fecha, $hora, $keypass);

            $content = [
                'nombre' => $data['nombre'].' '.$data['apellido_paterno'].' '.$data['apellido_materno'],
                'empresa' => $empresa,
                'fecha' => date('d-m-Y', strtotime($fecha)),
                'hora' => date('h:i a', strtotime($horai)),
                'sala' => $sala,
                'direccion' => $direccion,
                'anfitrion' => $anfitrion
            ];

            sendConfirmation($correo, $newPass, $content);

            echo json_encode(['success' => true]);
            
        }else{
            echo json_encode(['success' => false, 'error' => 'Faltan parametros']);
        }
        
    }
    $dbConn = null;
?>