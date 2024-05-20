<?php
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    require 'vendor/autoload.php';

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

    function isAuth($headers) {
        if (array_key_exists('Authorization', $headers)) {
            $token = getToken($headers['Authorization']);
            try {
                $decodeToken = (array) JWT::decode($token, new Key('PASSWORD_TEST', 'HS256'));
                return 200;
            } catch (\Throwable $th) {
                return 308;
            }
        } else {
            return 401;
        }
    }
?>