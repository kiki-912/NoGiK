<?php
// NogiK - Backend Action Processor
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!$action) {
    header("Location: ../../index.php");
    exit();
}

function get_xp_to_next_level($level) {
    if ($level == 1) return 500;
    if ($level == 2) return 750;
    if ($level == 3) return 1200;
    if ($level == 4) return 2000;
    if ($level == 5) return 2700;
    if ($level == 6) return 3500;
    if ($level == 7) return 4500;
    if ($level == 8) return 6000;
    return $level * 800;
}

// Generate unique ID helper
function generate_uuid() {
    return uniqid('', true);
}

switch ($action) {
    case 'setup_db':
        try {
            $host = 'localhost';
            $db   = 'nogik';
            $user = 'root';
            $pass = '';
            $charset = 'utf8mb4';
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            // Connect to MySQL server
            $pdo_setup = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
            
            // Create database
            $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo_setup->exec("USE `$db`");
            
            // Read and run sql dump
            $sql_file = __DIR__ . '/../database/nogik.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                
                // PDO exec can run multiple queries in MySQL
                $pdo_setup->exec($sql);
                
                header("Location: ../../index.php?success=setup_complete");
                exit();
            } else {
                die("Error: No se encontró el archivo SQL en " . $sql_file);
            }
        } catch (\PDOException $e) {
            die("Error en la configuración de base de datos: " . $e->getMessage());
        }
        break;

    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($email) || empty($password)) {
            header("Location: ../../index.php?error=empty");
            exit();
        }
        
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['password'] === $password) {
            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            header("Location: ../../Frontend/" . ($user['role'] === 'student' ? 'student.php' : 'teacher.php'));
            exit();
        } else {
            // Invalid credentials
            header("Location: ../../index.php?error=invalid");
            exit();
        }
        break;
        
    case 'upload_set':
        require_role('student');
        $user = get_current_user_details();
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $duration = intval($_POST['duration'] ?? 60);
        $url = trim($_POST['url'] ?? '');
        
        if (empty($title) || empty($genre) || empty($url)) {
            header("Location: ../../Frontend/student_sets.php?error=empty");
            exit();
        }
        
        $set_id = 'set-' . generate_uuid();
        
        // Insert set
        $stmt = $pdo->prepare("INSERT INTO dj_sets (id, student_id, title, description, type, url, genre, duration, uploaded_at) VALUES (?, ?, ?, ?, 'link', ?, ?, ?, NOW())");
        $stmt->execute([$set_id, $user['id'], $title, $description, $url, $genre, $duration]);
        
        // Increment student total sets
        $stmt_u = $pdo->prepare("UPDATE students SET total_sets = total_sets + 1 WHERE user_id = ?");
        $stmt_u->execute([$user['id']]);
        
        header("Location: ../../Frontend/student_sets.php?success=uploaded");
        exit();
        break;
        
    case 'submit_comment':
        require_login();
        $user = get_current_user_details();
        
        $set_id = trim($_POST['set_id'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if (empty($set_id) || empty($content)) {
            header("Location: ../../Frontend/student_community.php?error=empty");
            exit();
        }
        
        $comment_id = 'comment-' . generate_uuid();
        
        $stmt = $pdo->prepare("INSERT INTO comments (id, set_id, user_id, content, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$comment_id, $set_id, $user['id'], $content]);
        
        // Redirect back to sets details
        header("Location: ../../Frontend/student_sets.php?id=" . urlencode($set_id) . "&success=commented");
        exit();
        break;
        
    case 'join_class':
        require_role('student');
        $user = get_current_user_details();
        
        $class_id = trim($_GET['class_id'] ?? $_POST['class_id'] ?? '');
        
        if (empty($class_id)) {
            header("Location: ../../Frontend/student_calendar.php?error=empty");
            exit();
        }
        
        // Check if already joined
        $stmt = $pdo->prepare("SELECT * FROM class_attendees WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$class_id, $user['id']]);
        if ($stmt->fetch()) {
            header("Location: ../../Frontend/student_calendar.php?info=already_joined");
            exit();
        }
        
        $stmt = $pdo->prepare("INSERT INTO class_attendees (class_id, student_id) VALUES (?, ?)");
        $stmt->execute([$class_id, $user['id']]);
        
        header("Location: ../../Frontend/student_calendar.php?success=joined");
        exit();
        break;
        
    case 'approve_class_join':
        require_role('teacher');
        $class_id = trim($_POST['class_id'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        
        if (empty($class_id) || empty($student_id)) {
            header("Location: ../../Frontend/teacher_notifications.php?error=invalid");
            exit();
        }
        
        $stmt = $pdo->prepare("UPDATE class_attendees SET status = 'approved' WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$class_id, $student_id]);
        
        header("Location: ../../Frontend/teacher_notifications.php?success=approved");
        exit();
        break;
        
    case 'reject_class_join':
        require_role('teacher');
        $class_id = trim($_POST['class_id'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        
        if (empty($class_id) || empty($student_id)) {
            header("Location: ../../Frontend/teacher_notifications.php?error=invalid");
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM class_attendees WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$class_id, $student_id]);
        
        header("Location: ../../Frontend/teacher_notifications.php?success=rejected");
        exit();
        break;
        
    case 'submit_participation':
        require_role('student');
        $user = get_current_user_details();
        
        $event_id = trim($_POST['event_id'] ?? '');
        $justification = trim($_POST['justification'] ?? '');
        $track_names = $_POST['track_name'] ?? [];
        $artists = $_POST['artist'] ?? [];
        $bpms = $_POST['bpm'] ?? [];
        $keys = $_POST['key'] ?? [];
        $notes = $_POST['notes'] ?? [];
        
        if (empty($event_id) || empty($justification) || count($track_names) < 3) {
            header("Location: ../../Frontend/student_simulator.php?id=" . urlencode($event_id) . "&error=invalid_participation");
            exit();
        }
        
        $participation_id = 'part-' . generate_uuid();
        
        // Insert participation
        $stmt = $pdo->prepare("INSERT INTO event_participations (id, event_id, student_id, justification, submitted_at, status) VALUES (?, ?, ?, ?, NOW(), 'pending')");
        $stmt->execute([$participation_id, $event_id, $user['id'], $justification]);
        
        // Insert tracks
        $stmt_track = $pdo->prepare("INSERT INTO setlist_tracks (participation_id, position, track_name, artist, bpm, track_key, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < count($track_names); $i++) {
            if (empty($track_names[$i]) || empty($artists[$i])) continue;
            $stmt_track->execute([
                $participation_id,
                $i + 1,
                trim($track_names[$i]),
                trim($artists[$i]),
                intval($bpms[$i] ?? 128),
                trim($keys[$i] ?? ''),
                trim($notes[$i] ?? '')
            ]);
        }
        
        header("Location: ../../Frontend/student_simulator.php?success=submitted");
        exit();
        break;
        
    case 'evaluate_set':
        require_role('teacher');
        $teacher = get_current_user_details();
        
        $set_id = trim($_POST['set_id'] ?? '');
        $technique = intval($_POST['technique'] ?? 5);
        $coherence = intval($_POST['coherence'] ?? 5);
        $creativity = intval($_POST['creativity'] ?? 5);
        $adaptation = intval($_POST['adaptation'] ?? 5);
        $feedback = trim($_POST['feedback'] ?? '');
        
        if (empty($set_id) || empty($feedback)) {
            header("Location: ../../Frontend/teacher_evaluations.php?error=empty");
            exit();
        }
        
        // Fetch set to get student_id
        $stmt_set = $pdo->prepare("SELECT * FROM dj_sets WHERE id = ?");
        $stmt_set->execute([$set_id]);
        $set = $stmt_set->fetch();
        
        if (!$set) {
            header("Location: ../../Frontend/teacher_evaluations.php?error=not_found");
            exit();
        }
        
        $overall_score = ($technique + $coherence + $creativity + $adaptation) / 4;
        $xp_awarded = intval($overall_score * 25);
        $reputation_change = intval($overall_score / 2);
        
        $eval_id = 'eval-' . generate_uuid();
        
        // Insert set evaluation
        $stmt = $pdo->prepare("INSERT INTO set_evaluations (id, set_id, teacher_id, technique, coherence, creativity, adaptation, overall_score, feedback, evaluated_at, xp_awarded, reputation_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([$eval_id, $set_id, $teacher['id'], $technique, $coherence, $creativity, $adaptation, $overall_score, $feedback, $xp_awarded, $reputation_change]);
        
        // Award XP and reputation to Student
        $stmt_stud = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt_stud->execute([$set['student_id']]);
        $student = $stmt_stud->fetch();
        
        if ($student) {
            $new_xp = $student['xp'] + $xp_awarded;
            $new_reputation = max(0, $student['reputation'] + $reputation_change);
            $new_level = $student['level'];
            $xp_to_next = $student['xp_to_next_level'];
            
            while ($new_xp >= $xp_to_next) {
                $new_xp -= $xp_to_next;
                $new_level++;
                $xp_to_next = get_xp_to_next_level($new_level);
            }
            
            $stmt_u = $pdo->prepare("UPDATE students SET level = ?, xp = ?, xp_to_next_level = ?, reputation = ? WHERE user_id = ?");
            $stmt_u->execute([$new_level, $new_xp, $xp_to_next, $new_reputation, $set['student_id']]);
            
            // Auto complete some student skills (loops-effects, beatmatching, etc.) depending on overall score
            $skills_to_update = [];
            if ($genre === 'Tech House' || $genre === 'House') {
                $skills_to_update[] = 'beatmatching';
                $skills_to_update[] = 'transitions';
            } else {
                $skills_to_update[] = 'creativity';
            }
            
            foreach ($skills_to_update as $skill_id) {
                // Fetch skill level
                $stmt_sk = $pdo->prepare("SELECT * FROM student_skills WHERE student_id = ? AND skill_id = ?");
                $stmt_sk->execute([$set['student_id'], $skill_id]);
                $sk = $stmt_sk->fetch();
                if ($sk) {
                    $sk_xp = $sk['xp'] + intval($overall_score * 15);
                    $sk_lvl = $sk['level'];
                    $sk_xp_to_next = $sk['xp_to_next_level'];
                    
                    while ($sk_xp >= $sk_xp_to_next && $sk_lvl < 5) {
                        $sk_xp -= $sk_xp_to_next;
                        $sk_lvl++;
                        $sk_xp_to_next = ($sk_lvl + 1) * 100;
                    }
                    
                    $sk_status = ($sk_lvl == 5) ? 'completed' : 'in-progress';
                    
                    $stmt_usk = $pdo->prepare("UPDATE student_skills SET level = ?, status = ?, xp = ?, xp_to_next_level = ? WHERE student_id = ? AND skill_id = ?");
                    $stmt_usk->execute([$sk_lvl, $sk_status, $sk_xp, $sk_xp_to_next, $set['student_id'], $skill_id]);
                }
            }
        }
        
        header("Location: ../../Frontend/teacher_evaluations.php?success=set_evaluated");
        exit();
        break;
        
    case 'evaluate_event':
        require_role('teacher');
        $teacher = get_current_user_details();
        
        $participation_id = trim($_POST['participation_id'] ?? '');
        $track_selection = intval($_POST['track_selection'] ?? 5);
        $energy_flow = intval($_POST['energy_flow'] ?? 5);
        $style_match = intval($_POST['style_match'] ?? 5);
        $transitions = intval($_POST['transitions'] ?? 5);
        $crowd_adaptation = intval($_POST['crowd_adaptation'] ?? 5);
        $feedback = trim($_POST['feedback'] ?? '');
        
        if (empty($participation_id) || empty($feedback)) {
            header("Location: ../../Frontend/teacher_evaluations.php?error=empty");
            exit();
        }
        
        // Fetch participation to get student_id and event_id
        $stmt_part = $pdo->prepare("SELECT * FROM event_participations WHERE id = ?");
        $stmt_part->execute([$participation_id]);
        $part = $stmt_part->fetch();
        
        if (!$part) {
            header("Location: ../../Frontend/teacher_evaluations.php?error=not_found");
            exit();
        }
        
        // Fetch event to get payment or difficulty context
        $stmt_ev = $pdo->prepare("SELECT * FROM simulated_events WHERE id = ?");
        $stmt_ev->execute([$part['event_id']]);
        $event = $stmt_ev->fetch();
        
        $total_score = ($track_selection + $energy_flow + $style_match + $transitions + $crowd_adaptation) / 5;
        $xp_awarded = intval($total_score * 30);
        $reputation_change = intval($total_score * 1.5);
        
        $eval_id = 'eeval-' . generate_uuid();
        
        // Insert event evaluation
        $stmt = $pdo->prepare("INSERT INTO event_evaluations (id, participation_id, teacher_id, track_selection, energy_flow, style_match, transitions, crowd_adaptation, total_score, reputation_change, xp_awarded, feedback, evaluated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $eval_id, 
            $participation_id, 
            $teacher['id'], 
            $track_selection, 
            $energy_flow, 
            $style_match, 
            $transitions, 
            $crowd_adaptation, 
            $total_score, 
            $reputation_change, 
            $xp_awarded, 
            $feedback
        ]);
        
        // Update participation status
        $stmt_up = $pdo->prepare("UPDATE event_participations SET status = 'evaluated' WHERE id = ?");
        $stmt_up->execute([$participation_id]);
        
        // Award XP and reputation to Student
        $stmt_stud = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt_stud->execute([$part['student_id']]);
        $student = $stmt_stud->fetch();
        
        if ($student) {
            $new_xp = $student['xp'] + $xp_awarded;
            $new_reputation = max(0, $student['reputation'] + $reputation_change);
            $new_level = $student['level'];
            $xp_to_next = $student['xp_to_next_level'];
            
            while ($new_xp >= $xp_to_next) {
                $new_xp -= $xp_to_next;
                $new_level++;
                $xp_to_next = get_xp_to_next_level($new_level);
            }
            
            $stmt_u = $pdo->prepare("UPDATE students SET level = ?, xp = ?, xp_to_next_level = ?, reputation = ? WHERE user_id = ?");
            $stmt_u->execute([$new_level, $new_xp, $xp_to_next, $new_reputation, $part['student_id']]);
            
            // Advance student energy management and creativity skills
            $skills_to_update = ['energy-management', 'creativity'];
            foreach ($skills_to_update as $skill_id) {
                $stmt_sk = $pdo->prepare("SELECT * FROM student_skills WHERE student_id = ? AND skill_id = ?");
                $stmt_sk->execute([$part['student_id'], $skill_id]);
                $sk = $stmt_sk->fetch();
                if ($sk) {
                    $sk_xp = $sk['xp'] + intval($total_score * 20);
                    $sk_lvl = $sk['level'];
                    $sk_xp_to_next = $sk['xp_to_next_level'];
                    
                    while ($sk_xp >= $sk_xp_to_next && $sk_lvl < 5) {
                        $sk_xp -= $sk_xp_to_next;
                        $sk_lvl++;
                        $sk_xp_to_next = ($sk_lvl + 1) * 100;
                    }
                    
                    $sk_status = ($sk_lvl == 5) ? 'completed' : 'in-progress';
                    
                    $stmt_usk = $pdo->prepare("UPDATE student_skills SET level = ?, status = ?, xp = ?, xp_to_next_level = ? WHERE student_id = ? AND skill_id = ?");
                    $stmt_usk->execute([$sk_lvl, $sk_status, $sk_xp, $sk_xp_to_next, $part['student_id'], $skill_id]);
                }
            }
        }
        
        $redirect = trim($_POST['redirect_target'] ?? 'teacher_evaluations');
        if ($redirect === 'teacher_simulator') {
            header("Location: ../../Frontend/teacher_simulator.php?success=event_evaluated");
        } else {
            header("Location: ../../Frontend/teacher_evaluations.php?success=event_evaluated");
        }
        exit();
        break;
        
    case 'create_class':
        require_role('teacher');
        $teacher = get_current_user_details();
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $duration = intval($_POST['duration'] ?? 60);
        $skills = $_POST['skills'] ?? [];
        
        $mat_titles = $_POST['mat_title'] ?? [];
        $mat_types = $_POST['mat_type'] ?? [];
        $mat_urls = $_POST['mat_url'] ?? [];
        
        if (empty($title) || empty($description) || empty($date)) {
            header("Location: ../../Frontend/teacher_classes.php?error=empty");
            exit();
        }
        
        $class_id = 'class-' . generate_uuid();
        
        // Insert Class
        $stmt = $pdo->prepare("INSERT INTO classes (id, title, description, teacher_id, class_date, duration, status) VALUES (?, ?, ?, ?, ?, ?, 'upcoming')");
        $stmt->execute([$class_id, $title, $description, $teacher['id'], $date, $duration]);
        
        // Insert Class Skills mapping
        $stmt_sk = $pdo->prepare("INSERT INTO class_skills (class_id, skill_id) VALUES (?, ?)");
        foreach ($skills as $skill_id) {
            $stmt_sk->execute([$class_id, $skill_id]);
        }
        
        // Insert materials
        $stmt_mat = $pdo->prepare("INSERT INTO class_materials (id, class_id, type, title, url, description) VALUES (?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < count($mat_titles); $i++) {
            if (empty($mat_titles[$i]) || empty($mat_urls[$i])) continue;
            $mat_id = 'mat-' . generate_uuid();
            $stmt_mat->execute([
                $mat_id,
                $class_id,
                $mat_types[$i] ?? 'link',
                trim($mat_titles[$i]),
                trim($mat_urls[$i]),
                'Material de clase programada.'
            ]);
        }
        
        header("Location: ../../Frontend/teacher_classes.php?success=class_created");
        exit();
        break;

    case 'create_event':
        require_role('teacher');
        $teacher = get_current_user_details();
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue_type = strtolower(trim($_POST['type'] ?? 'club'));
        $difficulty_raw = trim($_POST['difficulty'] ?? 'Medio');
        
        // Map difficulty values to DB enum
        $difficulty = 'intermediate';
        if ($difficulty_raw === 'Fácil') $difficulty = 'beginner';
        elseif ($difficulty_raw === 'Difícil') $difficulty = 'advanced';
        
        $required_reputation = intval($_POST['required_reputation'] ?? 0);
        $music_styles = trim($_POST['music_styles'] ?? '');
        $payment = intval($_POST['payment'] ?? 0);
        
        if (empty($name) || empty($description) || empty($music_styles)) {
            header("Location: ../../Frontend/teacher_simulator.php?error=empty");
            exit();
        }
        
        // Sensible defaults based on location type
        $audience = 500;
        $duration = 60;
        if ($venue_type === 'bar') { $audience = 150; $duration = 90; }
        elseif ($venue_type === 'festival') { $audience = 8000; $duration = 60; }
        elseif ($venue_type === 'private') { $audience = 200; $duration = 120; }
        elseif ($venue_type === 'radio') { $audience = 1000; $duration = 45; }
        
        $event_id = 'event-' . generate_uuid();
        
        $stmt = $pdo->prepare("
            INSERT INTO simulated_events 
            (id, name, description, venue_type, audience, duration, music_styles, payment, difficulty, required_reputation, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
        ");
        $stmt->execute([
            $event_id, 
            $name, 
            $description, 
            $venue_type, 
            $audience, 
            $duration, 
            $music_styles, 
            $payment, 
            $difficulty, 
            $required_reputation
        ]);
        
        header("Location: ../../Frontend/teacher_simulator.php?success=event_created");
        exit();
        break;

    case 'update_profile':
        require_login();
        $user_id = $_SESSION['user_id'];
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $avatar_seed = trim($_POST['avatar_seed'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($name) || empty($email)) {
            header("Location: ../../Frontend/profile.php?error=empty");
            exit();
        }
        
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $user_id]);
        if ($stmt_check->fetchColumn() > 0) {
            header("Location: ../../Frontend/profile.php?error=email_taken");
            exit();
        }
        
        $avatar_url = null;
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['avatar_file']['tmp_name'];
            $file_name = $_FILES['avatar_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (in_array($file_ext, $allowed_exts)) {
                $upload_dir = __DIR__ . '/../../Frontend/public/uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
                $dest_path = $upload_dir . '/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $avatar_url = 'Frontend/public/uploads/' . $new_file_name;
                }
            }
        }
        
        if ($avatar_url === null) {
            if (!empty($avatar_seed)) {
                $avatar_url = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($avatar_seed);
            } else {
                $stmt_prev = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt_prev->execute([$user_id]);
                $avatar_url = $stmt_prev->fetchColumn() ?: ('https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($name));
            }
        }
        
        if (!empty($new_password)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, avatar = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $avatar_url, $new_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$name, $email, $avatar_url, $user_id]);
        }
        
        header("Location: ../../Frontend/profile.php?success=profile_updated");
        exit();
        break;
}
?>
