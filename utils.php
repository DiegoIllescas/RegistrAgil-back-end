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
        $bytes = openssl_random_pseudo_bytes(8);
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
            $mail->Body         = 'Tu cuenta es '.$email." y tu clave es ".$clave;

            $mail->send();
        }catch(Exception $e) {
            
        }
    }
?>