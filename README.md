# RegistrAgil-back-end
Repositorio de back-end para el proyecto RegistrAgil de ADS

# Instrucciones
## Requisitos

Tener un servidor de Apache instalado ademas de mySQL, ya sea por XAMPP,WAMPP,LAMPP....

Tener instalado `composer`, si no lo tienes instalado accede al link:

`https://getcomposer.org/download/` Para distribuciones Linux

`https://getcomposer.org/doc/00-intro.md#installation-windows` Para Windows

## Paso a paso

1. Clonar este repositorio en algun directorio dentro de la carpeta htdocs del servidor Apache instalado. ej.`xampp/htdocs/ejemplo`

2. Migrar la base de datos dentro de la carpeta `sql` de este repositorio, el `baseFormat.sql` es una propuesta organizada y reducida de la base de datos `baseregistragil.sql`. 

Para migrar la base de datos: Con el servidor Apache y mySQL corriendo entrar a `https://localhost/phpmyadmin/index.php` y seleccionar la opcion [Importar], seleccionar la base que se desea usar (No se sobreescriben si importas las 2).

Dentro de `config.php` especificar con que base de datos vas a utilizar.

3. Dentro del directorio del repositorio ejecuta el comando `composer install`

4. Desarrolla las funciones faltantes siguiendo la estructura:
    
    Metodo POST para ALTAS

    Metodo DELTE para BAJAS

    Metodo PUT para CAMBIOS

    Metodo GET para CONSULTAS

    ej. `junta.php` tiene el formato base

5. Para probar las funciones descargar POSTMAN(`https://www.postman.com/downloads/`) y seleccionar el Metodo y dentro de la opcion [raw] seleccionar JSON y formar los JSON necesarios.

NOTA: Puedes comentar las validaciones de `isAuth()` para probar las funciones o mandar una peticion a `auth.php` con correo y clave como valores, si estas usando `baseFormat.sql` como base las cuentas de prueba estan definidos en el archivo `cuentas.json` si estas ocupando la otra, no me se las cuentas xd.    Te respondera un token ese token lo anadiras como header en post man de esta manera:

name                        content

Authorization               Bearer a34$af4CSt566......

Junto con el JSON en la seccion de `raw`

# Implementacion

Realiza fetch desde React donde necesites los datos y la direccion sera `localhost/ejemplo/archivo.php`
Asegurate que sea una funcion `async` y donde lees los datos especifica la palabra `await`
