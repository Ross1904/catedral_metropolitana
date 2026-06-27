SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `actividades_pastorales`;
CREATE TABLE `actividades_pastorales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_actividad` varchar(150) NOT NULL,
  `categoria` enum('Formación','Grupo Devocional','Reunión') NOT NULL,
  `dias_reunion` varchar(100) NOT NULL,
  `hora_inicio` time NOT NULL,
  `lugar` varchar(100) DEFAULT 'Catedral',
  `id_usuario_encargado` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario_encargado` (`id_usuario_encargado`),
  CONSTRAINT `actividades_pastorales_ibfk_1` FOREIGN KEY (`id_usuario_encargado`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `actividades_pastorales` VALUES ('5', 'Catequesis de primera comunion', 'Formación', 'todos los domingos', '14:00:00', 'Catedral Metropolitana', '1');
INSERT INTO `actividades_pastorales` VALUES ('6', 'Catequesis confirmación', 'Formación', 'miercoles y viernes', '17:00:00', 'Catedral Metropolitana', '1');
INSERT INTO `actividades_pastorales` VALUES ('9', 'Adoradores del Santísimo', 'Grupo Devocional', 'Martes y Sábados', '08:00:00', 'Catedral Metropolitana', '1');

DROP TABLE IF EXISTS `agenda_catedral`;
CREATE TABLE `agenda_catedral` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo_actividad` varchar(150) NOT NULL,
  `tipo_actividad` enum('Boda','Comunión','Bautizo','Misa Especial','Mantenimiento','Otro') NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL,
  `estado` enum('Agendado','Cancelado','Realizado') DEFAULT 'Agendado',
  `id_usuario_registro` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario_registro` (`id_usuario_registro`),
  CONSTRAINT `agenda_catedral_ibfk_1` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agenda_catedral` VALUES ('24', 'Bautizo a Ernestina', 'Bautizo', '2026-06-11 16:00:00', '2026-06-11 17:00:00', 'Agendado', '1');

DROP TABLE IF EXISTS `documentos_actas`;
CREATE TABLE `documentos_actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento` varchar(50) NOT NULL,
  `nombre_principal` varchar(255) NOT NULL,
  `fecha_sacramento` date NOT NULL,
  `datos_json` text DEFAULT NULL,
  `libro` varchar(50) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario_registro` (`id_usuario_registro`),
  CONSTRAINT `documentos_actas_ibfk_1` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `documentos_actas` VALUES ('5', 'Bautismo', 'Elliot Ludwig', '2026-03-13', '{\"fecha_nacimiento\":\"2026-03-05\",\"ciudad_nacimiento\":\"Bolivar\",\"estado_nacimiento\":\"Bolivar\",\"nombre_padre\":\"Harley Sawyer\",\"nombre_madre\":\"Light Pier\",\"nombre_padrino\":\"Huggy Wuggy\",\"nombre_madrina\":\"Kissy Missy\",\"ministro\":\"Candy Cat\"}', '303030030', '8008', '0202', '', '1', '2026-03-27 19:03:18');
INSERT INTO `documentos_actas` VALUES ('9', 'Confirmacion', 'wazaaaa', '2026-03-05', '{\"fecha_nacimiento\":\"2026-03-13\",\"ciudad_nacimiento\":\"n bnm\",\"estado_nacimiento\":\"ghvhv \",\"nombre_padre\":\"gjyt\",\"nombre_madre\":\"chjh \",\"nombre_padrino\":\"hgchjc\",\"nombre_madrina\":\"hvvh\",\"ministro\":\"hjgmfhj\"}', '777777777', '7777', '77', '', '1', '2026-03-28 03:57:48');
INSERT INTO `documentos_actas` VALUES ('10', 'Matrimonio', 'Anthony Maita Y Rosaura Acosta', '2026-03-12', '{\"nombre_esposo\":\"Anthony Maita\",\"estado_civil_esposo\":\"Soltero\",\"edad_esposo\":\"21\",\"viudo_de_esposo\":\"\",\"natural_esposo\":\"Ciudad Bolívar, Estado Bolívar\",\"padre_esposo\":\"José Maita\",\"madre_esposo\":\"Lauris Viera\",\"nombre_esposa\":\"Rosaura Acosta\",\"estado_civil_esposa\":\"Soltera\",\"edad_esposa\":\"20\",\"viuda_de_esposa\":\"\",\"natural_esposa\":\"Caracas, Distrito Capital\",\"padre_esposa\":\"Oswaldo Acosta\",\"madre_esposa\":\"Mayra Requena\",\"testigos\":\"Richel Santaella, Jannelys Zarraga, Xavier Capriles\",\"ministro\":\"Enmanuel Rodriguez\"}', '19041904', '1904', '19', '', '1', '2026-03-30 20:15:49');
INSERT INTO `documentos_actas` VALUES ('11', 'Bautismo', 'Juan Pablo Maritnez', '2026-04-06', '{\"fecha_nacimiento\":\"2005-04-14\",\"ciudad_nacimiento\":\"Ciudad Bolivar\",\"estado_nacimiento\":\"Bolivar\",\"nombre_padre\":\"Ernesto\",\"nombre_madre\":\"Lola\",\"nombre_padrino\":\"Domingo\",\"nombre_madrina\":\"Carmen\",\"ministro\":\"Ramon\"}', '12', '2', '12', '', '1', '2026-04-06 09:33:51');

DROP TABLE IF EXISTS `donaciones`;
CREATE TABLE `donaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_donacion` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `donante` varchar(100) DEFAULT 'Anónimo',
  `metodo_pago` varchar(50) DEFAULT 'N/A',
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` varchar(50) DEFAULT 'N/A',
  `referencia` varchar(50) DEFAULT 'N/A',
  `fecha_donacion` date NOT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_donaciones_usuario` (`id_usuario_registro`),
  CONSTRAINT `fk_donaciones_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `donaciones` VALUES ('17', 'Suministro', '0.00', 'Herrera', 'N/A', 'Bolsas de comida', '10', 'N/A', '2026-03-09', '5');
INSERT INTO `donaciones` VALUES ('24', 'Monetaria', '199.00', 'pedro', 'Efectivo', 'Aporte Económico', 'N/A', 'N/A', '2026-04-06', '1');

DROP TABLE IF EXISTS `preguntas_seguridad`;
CREATE TABLE `preguntas_seguridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `pregunta` varchar(255) NOT NULL,
  `respuesta` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `preguntas_seguridad_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preguntas_seguridad` VALUES ('41', '1', '¿Nombre de tu escuela primaria?', '$2y$10$8d1fCZe4FmQGh.so64HVZek.OpMububTsb.xcrXXjdnQ239yUExaC');
INSERT INTO `preguntas_seguridad` VALUES ('42', '1', '¿Marca de tu primer teléfono?', '$2y$10$hMglPaIwPjSOlL3PNJFK8eGDZFSFC/tPRg.Bu6YsW219FyS.PyiGi');
INSERT INTO `preguntas_seguridad` VALUES ('43', '1', '¿Nombre de tu mejor amigo?', '$2y$10$jUmjWOw3RRSrxdBzeT1drO58/ifyUu6.Yc.sCB.qfKXawkuNharU.');
INSERT INTO `preguntas_seguridad` VALUES ('44', '1', '¿Ciudad donde naciste?', '$2y$10$XfgKS9.ApIOIDT6H/IjhJeCVpjxZiIg3fARUuruZwv8jefFjmTZpW');
INSERT INTO `preguntas_seguridad` VALUES ('49', '5', '¿Nombre de tu primera mascota?', '$2y$10$ADBuW6Ft/wsKNXKrT6u0OuaL5cLIO4GqxTVJPepdZ4WHViSx4a6Dm');
INSERT INTO `preguntas_seguridad` VALUES ('50', '5', '¿Ciudad donde naciste?', '$2y$10$Rw9bQkQEWbibY56EfdDRtuULfKoZAQvlBQGMaD0woFIkI7F.VwrJ2');
INSERT INTO `preguntas_seguridad` VALUES ('51', '5', '¿Nombre de tu escuela primaria?', '$2y$10$iN6vO/uOOcd9ZM7WtKphYOvphVuAboq28IkFsKk0dNvrvzmFmZBXW');
INSERT INTO `preguntas_seguridad` VALUES ('52', '5', '¿Tu color favorito de niño?', '$2y$10$RKfYFw4QxIOvhiGPd17vaeClR9eXjJ2NXc8hfmjkQDTPr9bQb/Hk6');
INSERT INTO `preguntas_seguridad` VALUES ('53', '11', '¿Nombre de tu primera mascota?', '$2y$10$I7mP8gD4eC80AWXFzJ3jUuWGHnnAD0UahIgeValuuk7G60h3tnV4u');
INSERT INTO `preguntas_seguridad` VALUES ('54', '11', '¿Tu color favorito de niño?', '$2y$10$4wT6cwCjK9UUEuQq5I9U9.HyDY0iv3FQq8UlEEVtVDnS3ItxEyIDq');
INSERT INTO `preguntas_seguridad` VALUES ('55', '11', '¿Año en que te graduaste?', '$2y$10$K4I9HCVDMC8Xp0M2BwNWneaFQV41PS1wzCXRNZ8Iiy2HgvTPymNIK');
INSERT INTO `preguntas_seguridad` VALUES ('56', '11', '¿Nombre de tu mejor amigo?', '$2y$10$/NAqU8xRPK4WvGvYlDOBou7TIbTnHlSlOLaatB0Ay3iYovqtfh1jC');
INSERT INTO `preguntas_seguridad` VALUES ('61', '13', '¿Nombre de tu primera mascota?', '$2y$10$44Qr33lQtCw46Z4V1OtJ0.5bem2XiUwX2yckJG335DHFEe9QwOvAi');
INSERT INTO `preguntas_seguridad` VALUES ('62', '13', '¿Nombre de tu escuela primaria?', '$2y$10$tZiK9tK5YKWXyMfbDvU5ZuUxfbPYOpvlLdEjWExMHp1cp5PL0BIES');
INSERT INTO `preguntas_seguridad` VALUES ('63', '13', '¿Ciudad donde naciste?', '$2y$10$1CR56ncIFfEUV0XqYlyDtujy8mrzm7Yheg1Sk6U7JYz6arDtHK4PC');
INSERT INTO `preguntas_seguridad` VALUES ('64', '13', '¿Tu color favorito de niño?', '$2y$10$IqyFccbXJA2OjPOaJwcmBeGMV7uhMGebgJz/xhJmIVPAwpP9UPaQW');

DROP TABLE IF EXISTS `registro_actividad`;
CREATE TABLE `registro_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` text NOT NULL,
  `fecha_accion` datetime NOT NULL DEFAULT current_timestamp(),
  `visto` text DEFAULT NULL,
  `eliminado_por` text DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `fk_registro_usuario` (`id_usuario`),
  CONSTRAINT `fk_registro_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `registro_actividad` VALUES ('78', '1', 'Agenda', 'Canceló/Eliminó el evento: hijo de los hernandez', '2026-03-08 23:54:12', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('79', '1', 'Donaciones', 'Registró donación (Monetaria) de sssss', '2026-03-09 00:06:02', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('80', '5', 'Donaciones', 'Eliminó la donación (Monetaria) de sssss', '2026-03-09 00:06:31', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('81', '1', 'Actividades Pastorales', 'Registró nueva actividad: pato', '2026-03-09 00:19:00', ',5,1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('82', '1', 'Actividades Pastorales', 'Eliminó la actividad: pato', '2026-03-09 00:19:05', ',5,1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('83', '1', 'Sistema', 'Restauración profunda de Base de Datos completada', '2026-03-09 00:23:01', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('84', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-09 00:27:58', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('85', '1', 'Agenda', 'Agendó nuevo evento: aaaaa', '2026-03-09 00:29:43', ',1,5', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('86', '1', 'Sistema', 'Restauración de base de datos ejecutada (Usuarios protegidos)', '2026-03-09 00:32:05', ',1,5', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('87', '1', 'Agenda', 'Agendó nuevo evento: besitos con panquecito', '2026-03-09 09:19:38', ',1,5', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('88', '5', 'Agenda', 'Agendó nuevo evento: Lois hernandez', '2026-03-09 11:21:24', ',5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('89', '5', 'Actividades Pastorales', 'Registró nueva actividad: Adoradores del santisimo', '2026-03-09 11:23:43', ',5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('90', '5', 'Donaciones', 'Registró donación (Suministro) de Herrera', '2026-03-09 11:25:17', ',5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('91', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-09 11:28:27', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('92', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: Ramon', '2026-03-09 11:28:40', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('95', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-10 14:47:52', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('96', '1', 'Agenda', 'Actualizó evento de agenda: besitos con panquecito', '2026-03-11 14:11:49', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('97', '1', 'Agenda', 'Canceló/Eliminó el evento: besitos con panquecito', '2026-03-11 14:11:57', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('98', '1', 'Agenda', 'Agendó nuevo evento: wawa', '2026-03-11 14:12:20', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('99', '1', 'Donaciones', 'Registró donación (Monetaria) de Anónimo', '2026-03-11 14:49:48', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('100', '1', 'Actividades Pastorales', 'Eliminó la actividad: Adoradores del santisimo', '2026-03-11 15:30:27', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('101', '1', 'Agenda', 'Canceló/Eliminó el evento: wawa', '2026-03-11 15:31:32', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('102', '1', 'Sistema', 'Restauración completa de base de datos ejecutada', '2026-03-11 15:54:22', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('103', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-11 15:54:44', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('104', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de Anónimo', '2026-03-11 16:01:59', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('105', '1', 'Donaciones', 'Registró donación (Monetaria) de Anónimo', '2026-03-11 16:02:03', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('106', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de Anónimo', '2026-03-11 16:02:08', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('107', '1', 'Donaciones', 'Registró donación (Monetaria) de Anónimo', '2026-03-11 16:09:54', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('108', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de Anónimo', '2026-03-11 16:09:57', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('109', '1', 'Donaciones', 'Registró donación (Monetaria) de ggg', '2026-03-11 16:10:15', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('110', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de ggg', '2026-03-11 16:10:19', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('111', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-11 16:33:57', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('112', '1', 'Sistema', 'Restauración completa de base de datos ejecutada', '2026-03-11 16:36:26', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('113', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-03-11 16:37:18', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('115', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 09:47:22', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('116', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 10:06:31', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('117', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 11:20:45', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('118', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 11:31:18', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('119', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 11:31:32', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('120', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 11:31:43', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('121', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 11:47:41', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('122', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 11:47:54', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('123', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 11:48:03', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('124', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 11:48:12', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('125', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-12 11:48:17', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('126', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 11:55:09', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('127', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-03-12 11:56:25', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('128', '1', 'Agenda', 'Agendó nuevo evento: misa de cumple', '2026-03-12 20:32:39', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('129', '1', 'Donaciones', 'Registró donación (Monetaria) de trump', '2026-03-12 20:34:36', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('130', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: ana', '2026-03-12 20:39:22', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('131', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: lupe', '2026-03-12 20:40:49', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('132', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-03-12 20:43:48', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('133', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-12 20:44:13', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('134', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: lupe', '2026-03-12 20:47:31', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('135', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de trump', '2026-03-16 08:11:33', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('136', '1', 'Agenda', 'Canceló/Eliminó el evento: misa de cumple', '2026-03-16 08:11:56', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('137', '1', 'Agenda', 'Agendó nuevo evento: jesus', '2026-03-16 12:38:05', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('138', '1', 'Agenda', 'Canceló/Eliminó el evento: jesus', '2026-03-27 09:55:06', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('139', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis confirmacion', '2026-03-27 12:23:05', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('140', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: lupe', '2026-03-27 12:24:22', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('141', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis confirmacion', '2026-03-27 13:01:25', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('142', '1', 'Agenda', 'Actualizó evento de agenda: Lois hernandez', '2026-03-27 14:11:33', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('143', '1', 'Agenda', 'Actualizó evento de agenda: Lois hernandez', '2026-03-27 15:39:06', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('144', '1', 'Donaciones', 'Registró donación (Monetaria) de Anónimo', '2026-03-27 18:01:43', ',1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('145', '1', 'Archivos', 'Eliminó un acta de Bautismo perteneciente a: Elliot Ludwig', '2026-03-27 19:02:13', '0,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('146', '1', 'Archivos', 'Emitió una nueva Partida de Bautismo para: Elliot Ludwig', '2026-03-27 19:03:18', ',1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('147', '1', 'Archivos', 'Emitió una nueva Partida de Bautismo para: wawawa', '2026-03-27 19:22:25', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('148', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: wawawa', '2026-03-27 21:13:41', '0,1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('149', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: wawawa', '2026-03-27 21:13:58', '0,1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('150', '1', 'Perfil', 'Actualizó sus preguntas de seguridad.', '2026-03-27 21:25:53', ',1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('151', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: lupe', '2026-03-27 23:35:36', ',1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('152', '5', 'Archivos', 'Emitió una nueva Partida de Bautismo para: 11', '2026-03-27 23:37:33', ',5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('153', '1', 'Archivos', 'Eliminó un acta de Bautismo perteneciente a: 11', '2026-03-27 23:37:57', '0,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('154', '1', 'Archivos', 'Eliminó un acta de Bautismo perteneciente a: wawawa', '2026-03-27 23:50:07', '0,1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('155', '1', 'Archivos', 'Emitió una nueva Partida de Bautismo para: hahah.k', '2026-03-27 23:58:54', ',1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('156', '5', 'Archivos', 'Eliminó un acta de Bautismo perteneciente a: hahah.k', '2026-03-27 23:59:15', '0,5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('157', '1', 'Usuarios', 'Suspendió el acceso de un usuario en el sistema.', '2026-03-28 03:26:20', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('158', '1', 'Usuarios', 'Restauró el acceso de un usuario en el sistema.', '2026-03-28 03:27:20', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('159', '1', 'Usuarios', 'Restauró el acceso de un usuario en el sistema.', '2026-03-28 03:27:21', ',1', ',1,5,2');
INSERT INTO `registro_actividad` VALUES ('161', '5', 'Perfil', 'Actualizó sus preguntas de seguridad.', '2026-03-28 03:29:40', ',5,1', ',5,1,2');
INSERT INTO `registro_actividad` VALUES ('162', '1', 'Archivos', 'Registró una nueva Partida de Confirmación a nombre de: cghm', '2026-03-28 03:57:48', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('163', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-03-30 12:01:48', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('164', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-30 12:01:56', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('165', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: Elliot Ludwig', '2026-03-30 16:49:34', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('166', '1', 'Archivos', 'Editó y actualizó la Partida de Confirmación de: wazaaaa', '2026-03-30 18:01:14', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('167', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: Elliot Ludwig', '2026-03-30 18:02:24', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('168', '1', 'Archivos', 'Registró Matrimonio: Anthony Maita Y Rosaura Acosta', '2026-03-30 20:15:49', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('169', '1', 'Archivos', 'Editó y actualizó la Partida de Matrimonio de: Anthony Maita Y Rosaura Acosta', '2026-03-30 20:34:42', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('170', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-03-30 21:12:26', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('171', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-03-30 21:12:34', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('172', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de Anónimo', '2026-03-30 23:03:55', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('173', '1', 'Agenda', 'Canceló/Eliminó el evento: Lois hernandez', '2026-04-03 18:39:28', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('174', '1', 'Archivos', 'Emitió una nueva Partida de Bautismo para: Juan Pablo Maritnez', '2026-04-06 09:33:51', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('175', '1', 'Agenda', 'Agendó nuevo evento: lolo', '2026-04-06 09:35:46', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('176', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 09:36:24', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('177', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis confirmacion', '2026-04-06 09:38:46', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('178', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis de primera comunion', '2026-04-06 09:38:58', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('179', '1', 'Donaciones', 'Registró donación (Monetaria) de juan', '2026-04-06 09:41:39', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('180', '1', 'Donaciones', 'Actualizó donación ID: 24', '2026-04-06 09:43:07', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('181', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 15:23:48', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('182', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 15:34:16', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('183', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 15:34:34', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('184', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 15:34:50', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('185', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 15:44:24', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('186', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis de primera comunion', '2026-04-06 15:59:28', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('187', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis confirmacion', '2026-04-06 15:59:48', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('188', '1', 'Donaciones', 'Actualizó donación ID: 24', '2026-04-06 16:00:28', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('189', '1', 'Usuarios', 'Suspendió el acceso de un usuario en el sistema.', '2026-04-06 16:00:50', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('190', '1', 'Usuarios', 'Restauró el acceso de un usuario en el sistema.', '2026-04-06 16:01:29', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('191', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-04-06 16:01:47', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('192', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-04-06 16:02:05', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('193', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-04-06 18:15:22', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('194', '1', 'Agenda', 'Agendó nuevo evento: Arreglos eclesiásticos', '2026-04-13 16:28:15', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('195', '1', 'Agenda', 'Canceló/Eliminó el evento: Arreglos eclesiásticos', '2026-04-13 16:32:53', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('196', '1', 'Agenda', 'Agendó nuevo evento: Arreglos Eclesiásticos', '2026-04-13 16:35:11', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('197', '1', 'Donaciones', 'Registró donación (Monetaria) de Jose', '2026-04-22 11:08:50', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('198', '1', 'Donaciones', 'Registró donación (Suministro) de holl', '2026-04-22 11:09:26', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('199', '1', 'Donaciones', 'Registró donación (Otros) de anthony', '2026-04-22 11:13:42', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('200', '1', 'Donaciones', 'Registró donación (Otros) de Kill', '2026-04-22 11:15:10', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('201', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-05-14 20:28:04', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('202', '1', 'Agenda', 'Actualizó evento de agenda: lolo', '2026-05-22 15:19:38', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('203', '1', 'Agenda', 'Canceló/Eliminó el evento: lolo', '2026-05-22 15:19:47', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('204', '1', 'Donaciones', 'Eliminó la donación (Otros) de Kill', '2026-05-22 15:20:19', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('205', '1', 'Donaciones', 'Eliminó la donación (Otros) de anthony', '2026-05-22 15:20:25', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('206', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de pepe', '2026-05-22 15:20:32', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('207', '1', 'Donaciones', 'Eliminó la donación (Suministro) de holl', '2026-05-22 15:20:37', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('208', '1', 'Agenda', 'Canceló/Eliminó el evento: Arreglos Eclesiásticos', '2026-05-26 08:31:43', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('209', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-05-26 08:37:29', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('210', '1', 'Sistema', 'Creó un respaldo automático en la bóveda del servidor', '2026-05-26 08:37:36', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('211', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-05-26 08:37:43', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('212', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: ana', '2026-05-26 09:21:32', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('213', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: Juan Pablo Maritnez', '2026-05-26 11:01:45', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('214', '1', 'Archivos', 'Editó y actualizó la Partida de Bautismo de: Juan Pablo Maritnez', '2026-05-26 11:02:00', '0,1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('215', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de Jose', '2026-05-26 11:02:26', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('216', '1', 'Usuarios', 'Eliminó a un usuario del sistema', '2026-06-04 18:20:39', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('217', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: admin', '2026-06-04 20:13:24', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('218', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: admin', '2026-06-04 20:13:35', ',1', ',1,2');
INSERT INTO `registro_actividad` VALUES ('219', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: lupe', '2026-06-04 20:19:39', ',1', ',2,1');
INSERT INTO `registro_actividad` VALUES ('220', '1', 'Actividades Pastorales', 'Registró nueva actividad: Adoradores del Santísimo', '2026-06-04 20:23:02', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('221', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis de primera comunion', '2026-06-04 20:23:30', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('222', '1', 'Agenda', 'Agendó nuevo evento: Bautizo a Ernestina', '2026-06-04 21:07:20', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('223', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-06-05 13:11:47', '', ',1');
INSERT INTO `registro_actividad` VALUES ('224', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-06-05 13:28:48', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('225', '1', 'Actividades Pastorales', 'Editó la actividad: Catequesis confirmación', '2026-06-09 13:51:57', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('226', '1', 'Donaciones', 'Actualizó donación ID: 24', '2026-06-09 13:52:08', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('227', '1', 'Usuarios', 'Modificó el perfil y nivel de acceso del usuario: Dai', '2026-06-09 13:57:19', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('228', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-06-09 14:19:58', '', ',1');
INSERT INTO `registro_actividad` VALUES ('229', '1', 'Donaciones', 'Registró donación (Monetaria) de enmanuel', '2026-06-09 14:21:53', '', ',1');
INSERT INTO `registro_actividad` VALUES ('230', '1', 'Donaciones', 'Eliminó la donación (Monetaria) de enmanuel', '2026-06-09 14:22:18', '', ',1');
INSERT INTO `registro_actividad` VALUES ('231', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-06-27 11:51:29', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('232', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-06-27 12:01:07', ',1', ',1');
INSERT INTO `registro_actividad` VALUES ('233', '1', 'Sistema', 'Restauró el sistema desde la bóveda del servidor', '2026-06-27 12:28:57', '', '');
INSERT INTO `registro_actividad` VALUES ('234', '1', 'Sistema', 'Eliminó un archivo de respaldo del servidor', '2026-06-27 12:33:44', '', '');
INSERT INTO `registro_actividad` VALUES ('235', '1', 'Sistema', 'Creó un nuevo respaldo en la bóveda del servidor', '2026-06-27 12:42:52', '', '');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` VALUES ('3', 'Administrador');
INSERT INTO `roles` VALUES ('1', 'Ciudadano');
INSERT INTO `roles` VALUES ('2', 'Secretario');

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `id_rol` int(11) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` VALUES ('1', 'Rosaura', 'admin', '$2y$10$cQ75ruLHjSIGo/KKinI64u8BsuB5HsEK6oUkgQDsPoIN/JyT.kYW6', '3', '2026-03-07 16:35:42', 'Activo');
INSERT INTO `usuarios` VALUES ('5', 'ana', 'ana', '$2y$10$OY0M8xIKK9PuO4AgcrYtlOPIK.wfBqZkg8gF9ossngc.sxPQHfSc2', '2', '2026-03-08 05:26:24', 'Activo');
INSERT INTO `usuarios` VALUES ('11', 'Dailenis Betzabeth', 'Dai', '$2y$10$getU1m6mmUUFhNhO0oJDJe/sVGTFGc3X5DAx28zRw1P8stjLvxOeO', '2', '2026-05-14 20:12:30', 'Activo');
INSERT INTO `usuarios` VALUES ('13', 'lupe', 'lupe', '$2y$10$F2uqbXKBPG8mFQj4EY12HeRNs25FMvZDw7mMrG8xiKpU3183zU3i6', '1', '2026-06-04 20:20:44', 'Activo');

SET FOREIGN_KEY_CHECKS=1;
