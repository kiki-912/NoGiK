-- =========================================================================
-- Base de datos para NogiK - Plataforma DJ
-- Autor: Antigravity AI
-- Fecha de Generación: 2026-07-09
-- =========================================================================

CREATE DATABASE IF NOT EXISTS `nogik_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nogik_db`;

-- Desactivar temporalmente restricciones de clave externa para recreación limpia
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `student_completed_classes`;
DROP TABLE IF EXISTS `student_skills`;
DROP TABLE IF EXISTS `teacher_specialties`;
DROP TABLE IF EXISTS `evaluations`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `sets`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `tiers`;
DROP TABLE IF EXISTS `events`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. TABLA: TIERS (Rangos de Reputación)
-- =========================================================================
CREATE TABLE `tiers` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `min_reputation` INT NOT NULL,
  `max_reputation` INT NOT NULL,
  `color` VARCHAR(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 2. TABLA: SKILLS (Habilidades de DJ)
-- =========================================================================
CREATE TABLE `skills` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 3. TABLA: USERS (Profesores y Alumnos)
-- =========================================================================
CREATE TABLE `users` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Nota: Se almacena texto plano temporal para compatibilidad, usar hash en producción
  `role` ENUM('student', 'teacher') NOT NULL,
  `avatar` VARCHAR(10) DEFAULT NULL, -- Emojis o iniciales del usuario
  -- Campos específicos para Profesores
  `bio` TEXT DEFAULT NULL,
  -- Campos específicos para Alumnos
  `level` INT DEFAULT 1,
  `xp` INT DEFAULT 0,
  `xp_to_next_level` INT DEFAULT 500,
  `reputation` INT DEFAULT 0,
  `total_sets` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 4. TABLA: TEACHER_SPECIALTIES (Especialidades Musicales del Profesor)
-- =========================================================================
CREATE TABLE `teacher_specialties` (
  `teacher_id` VARCHAR(50) NOT NULL,
  `specialty` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`teacher_id`, `specialty`),
  CONSTRAINT `fk_teacher_specialties_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 5. TABLA: STUDENT_SKILLS (Nivel y Progreso de Habilidad del Alumno)
-- =========================================================================
CREATE TABLE `student_skills` (
  `student_id` VARCHAR(50) NOT NULL,
  `skill_id` VARCHAR(50) NOT NULL,
  `level` INT DEFAULT 0,
  `status` ENUM('not-started', 'in-progress', 'completed') DEFAULT 'not-started',
  `xp` INT DEFAULT 0,
  `xp_to_next_level` INT DEFAULT 200,
  PRIMARY KEY (`student_id`, `skill_id`),
  CONSTRAINT `fk_student_skills_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 6. TABLA: CLASSES (Clases de Formación)
-- =========================================================================
CREATE TABLE `classes` (
  `id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `class_date` DATETIME NOT NULL,
  `duration` INT NOT NULL, -- Duración en minutos
  `status` ENUM('completed', 'upcoming') NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_classes_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 7. TABLA: STUDENT_COMPLETED_CLASSES (Historial de Clases de Alumnos)
-- =========================================================================
CREATE TABLE `student_completed_classes` (
  `student_id` VARCHAR(50) NOT NULL,
  `class_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`student_id`, `class_id`),
  CONSTRAINT `fk_completed_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_completed_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 8. TABLA: SETS (Sets Musicales Subidos por Alumnos)
-- =========================================================================
CREATE TABLE `sets` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `url` VARCHAR(255) NOT NULL,
  `genre` VARCHAR(100) NOT NULL,
  `duration` INT NOT NULL, -- Duración en minutos
  `uploaded_at` DATE NOT NULL,
  `comments_count` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sets_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 9. TABLA: EVALUATIONS (Evaluaciones Realizadas por Profesores)
-- =========================================================================
CREATE TABLE `evaluations` (
  `set_id` VARCHAR(50) NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `technique` INT NOT NULL CHECK (`technique` BETWEEN 1 AND 10),
  `coherence` INT NOT NULL CHECK (`coherence` BETWEEN 1 AND 10),
  `creativity` INT NOT NULL CHECK (`creativity` BETWEEN 1 AND 10),
  `adaptation` INT NOT NULL CHECK (`adaptation` BETWEEN 1 AND 10),
  `overall_score` DECIMAL(4,2) NOT NULL,
  `feedback` TEXT DEFAULT NULL,
  `xp_awarded` INT NOT NULL,
  `reputation_change` INT NOT NULL,
  PRIMARY KEY (`set_id`),
  CONSTRAINT `fk_evaluations_set` FOREIGN KEY (`set_id`) REFERENCES `sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluations_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- 10. TABLA: EVENTS (Eventos Simulados)
-- =========================================================================
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `venue` VARCHAR(100) NOT NULL,
  `audience` INT NOT NULL,
  `duration` INT NOT NULL, -- Duración en minutos
  `styles` VARCHAR(255) NOT NULL,
  `payment` DECIMAL(10,2) NOT NULL,
  `difficulty` VARCHAR(50) NOT NULL,
  `required_reputation` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- DATOS SEMILLA / INSERCIONES DE EJEMPLO
-- =========================================================================

-- Inserción en `tiers`
INSERT INTO `tiers` (`id`, `name`, `min_reputation`, `max_reputation`, `color`) VALUES
('bedroom', 'Bedroom DJ', 0, 20, '#CD7F32'),
('warmup', 'Warm-up DJ', 21, 40, '#C0C0C0'),
('resident', 'Residente', 41, 60, '#FFD700'),
('headliner', 'Headliner', 61, 80, '#E5E4E2'),
('festival', 'Festival Star', 81, 100, '#B9F2FF');

-- Inserción en `skills`
INSERT INTO `skills` (`id`, `name`, `description`, `category`) VALUES
('beatmatching', 'Beatmatching', 'Sincronizacion precisa de BPMs y fases entre tracks', 'Tecnica'),
('loops-effects', 'Loops y Efectos', 'Uso creativo de loops, samples y efectos', 'Creativa'),
('transitions', 'Transiciones', 'Fluidez y tecnica en el paso entre canciones', 'Tecnica'),
('creativity', 'Creatividad', 'Originalidad en seleccion y combinacion de tracks', 'Creativa'),
('energy-management', 'Manejo de Energia', 'Control del flow y energia del set', 'Performance');

-- Inserción en `users` (Profesores y Alumnos)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `bio`, `level`, `xp`, `xp_to_next_level`, `reputation`, `total_sets`) VALUES
-- Profesores
('teacher-1', 'Carlos Mendoza', 'carlos@nogik.com', 'teacher123', 'teacher', 'CM', '15 anos de experiencia como DJ residente en clubs de Ibiza y Berlin.', NULL, NULL, NULL, NULL, NULL),
('teacher-2', 'Ana Rivera', 'ana@nogik.com', 'teacher123', 'teacher', 'AR', 'Productora y DJ con releases en sellos internacionales.', NULL, NULL, NULL, NULL, NULL),
-- Alumnos
('student-1', 'Demo Student', 'demo@nogik.com', 'demo123', 'student', '👩🏻', NULL, 4, 1850, 2000, 45, 5),
('student-2', 'Miguel Torres', 'miguel@email.com', 'nogik123', 'student', '🧑🏼‍🎤', NULL, 6, 3200, 3500, 62, 8),
('student-3', 'Laura Sanchez', 'laura@email.com', 'nogik123', 'student', '👨🏻', NULL, 2, 450, 750, 18, 2),
('student-4', 'Diego Ramirez', 'diego@email.com', 'nogik123', 'student', '🧔🏾', NULL, 8, 5800, 6000, 78, 12),
('student-5', 'Sofia Herrera', 'sofia@email.com', 'nogik123', 'student', '👩🏾', NULL, 1, 120, 500, 5, 0);

-- Inserción en `teacher_specialties`
INSERT INTO `teacher_specialties` (`teacher_id`, `specialty`) VALUES
('teacher-1', 'Techno'),
('teacher-1', 'Minimal'),
('teacher-1', 'Tech House'),
('teacher-2', 'House'),
('teacher-2', 'Deep House'),
('teacher-2', 'Disco');

-- Inserción en `student_skills`
INSERT INTO `student_skills` (`student_id`, `skill_id`, `level`, `status`, `xp`, `xp_to_next_level`) VALUES
-- student-1
('student-1', 'beatmatching', 4, 'completed', 450, 500),
('student-1', 'loops-effects', 3, 'in-progress', 280, 400),
('student-1', 'transitions', 3, 'in-progress', 350, 400),
('student-1', 'creativity', 2, 'in-progress', 180, 300),
('student-1', 'energy-management', 2, 'in-progress', 150, 300),
-- student-2
('student-2', 'beatmatching', 5, 'completed', 500, 500),
('student-2', 'loops-effects', 4, 'completed', 480, 500),
('student-2', 'transitions', 4, 'in-progress', 420, 500),
('student-2', 'creativity', 3, 'in-progress', 320, 400),
('student-2', 'energy-management', 3, 'in-progress', 280, 400),
-- student-3
('student-3', 'beatmatching', 2, 'in-progress', 150, 300),
('student-3', 'loops-effects', 1, 'in-progress', 80, 200),
('student-3', 'transitions', 1, 'in-progress', 100, 200),
('student-3', 'creativity', 1, 'not-started', 50, 200),
('student-3', 'energy-management', 1, 'not-started', 30, 200),
-- student-4
('student-4', 'beatmatching', 5, 'completed', 500, 500),
('student-4', 'loops-effects', 5, 'completed', 500, 500),
('student-4', 'transitions', 5, 'completed', 500, 500),
('student-4', 'creativity', 4, 'in-progress', 420, 500),
('student-4', 'energy-management', 4, 'in-progress', 380, 500),
-- student-5
('student-5', 'beatmatching', 1, 'in-progress', 60, 200),
('student-5', 'loops-effects', 0, 'not-started', 0, 200),
('student-5', 'transitions', 0, 'not-started', 20, 200),
('student-5', 'creativity', 0, 'not-started', 0, 200),
('student-5', 'energy-management', 0, 'not-started', 0, 200);

-- Inserción en `classes` (se agregan clases 4, 5 y 6 para integridad del historial de alumnos)
INSERT INTO `classes` (`id`, `title`, `description`, `teacher_id`, `class_date`, `duration`, `status`) VALUES
('class-1', 'Fundamentos del Beatmatching', 'Aprende las bases de la sincronizacion de BPMs usando los oidos y el pitch fader.', 'teacher-1', '2024-10-01 18:00:00', 90, 'completed'),
('class-2', 'Uso Creativo de Loops', 'Tecnicas para crear loops en vivo y usarlos creativamente durante el mix.', 'teacher-2', '2024-10-08 18:00:00', 90, 'completed'),
('class-3', 'Transiciones Fluidas', 'Domina corte, fade, EQ mixing y mas.', 'teacher-1', '2024-10-15 18:00:00', 120, 'completed'),
('class-4', 'Estructura Musical y Fraseo', 'Comprende el compás, las secciones de un track y cómo alinear los beats correctamente.', 'teacher-2', '2024-10-22 18:00:00', 90, 'completed'),
('class-5', 'Ecualización y Mezcla de Frecuencias', 'Uso avanzado de aisladores, kill switches y curvas de ecualización en mesa.', 'teacher-1', '2024-10-29 18:00:00', 90, 'completed'),
('class-6', 'Introducción al Marketing para DJs', 'Construye tu marca personal, redes sociales y relaciones públicas en la escena local.', 'teacher-2', '2024-11-05 18:00:00', 120, 'completed'),
('class-7', 'Tecnicas Avanzadas de Scratching', 'Introduccion al scratching y tecnicas turntablistas basicas.', 'teacher-1', '2024-11-12 18:00:00', 120, 'upcoming'),
('class-8', 'Masterclass: Warm-up Sets', 'El arte de abrir una noche con seleccion musical y manejo de energia inicial.', 'teacher-2', '2024-11-19 18:00:00', 90, 'upcoming');

-- Inserción en `student_completed_classes` (Relación M:N)
INSERT INTO `student_completed_classes` (`student_id`, `class_id`) VALUES
-- student-1
('student-1', 'class-1'),
('student-1', 'class-2'),
('student-1', 'class-3'),
-- student-2
('student-2', 'class-1'),
('student-2', 'class-2'),
('student-2', 'class-3'),
('student-2', 'class-4'),
('student-2', 'class-5'),
-- student-3
('student-3', 'class-1'),
-- student-4
('student-4', 'class-1'),
('student-4', 'class-2'),
('student-4', 'class-3'),
('student-4', 'class-4'),
('student-4', 'class-5'),
('student-4', 'class-6');

-- Inserción en `sets`
INSERT INTO `sets` (`id`, `student_id`, `title`, `description`, `url`, `genre`, `duration`, `uploaded_at`, `comments_count`) VALUES
('set-1', 'student-1', 'Tech House Journey', 'Mi primer set completo de tech house, enfocado en transiciones limpias.', 'https://soundcloud.com/example/tech-house-journey', 'Tech House', 60, '2024-10-18', 2),
('set-2', 'student-1', 'Deep Vibes Session', 'Set de deep house para ambientes mas relajados.', 'https://mixcloud.com/example/deep-vibes', 'Deep House', 45, '2024-10-25', 0),
('set-3', 'student-2', 'Melodic Techno Experience', 'Explorando sonidos melodicos con progresiones intensas.', 'https://soundcloud.com/example/melodic-techno', 'Melodic Techno', 90, '2024-10-22', 0),
('set-4', 'student-4', 'Peak Time Weapons', 'Set de hora punta con tracks potentes y mucha energia.', 'https://soundcloud.com/example/peak-time', 'Techno', 75, '2024-10-28', 0),
('set-5', 'student-3', 'House Basics Mix', 'Practicando lo basico con house clasico.', 'https://soundcloud.com/example/house-basics', 'House', 30, '2024-10-30', 0);

-- Inserción en `evaluations`
INSERT INTO `evaluations` (`set_id`, `teacher_id`, `technique`, `coherence`, `creativity`, `adaptation`, `overall_score`, `feedback`, `xp_awarded`, `reputation_change`) VALUES
('set-1', 'teacher-1', 7, 8, 6, 7, 7.00, 'Buen trabajo en las transiciones. El beatmatching esta muy limpio.', 150, 3),
('set-3', 'teacher-1', 9, 9, 8, 8, 8.50, 'Excelente dominio tecnico y muy buena narrativa en el set.', 200, 5),
('set-4', 'teacher-2', 9, 9, 9, 8, 8.75, 'Set de nivel profesional. Excelente seleccion y ejecucion impecable.', 250, 7);

-- Inserción en `events`
INSERT INTO `events` (`name`, `venue`, `audience`, `duration`, `styles`, `payment`, `difficulty`, `required_reputation`) VALUES
('Noche de Miercoles en Bar Local', 'Bar', 80, 120, 'House, Deep House, Disco', 100.00, 'Principiante', 0),
('After-Office Friday', 'Bar', 120, 180, 'Tech House, House, Funk', 150.00, 'Principiante', 10),
('Warm-up en Club Underground', 'Club', 300, 120, 'Minimal, Tech House, Deep House', 200.00, 'Intermedio', 25),
('Slot de Madrugada en Club', 'Club', 500, 180, 'Techno, Melodic Techno, Tech House', 350.00, 'Avanzado', 50),
('Festival de Electronica - Stage Secundario', 'Festival', 3000, 90, 'Techno, Tech House, Progressive House', 1000.00, 'Pro', 85);
