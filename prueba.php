<?php
    include "config.php";
    include "utils.php";

    $dbConn = connect($db);
    if(!$dbConn) {
        echo "Error al conectar a la base";
    }

    $query = "SELECT * FROM anfitrion";
    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $rowsCount = $stmt->rowCount();

    echo $rowsCount;
?>