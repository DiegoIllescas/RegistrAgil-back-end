DROP DATABASE IF EXISTS registragil;

CREATE DATABASE registragil DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE registragil;

CREATE TABLE Usuario (
    id_usuario int unsigned not null primary key auto_increment,
    correo nvarchar(45) unique not null,
    nombre nvarchar(50),
    apellido_paterno nvarchar(50),
    apellido_materno nvarchar(50),
    empresa nvarchar(45),
    fotografia nvarchar(45),
    telefono nvarchar(10),
    permisos int(11),
    clave nvarchar(60)
);

CREATE TABLE Empleado (
    id_empleado int unsigned not null primary key auto_increment,
    id_usuario int unsigned not null,
    departamento nvarchar(45) not null,
    direccion nvarchar(200) not null,
    foreign key (id_usuario) references Usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Invitado () {
    id_invitado int unsigned not null primary key auto_increment,
    id_usuario int unsigned not null,
    tipo_identificacion nvarchar(50),
    foreign key (id_usuario) references Usuario(id_usuario) ON DELETE CASCADE
}

CREATE TABLE Dispositivos (
    id_dispositivo int unsigned not null primary key auto_increment,
    no_serie int(11) unique unsigned not null,
    modelo nvarchar(45) not null,
);

CREATE TABLE Automovil () {
    id_automovil int unsigned not null primary key auto_increment,
    placa nvarchar(10) unique not null,
    color nvarchar(50) not null,
    modelo nvarchar(50) not null
}

CREATE TABLE Junta (
    id_junta int unsigned not null primary key auto_increment,
    id_anfitrion int unsigned not null,
    asunto nvarchar(50) not null,
    sala nvarchar(50) not null,
    fecha date not null,
    hora_inicio time not null,
    hora_fin time not null,
    descripcion text not null,
    foreign key (id_anfitrion) references Empleado(id_empleado)
);

CREATE TABLE InvitadosPorJunta (
    id_qr int unsigned not null primary key auto_increment,
    id_junta int unsigned not null,
    id_invitado int unsigned not null,
    id_automovil int unsigned,
    entrada time,
    salida time,
    invitado_por int unsigned,
    estado nvarchar(10) not null,
    foreign key (id_junta) references Junta(id_junta),
    foreign key (id_invitado) references Invitado(id_invitado),
    foreign key (id_automovil) references Automovil(id_automovil),
    foreign key (invitado_por) references Invitado(id_invitado)
);

CREATE TABLE DispositivosPorReunion (
    id_qr int unsigned not null,
    id_dispositivo int unsigned not null,
    foreign key (id_qr) references InvitadosPorJunta(id_qr),
    foreign key (id_dispositivo) references Dispositivos(id_dispositivo)
);