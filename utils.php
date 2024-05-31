<?php
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';

    $payload;

    function connect($db) {
        try {
            $dsn = "mysql:host={$db['host']};dbname={$db['db']};charset=UTF8;port={$db['port']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];
            $conn = new PDO($dsn, $db['user'], $db['pass'], $options);
            return $conn;
        } catch (PDOException $e) {
            return null;
        }
    }

    function newPassword() {
        $bytes = openssl_random_pseudo_bytes(4);
        return bin2hex($bytes);
    }

    function getToken($header) {
        $auth = explode(' ', $header);
        return $auth[1];
    } 

    function isAuth($headers, $key) {
        if (array_key_exists('Authorization', $headers)) {
            $token = getToken($headers['Authorization']);
            try {
                $payload = (array) JWT::decode($token, new Key($key, 'HS256'));
                return ['status' => 200, 'payload' => $payload];
            } catch (\Throwable $th) {
                return ['status' => 432];
            }
        } else {
            return ['status' => 401];
        }
    }

    function genToken($payload, $key) {
        return JWT::encode($payload,$key, 'HS256');
    }

    function sendPassword($email, $clave) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host         = 'smtp.gmail.com';
            $mail->SMTPAuth     = true;
            $mail->Username     = 'softwarelegends65@gmail.com';
            $mail->Password     = 'prhj hhpo rnvs xrqj';
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port         = 465;

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject      = 'Has sido registrado en RegistrAgil por uno de los Administradores';
            $mail->Body         = "Ha sido registrado en el sistema con las siguientes credenciales: \nCorreo: $email\nClave: $clave";

            $mail->send();
            return true;
        }catch(Exception $e) {
            return false;
        }
    }

    function sendNewPassword($email, $clave) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host         = 'smtp.gmail.com';
            $mail->SMTPAuth     = true;
            $mail->Username     = 'softwarelegends65@gmail.com';
            $mail->Password     = 'prhj hhpo rnvs xrqj';
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port         = 465;

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject      = 'Su clave para entrar al sistemaa ha sido cambiada por el Administrador';
            $mail->Body         = "Su nueva clave es: $clave";

            $mail->send();
            return true;
        }catch(Exception $e) {
            return false;
        }
    }

    function sendRestorePassword($correo, $idUsuario, $keyword) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host         = 'smtp.gmail.com';
            $mail->SMTPAuth     = true;
            $mail->Username     = 'softwarelegends65@gmail.com';
            $mail->Password     = 'prhj hhpo rnvs xrqj';
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port         = 465;

            $mail->addAddress($correo);

            $payload = [
                'id_usuario' => $idUsuario,
                'exp' => time() + 900
            ];

            $token = genToken($payload, $keyword);

            $mail->isHTML(true);
            $mail->Subject      = 'Recuperar Contraseña - RegistrAgil';
            $mail->Body         = "<p>Para restaurar su contraseña ingrese al siguiente link</p><p>http://localhost:5173/ResetPassword?token=$token</p>";

            $mail->send();
            return true;
        }catch(Exception $e) {
            return false;
        }
    }

    function sendInvitation($id_junta, $correo, $maxAcom, $data, $keyword) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host         = 'smtp.gmail.com';
            $mail->SMTPAuth     = true;
            $mail->Username     = 'softwarelegends65@gmail.com';
            $mail->Password     = 'prhj hhpo rnvs xrqj';
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port         = 465;

            $mail->addAddress($correo);

            $payload = [
                'exp' => time() + 7200,
                'idjunta' => $id_junta,
                'maxAcom' => $maxAcom
            ];

            $token = genToken($payload, $keyword);

            $mail->isHTML(true);
            $mail->Subject      = $data['asunto'];
            $mail->Body         = "
                <div>
                    <div style={background-color: #121212}>
                        {$data['anfitrion']}
                    </div>
                </div>
            ";

            $mail->send();
            return true;
        }catch(Exception $e) {
            return false;
        }
    }

    function sendPrueba() {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host         = 'smtp.gmail.com';
            $mail->SMTPAuth     = true;
            $mail->Username     = 'softwarelegends65@gmail.com';
            $mail->Password     = 'prhj hhpo rnvs xrqj';
            $mail->SMTPSecure   = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port         = 465;

            $mail->addAddress("dillescas365@gmail.com");

            

            $mail->isHTML(true);
            $mail->Subject      = "Prueba HTML";
            $mail->Body         = "
                <div>
                    <div style='background-color: #121212'>
                        Prueba de mandar html con estilos css
                    </div>
                </div>
            ";

            $mail->send();
            return true;
        }catch(Exception $e) {
            return false;
        }
    }
?>