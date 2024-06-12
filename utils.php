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
            $mail->Body         = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>RegistrÁgil</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        header {
            background-color: #88C7FF;
            color: #fff;
            text-align: center;
            padding: 20px 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            margin-bottom: 10px;
        }

        .informacion-reunion {
            margin-bottom: 30px;
        }

        .registro {
            text-align: center;
        }
        .mensaje{
            text-align: center;
        }
        .btn-registro {
            background-color: #88C7FF;
            color: #ffffff;
            font-weight: bold;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-registro:hover {
            background-color: #007BFF;
        }

        .anfitrion {
            margin-top: 30px;
        }

        footer {
            background-color: #88C7FF;
            color: #fff;
            text-align: center;
            padding: 20px 0;
        }
    </style>
    <meta charset='UTF-8'>
</head>
<body>
    <header>
        <div class='container'>
            <img src='logo.png' alt='Logo de RegistrÁgil' style='width: 200px; height: auto;'>
        </div>
    </header>

    <main>
        <div class='container'>

            <section class='mensaje'>
            <p>Es un placer para nosotros informarte que estás invitado a una reunión en {$data['empresa']}. </p>
 
            </section>


            <section class='informacion-reunion'>
                <h2>Detalles de la reunión:</h2>
                <ul>
                    <li>Fecha: {$data['fecha']}</li>
                    <li>Hora: {$data['hora_inicio']} </li>
                    <li>Sala: {$data['sala']}</li>
                    <li>Dirección: {$data['direccion']}</li>
                    <li>Anfitrión: {$data['anfitrion']}</li>
                </ul>
            </section>

            <section class='asunto-reunion'>
                <h2>Asunto de la Reunión</h2>
                <ul>
                    <li>{$data['asunto']}</li>
                </ul>
            </section>

            <section class='registro'>
                <h2>Registro previo:</h2>
                <p>Para prepararnos para tu visita y facilitar tu acceso a nuestras instalaciones, es necesario realizar tu registro previo en el siguiente enlace:</p>
                <a href='http://localhost:5173/FormularioInvitado?token=$token' class='btn-registro'>REGISTRAR</a>
                <p>Al completar tu registro, recibirás un usuario y contraseña para ingresar a [RegistrÁgil] y descargar tu código QR de acceso. Este código es necesario para entrar al edificio el día de la reunión.</p>
            </section>

            <section class='anfitrion'>
                <h2>Anfitrión:</h2>
                <ul>
                    <li>Nombre: {$data['anfitrion']}</li>
                    <li>Correo electrónico: {$data['anfitrionCorreo']}</li>
                    <li>Teléfono: {$data['telefono']}</li>
                </ul>
            </section>
        </div>
    </main>

    <footer>
        <div class='container'>
            <p>&copy; 2024 RegistrÁgil</p>
        </div>
    </footer>
</body>
</html>";

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