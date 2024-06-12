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
    fotografia nvarchar(100),
    telefono nvarchar(10),
    permisos int(11),
    clave nvarchar(255),
    lastUpdatePass date
);

CREATE TABLE Empleado (
    id_empleado int unsigned not null primary key auto_increment,
    id_usuario int unsigned not null,
    departamento nvarchar(45) not null,
    direccion nvarchar(200) not null,
    foreign key (id_usuario) references Usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Invitado (
    id_invitado int unsigned not null primary key auto_increment,
    id_usuario int unsigned not null,
    tipo_identificacion nvarchar(50),
    foreign key (id_usuario) references Usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Dispositivos (
    id_dispositivo int unsigned not null primary key auto_increment,
    no_serie int(11) unsigned unique not null,
    modelo nvarchar(45) not null
);

CREATE TABLE Automovil (
    id_automovil int unsigned not null primary key auto_increment,
    placa nvarchar(10) unique not null,
    color nvarchar(50) not null,
    modelo nvarchar(50) not null
);

CREATE TABLE Junta (
    id_junta int unsigned not null primary key auto_increment,
    id_anfitrion int unsigned not null,
    asunto nvarchar(255) not null,
    sala nvarchar(50) not null,
    fecha date not null,
    hora_inicio time not null,
    hora_fin time not null,
    descripcion text not null,
    direccion text not null,
    foreign key (id_anfitrion) references Empleado(id_empleado) ON DELETE CASCADE,
    UNIQUE (sala, fecha, hora_inicio, id_anfitrion)
);

CREATE TABLE InvitadosPorJunta (
    id_qr int unsigned not null primary key auto_increment,
    id_junta int unsigned not null,
    id_invitado int unsigned not null,
    id_automovil int unsigned,
    entrada time,
    salida time,
    invitado_por int unsigned,
    estado nvarchar(20) not null,
    foreign key (id_junta) references Junta(id_junta) ON DELETE CASCADE,
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

INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, fotografia, telefono, permisos, clave) VALUE ('admin@test.com','Juan','Gonzales','Lopez','Software Legends','./img/1.jpg','5511223344',1,'$2y$10$fyqdXZvd9dPbB8aTwX5hF.4ksPrVBaoiF2HmulUUVg5L6XFG11TXW');

INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, fotografia, telefono, permisos, clave) VALUE ('anfitrion1@test.com','Emma','Miranda','Santiago','Software Legends','./img/2.jpg','5522334455',4,'$2y$10$dUijuMHtrFNf8YUBgUM9luymboLJFGmdLnySYDXiysfiaxDsJDOwW');

INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, fotografia, telefono, permisos, clave) VALUE ('anfitrion2@test.com','Ricardo','Spohn','Hernandez','Software Legends','./img/3.jpg','5533445566',4,'$2y$10$i691/gs1QF7laI/zC66Mp.Z.0v9kzeodYvyF.5te1UE2Y7ggVhzrC');

INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, fotografia, telefono, permisos, clave) VALUE ('recep@test.com','Mario','Ruiz','Garcia','Software Legends','./img/4.jpg','5544556677',3,'$2y$10$OgsS5sBTued.ZP816rZLsezrWfMBB0KFvGK1THeWK9fRvOlfryORy');

INSERT INTO Usuario (correo, nombre, apellido_paterno, apellido_materno, empresa, fotografia, telefono, permisos, clave) VALUE ('invitado@test.com','Saul','Benitez','Rodriguez','Microsoft','./img/5.jpg','5555667788',2,'$2y$10$sfLABNpHZUdtSc8QW/7aP.PrAw06IGDa3S6Oytd76iumYKiUEMrO.');

INSERT INTO Empleado (id_usuario, direccion, departamento) VALUE (2, 'Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.', 'Desarrollo Front-end');

INSERT INTO Empleado (id_usuario, direccion, departamento) VALUE (3, 'Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.', 'Desarrollo Back-end');

INSERT INTO Empleado (id_usuario, direccion, departamento) VALUE (4, 'Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.', 'Recepción');

INSERT INTO Invitado (id_usuario, tipo_identificacion) VALUE (5, 'Pasaporte');

INSERT INTO Junta (id_anfitrion, asunto, sala, fecha, hora_inicio, hora_fin, descripcion, direccion) VALUE (1, "Revisión de Avances de Proyecto", "1113", "2024-06-13", "8:30", "10:00", "Presentación de Avances de Proyecto hechos durante el sprint pasado", "Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.");

INSERT INTO Junta (id_anfitrion, asunto, sala, fecha, hora_inicio, hora_fin, descripcion, direccion) VALUE (1, "Presentación del Proyecto", "1113", "2024-06-17", "8:30", "10:30", "Presentación del Proyecto integrado y funcional al cliente", "Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.");

INSERT INTO Junta (id_anfitrion, asunto, sala, fecha, hora_inicio, hora_fin, descripcion, direccion) VALUE (2, "Negociación de Acuerdo Comercial", "1114", "2024-06-19", "10:30", "12:00", "Negociación de Acuerdo Comercial para la Distribucion del Proyecto", "Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.");

INSERT INTO Junta (id_anfitrion, asunto, sala, fecha, hora_inicio, hora_fin, descripcion, direccion) VALUE (1, "Establecimiento de Requerimientos de nuevo Proyecto", "1113", "2024-07-15", "8:30", "10:00", "Junta para discutir las necesidades a cumplir por el sistema solicitado", "Av. Juan de Dios Bátiz s/n esq. Av. Miguel Othón de Mendizabal. Colonia Lindavista. Alcaldia: Gustavo A. Madero. C. P. 07738. Ciudad de México.");

INSERT INTO Automovil (modelo, color, placa) VALUE ("Sedán", "Rojo", "PCH-96-04");

INSERT INTO InvitadosPorJunta (id_junta, id_invitado, id_automovil, estado) VALUE (1, 1, 1,  "Confirmada");
