-- NogiK Database Initialization Dump
-- Prepared for import

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `event_evaluations`;
DROP TABLE IF EXISTS `setlist_tracks`;
DROP TABLE IF EXISTS `event_participations`;
DROP TABLE IF EXISTS `simulated_events`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `set_evaluations`;
DROP TABLE IF EXISTS `dj_sets`;
DROP TABLE IF EXISTS `class_attendees`;
DROP TABLE IF EXISTS `class_materials`;
DROP TABLE IF EXISTS `class_skills`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `student_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
CREATE TABLE `users` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'teacher') NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Students
CREATE TABLE `students` (
  `user_id` VARCHAR(50) PRIMARY KEY,
  `level` INT DEFAULT 1,
  `xp` INT DEFAULT 0,
  `xp_to_next_level` INT DEFAULT 500,
  `reputation` INT DEFAULT 0,
  `total_sets` INT DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Teachers
CREATE TABLE `teachers` (
  `user_id` VARCHAR(50) PRIMARY KEY,
  `specialties` TEXT NOT NULL, -- JSON or comma-separated list
  `bio` TEXT DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Skills
CREATE TABLE `skills` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `category` ENUM('technical', 'creative', 'performance') NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `max_level` INT DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Student Skills
CREATE TABLE `student_skills` (
  `student_id` VARCHAR(50) NOT NULL,
  `skill_id` VARCHAR(50) NOT NULL,
  `level` INT DEFAULT 0,
  `status` ENUM('not-started', 'in-progress', 'completed') DEFAULT 'not-started',
  `xp` INT DEFAULT 0,
  `xp_to_next_level` INT DEFAULT 200,
  PRIMARY KEY (`student_id`, `skill_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Classes
CREATE TABLE `classes` (
  `id` VARCHAR(50) PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `teacher_id` VARCHAR(50) DEFAULT NULL,
  `class_date` DATETIME NOT NULL,
  `duration` INT NOT NULL, -- minutes
  `status` ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Class Skills
CREATE TABLE `class_skills` (
  `class_id` VARCHAR(50) NOT NULL,
  `skill_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`class_id`, `skill_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Class Materials
CREATE TABLE `class_materials` (
  `id` VARCHAR(50) PRIMARY KEY,
  `class_id` VARCHAR(50) NOT NULL,
  `type` ENUM('video', 'document', 'link') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Class Attendees
CREATE TABLE `class_attendees` (
  `class_id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `status` ENUM('pending', 'approved') DEFAULT 'pending',
  PRIMARY KEY (`class_id`, `student_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. DJ Sets
CREATE TABLE `dj_sets` (
  `id` VARCHAR(50) PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `type` ENUM('audio', 'link') NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `genre` VARCHAR(50) NOT NULL,
  `duration` INT NOT NULL, -- minutes
  `uploaded_at` DATETIME NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Set Evaluations
CREATE TABLE `set_evaluations` (
  `id` VARCHAR(50) PRIMARY KEY,
  `set_id` VARCHAR(50) UNIQUE NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `technique` INT NOT NULL,
  `coherence` INT NOT NULL,
  `creativity` INT NOT NULL,
  `adaptation` INT NOT NULL,
  `overall_score` DECIMAL(4,2) NOT NULL,
  `feedback` TEXT NOT NULL,
  `evaluated_at` DATETIME NOT NULL,
  `xp_awarded` INT DEFAULT 0,
  `reputation_change` INT DEFAULT 0,
  FOREIGN KEY (`set_id`) REFERENCES `dj_sets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Comments
CREATE TABLE `comments` (
  `id` VARCHAR(50) PRIMARY KEY,
  `set_id` VARCHAR(50) NOT NULL,
  `user_id` VARCHAR(50) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`set_id`) REFERENCES `dj_sets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Simulated Events
CREATE TABLE `simulated_events` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `venue_type` ENUM('bar', 'club', 'festival', 'private', 'radio') NOT NULL,
  `audience` INT NOT NULL,
  `duration` INT NOT NULL, -- minutes
  `music_styles` VARCHAR(255) NOT NULL, -- comma-separated
  `payment` INT NOT NULL,
  `difficulty` ENUM('beginner', 'intermediate', 'advanced', 'pro') NOT NULL,
  `required_reputation` INT NOT NULL,
  `description` TEXT NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available', 'locked', 'completed') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Event Participations
CREATE TABLE `event_participations` (
  `id` VARCHAR(50) PRIMARY KEY,
  `event_id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `set_url` VARCHAR(255) DEFAULT '',
  `justification` TEXT NOT NULL,
  `submitted_at` DATETIME NOT NULL,
  `status` ENUM('pending', 'evaluated') DEFAULT 'pending',
  FOREIGN KEY (`event_id`) REFERENCES `simulated_events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Setlist Tracks
CREATE TABLE `setlist_tracks` (
  `participation_id` VARCHAR(50) NOT NULL,
  `position` INT NOT NULL,
  `track_name` VARCHAR(150) NOT NULL,
  `artist` VARCHAR(150) NOT NULL,
  `bpm` INT NOT NULL,
  `track_key` VARCHAR(10) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`participation_id`, `position`),
  FOREIGN KEY (`participation_id`) REFERENCES `event_participations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Event Evaluations
CREATE TABLE `event_evaluations` (
  `id` VARCHAR(50) PRIMARY KEY,
  `participation_id` VARCHAR(50) UNIQUE NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `track_selection` INT NOT NULL,
  `energy_flow` INT NOT NULL,
  `style_match` INT NOT NULL,
  `transitions` INT NOT NULL,
  `crowd_adaptation` INT NOT NULL,
  `total_score` DECIMAL(4,2) NOT NULL,
  `reputation_change` INT NOT NULL,
  `xp_awarded` INT NOT NULL,
  `feedback` TEXT NOT NULL,
  `evaluated_at` DATETIME NOT NULL,
  FOREIGN KEY (`participation_id`) REFERENCES `event_participations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA

-- Skills
INSERT INTO `skills` VALUES 
('beatmatching', 'Fundamentos del Beatmatching', 'Aprende las bases de la sincronización de BPMs usando los oídos y el pitch fader.', 'technical', 'headphones', 5),
('loops-effects', 'Loops y Efectos', 'Técnicas para crear loops en vivo y usar efectos creativos durante el mix.', 'technical', 'zap', 5),
('transitions', 'Transiciones', 'Domina diferentes tipos de transiciones: corte, fade, EQ mixing y más.', 'technical', 'trending-up', 5),
('creativity', 'Creatividad', 'Desarrolla tu propio estilo y aprende a tomar riesgos musicales.', 'creative', 'music', 5),
('energy-management', 'Lectura de Público', 'Técnicas para leer la energía de la pista y adaptar tu set en tiempo real.', 'performance', 'radio', 5);

-- Users (Password defaults are plaintext/demo or hash, we seed standard ones as plaintext for simplicity)
INSERT INTO `users` VALUES
('teacher-1', 'Carlos Mendoza', 'carlos@nogik.com', 'teacher123', 'teacher', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Carlos', '2024-01-01'),
('teacher-2', 'Ana Rivera', 'ana@nogik.com', 'teacher123', 'teacher', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Ana', '2024-01-01'),
('student-1', 'Demo Student', 'demo@nogik.com', 'demo123', 'student', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Demo', '2024-06-01'),
('student-2', 'Miguel Torres', 'miguel@email.com', 'student123', 'student', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Miguel', '2024-05-15'),
('student-3', 'Laura Sánchez', 'laura@email.com', 'student123', 'student', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Laura', '2024-07-01'),
('student-4', 'Diego Ramírez', 'diego@email.com', 'student123', 'student', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Diego', '2024-04-10'),
('student-5', 'Sofía Herrera', 'sofia@email.com', 'student123', 'student', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sofia', '2024-08-01');

-- Teachers Details
INSERT INTO `teachers` VALUES
('teacher-1', 'Techno, Minimal, Tech House', '15 años de experiencia como DJ residente en clubs de Ibiza y Berlin.'),
('teacher-2', 'House, Deep House, Disco', 'Productora y DJ con releases en sellos internacionales.');

-- Students Details
INSERT INTO `students` VALUES
('student-1', 4, 1850, 2000, 45, 5),
('student-2', 6, 3200, 3500, 62, 8),
('student-3', 2, 450, 750, 18, 2),
('student-4', 8, 5800, 6000, 78, 12),
('student-5', 1, 120, 500, 5, 0);

-- Student Skills mappings
INSERT INTO `student_skills` VALUES
('student-1', 'beatmatching', 4, 'completed', 450, 500),
('student-1', 'loops-effects', 3, 'in-progress', 280, 400),
('student-1', 'transitions', 3, 'in-progress', 350, 400),
('student-1', 'creativity', 2, 'in-progress', 180, 300),
('student-1', 'energy-management', 2, 'in-progress', 150, 300),

('student-2', 'beatmatching', 5, 'completed', 500, 500),
('student-2', 'loops-effects', 4, 'completed', 480, 500),
('student-2', 'transitions', 4, 'in-progress', 420, 500),
('student-2', 'creativity', 3, 'in-progress', 320, 400),
('student-2', 'energy-management', 3, 'in-progress', 280, 400),

('student-3', 'beatmatching', 2, 'in-progress', 150, 300),
('student-3', 'loops-effects', 1, 'in-progress', 80, 200),
('student-3', 'transitions', 1, 'in-progress', 100, 200),
('student-3', 'creativity', 1, 'not-started', 50, 200),
('student-3', 'energy-management', 1, 'not-started', 30, 200),

('student-4', 'beatmatching', 5, 'completed', 500, 500),
('student-4', 'loops-effects', 5, 'completed', 500, 500),
('student-4', 'transitions', 5, 'completed', 500, 500),
('student-4', 'creativity', 4, 'in-progress', 420, 500),
('student-4', 'energy-management', 4, 'in-progress', 380, 500),

('student-5', 'beatmatching', 1, 'in-progress', 60, 200),
('student-5', 'loops-effects', 0, 'not-started', 0, 200),
('student-5', 'transitions', 0, 'not-started', 20, 200),
('student-5', 'creativity', 0, 'not-started', 0, 200),
('student-5', 'energy-management', 0, 'not-started', 0, 200);

-- Classes
INSERT INTO `classes` VALUES
('class-1', 'Fundamentos del Beatmatching', 'Aprende las bases de la sincronización de BPMs usando los oídos y el pitch fader.', 'teacher-1', '2024-10-01 18:00:00', 90, 'completed'),
('class-2', 'Uso Creativo de Loops', 'Técnicas para crear loops en vivo y usarlos creativamente durante el mix.', 'teacher-2', '2024-10-08 18:00:00', 90, 'completed'),
('class-3', 'Transiciones Fluidas', 'Domina diferentes tipos de transiciones: corte, fade, EQ mixing y más.', 'teacher-1', '2024-10-15 18:00:00', 120, 'completed'),
('class-4', 'Efectos y FX Chains', 'Aprende a usar reverbs, delays, filters y crear cadenas de efectos.', 'teacher-2', '2024-10-22 18:00:00', 90, 'completed'),
('class-5', 'Lectura de Público', 'Técnicas para leer la energía de la pista y adaptar tu set en tiempo real.', 'teacher-1', '2024-10-29 18:00:00', 120, 'completed'),
('class-6', 'Construyendo un Set Coherente', 'Cómo estructurar un set de principio a fin con arcos de energía definidos.', 'teacher-2', '2024-11-05 18:00:00', 150, 'completed'),
('class-7', 'Técnicas Avanzadas de Scratching', 'Introducción al scratching y técnicas turntablistas básicas.', 'teacher-1', '2024-11-12 18:00:00', 120, 'upcoming'),
('class-8', 'Masterclass: Warm-up Sets', 'El arte de abrir una noche: selección musical y manejo de energía inicial.', 'teacher-2', '2024-11-19 18:00:00', 90, 'upcoming');

-- Class Skills
INSERT INTO `class_skills` VALUES
('class-1', 'beatmatching'),
('class-2', 'loops-effects'),
('class-3', 'transitions'),
('class-3', 'beatmatching'),
('class-4', 'loops-effects'),
('class-4', 'creativity'),
('class-5', 'energy-management'),
('class-5', 'creativity'),
('class-6', 'energy-management'),
('class-6', 'creativity'),
('class-6', 'transitions'),
('class-7', 'creativity'),
('class-7', 'transitions'),
('class-8', 'energy-management'),
('class-8', 'creativity');

-- Class Materials
INSERT INTO `class_materials` VALUES
('mat-1', 'class-1', 'video', 'Beatmatching Manual', 'https://youtube.com/watch?v=example1', 'Guía en video para principiantes.'),
('mat-2', 'class-1', 'document', 'Guía de BPMs por Género', '/docs/bpm-guide.pdf', 'Documento de referencia con géneros y BPMs.'),
('mat-3', 'class-2', 'video', 'Loop Techniques', 'https://youtube.com/watch?v=example2', 'Ejemplos de looping creativo.'),
('mat-4', 'class-3', 'video', 'EQ Mixing Masterclass', 'https://youtube.com/watch?v=example3', 'Cómo mezclar frecuencias.'),
('mat-5', 'class-3', 'link', 'Artículo: 10 Tipos de Transiciones', 'https://djmag.com/transitions', 'Lectura recomendada.'),
('mat-6', 'class-4', 'video', 'FX Deep Dive', 'https://youtube.com/watch?v=example4', 'Uso avanzado de reverb y delay.'),
('mat-7', 'class-5', 'video', 'Crowd Reading 101', 'https://youtube.com/watch?v=example5', 'Lección en video.'),
('mat-8', 'class-5', 'document', 'Energy Flow Charts', '/docs/energy-flow.pdf', 'Diagramas de flujo energético.');

-- Class Attendees
INSERT INTO `class_attendees` VALUES
('class-1', 'student-1'),
('class-1', 'student-2'),
('class-1', 'student-3'),
('class-1', 'student-4'),
('class-2', 'student-1'),
('class-2', 'student-2'),
('class-2', 'student-4'),
('class-3', 'student-1'),
('class-3', 'student-2'),
('class-3', 'student-4'),
('class-4', 'student-2'),
('class-4', 'student-4'),
('class-5', 'student-2'),
('class-5', 'student-4'),
('class-6', 'student-4');

-- DJ Sets
INSERT INTO `dj_sets` VALUES
('set-1', 'student-1', 'Tech House Journey', 'Mi primer set completo de tech house, enfocado en transiciones limpias.', 'link', 'https://soundcloud.com/example/tech-house-journey', 'Tech House', 60, '2024-10-18 12:00:00'),
('set-2', 'student-1', 'Deep Vibes Session', 'Set de deep house para ambientes más relajados.', 'link', 'https://mixcloud.com/example/deep-vibes', 'Deep House', 45, '2024-10-25 15:00:00'),
('set-3', 'student-2', 'Melodic Techno Experience', 'Explorando sonidos melódicos con progresiones intensas.', 'link', 'https://soundcloud.com/example/melodic-techno', 'Melodic Techno', 90, '2024-10-22 18:00:00'),
('set-4', 'student-4', 'Peak Time Weapons', 'Set de hora punta con tracks potentes y mucha energía.', 'link', 'https://soundcloud.com/example/peak-time', 'Techno', 75, '2024-10-28 20:00:00'),
('set-5', 'student-3', 'House Basics Mix', 'Practicando lo básico con house clásico.', 'link', 'https://soundcloud.com/example/house-basics', 'House', 30, '2024-10-30 10:00:00');

-- Set Evaluations
INSERT INTO `set_evaluations` VALUES
('eval-1', 'set-1', 'teacher-1', 7, 8, 6, 7, 7.00, 'Buen trabajo en las transiciones. La selección es coherente pero podrías arriesgar más en la creatividad. El beatmatching está muy limpio.', '2024-10-19 10:00:00', 150, 3),
('eval-2', 'set-3', 'teacher-1', 9, 9, 8, 8, 8.50, 'Excelente dominio técnico y muy buena narrativa en el set. Los buildups están muy bien ejecutados.', '2024-10-23 11:00:00', 200, 5),
('eval-3', 'set-4', 'teacher-2', 9, 9, 9, 8, 8.75, 'Set de nivel profesional. Excelente selección y ejecución impecable. Listo para eventos reales.', '2024-10-29 14:00:00', 250, 7);

-- Comments
INSERT INTO `comments` VALUES
('comment-1', 'set-1', 'student-2', 'Muy buen flow en las transiciones, se nota el progreso!', '2024-10-20 14:30:00'),
('comment-2', 'set-1', 'student-4', 'La selección de tracks está muy bien pensada. Me gustó el buildUp hacia el minuto 15.', '2024-10-20 16:45:00');

-- Simulated Events
INSERT INTO `simulated_events` VALUES
('event-1', 'Noche de Miércoles en Bar Local', 'bar', 80, 120, 'House, Deep House, Disco', 100, 'beginner', 0, 'Tu primera oportunidad de pinchar en público. Ambiente relajado con clientela habitual que busca buena música de fondo.', NULL, 'available'),
('event-2', 'After-Office Friday', 'bar', 120, 180, 'Tech House, House, Funk', 150, 'beginner', 10, 'Sesión de after-office en zona empresarial. Público diverso buscando desconectar de la semana.', NULL, 'available'),
('event-3', 'Warm-up en Club Underground', 'club', 300, 120, 'Minimal, Tech House, Deep House', 200, 'intermediate', 25, 'Sesión de apertura en club conocido. Debes calentar la pista para el DJ principal que viene después.', NULL, 'available'),
('event-4', 'Fiesta Privada Corporativa', 'private', 150, 240, 'House, Disco, Hip Hop', 400, 'intermediate', 35, 'Evento corporativo de fin de año. Público variado en edades, necesitas versatilidad.', NULL, 'available'),
('event-5', 'Slot de Madrugada en Club', 'club', 500, 180, 'Techno, Melodic Techno, Tech House', 350, 'advanced', 50, 'El slot de las 3-6am cuando el club está en su punto más alto. Máxima energía requerida.', NULL, 'available'),
('event-6', 'Radio Show Online', 'radio', 2000, 60, 'Techno, Minimal, Tech House', 150, 'intermediate', 40, 'Programa de radio online con audiencia internacional. Sin feedback visual del público.', NULL, 'available'),
('event-7', 'Headliner en Club Reconocido', 'club', 800, 180, 'Techno, Melodic Techno', 600, 'pro', 70, 'Eres el DJ principal de la noche. Todas las miradas están puestas en ti.', NULL, 'available'),
('event-8', 'Festival de Electrónica - Stage Secundario', 'festival', 3000, 90, 'Techno, Tech House, Progressive House', 1000, 'pro', 85, 'Tu debut en un festival real. Escenario secundario pero con gran audiencia.', NULL, 'available');
