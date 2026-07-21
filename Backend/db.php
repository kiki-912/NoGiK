<?php
// Backend/db.php
// Sistema de persistencia dual para NoGiK (MySQL con fallback automático a JSON)

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'nogik_db';

$pdo = null;
$use_mysql = false;

try {
    // Intentar conectar a la base de datos de Laragon
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $use_mysql = true;
} catch (PDOException $e) {
    $use_mysql = false;
}

$json_file = __DIR__ . '/data.json';

// Cargar datos por defecto de data.php si no hay JSON
function get_default_data() {
    require_once __DIR__ . '/data.php';
    global $skills, $tiers, $teachers, $students, $classes, $sets, $events;
    return [
        'skills' => $skills,
        'tiers' => $tiers,
        'teachers' => $teachers,
        'students' => $students,
        'classes' => $classes,
        'sets' => $sets,
        'events' => $events
    ];
}

// Guardar datos en el JSON
function save_to_json($data) {
    global $json_file;
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Cargar datos desde el JSON
function load_from_json() {
    global $json_file;
    if (!file_exists($json_file)) {
        $default_data = get_default_data();
        save_to_json($default_data);
        return $default_data;
    }
    $content = file_get_contents($json_file);
    $data = json_decode($content, true);
    if (!$data) {
        $default_data = get_default_data();
        save_to_json($default_data);
        return $default_data;
    }
    return $data;
}

// Importar esquema SQL si las tablas no existen en la base de datos
if ($use_mysql) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'classes'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            $sql_file = dirname(__DIR__) . '/nogik_database.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                // Dividir sentencias para ejecución
                $pdo->exec($sql);
            } else {
                $use_mysql = false;
            }
        }
    } catch (Exception $ex) {
        $use_mysql = false;
    }
}

$app_data = [];

// Lectura de datos
if ($use_mysql) {
    try {
        // Tiers
        $stmt = $pdo->query("SELECT id, name, min_reputation as min, max_reputation as max, color FROM tiers");
        $app_data['tiers'] = $stmt->fetchAll();
        
        // Skills
        $stmt = $pdo->query("SELECT id, name, description, category FROM skills");
        $app_data['skills'] = $stmt->fetchAll();
        
        // Teachers
        $stmt = $pdo->query("SELECT id, name, email, role, avatar, bio FROM users WHERE role = 'teacher'");
        $teachers_db = $stmt->fetchAll();
        foreach ($teachers_db as &$t) {
            $s_stmt = $pdo->prepare("SELECT specialty FROM teacher_specialties WHERE teacher_id = ?");
            $s_stmt->execute([$t['id']]);
            $t['specialties'] = $s_stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $app_data['teachers'] = $teachers_db;
        
        // Students
        $stmt = $pdo->query("SELECT id, name, email, role, avatar, level, xp, xp_to_next_level as xpToNextLevel, reputation, total_sets as totalSets FROM users WHERE role = 'student'");
        $students_db = $stmt->fetchAll();
        foreach ($students_db as &$s) {
            $c_stmt = $pdo->prepare("SELECT class_id FROM student_completed_classes WHERE student_id = ?");
            $c_stmt->execute([$s['id']]);
            $s['completedClassIds'] = $c_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $sk_stmt = $pdo->prepare("SELECT skill_id as skillId, level, status, xp, xp_to_next_level as xpToNextLevel FROM student_skills WHERE student_id = ?");
            $sk_stmt->execute([$s['id']]);
            $s['skills'] = $sk_stmt->fetchAll();
        }
        $app_data['students'] = $students_db;
        
        // Classes
        $stmt = $pdo->query("SELECT id, title, description, teacher_id as teacherId, class_date as date, duration, status FROM classes ORDER BY class_date ASC");
        $app_data['classes'] = $stmt->fetchAll();
        
        // Sets
        $stmt = $pdo->query("SELECT id, student_id as studentId, title, description, url, genre, duration, uploaded_at as uploadedAt, comments_count as comments FROM sets");
        $sets_db = $stmt->fetchAll();
        foreach ($sets_db as &$set) {
            $e_stmt = $pdo->prepare("SELECT teacher_id as teacherId, technique, coherence, creativity, adaptation, overall_score as overallScore, feedback, xp_awarded as xpAwarded, reputation_change as reputationChange FROM evaluations WHERE set_id = ?");
            $e_stmt->execute([$set['id']]);
            $eval = $e_stmt->fetch();
            $set['evaluation'] = $eval ? $eval : null;
        }
        $app_data['sets'] = $sets_db;
        
        // Events
        $stmt = $pdo->query("SELECT name, venue, audience, duration, styles, payment, difficulty, required_reputation as requiredReputation FROM events");
        $app_data['events'] = $stmt->fetchAll();
        
    } catch (Exception $ex) {
        $use_mysql = false;
        $app_data = load_from_json();
    }
} else {
    $app_data = load_from_json();
}

// Extraer en variables globales
$skills = $app_data['skills'];
$tiers = $app_data['tiers'];
$teachers = $app_data['teachers'];
$students = $app_data['students'];
$classes = $app_data['classes'];
$sets = $app_data['sets'];
$events = $app_data['events'];

// Guardar nueva clase de forma persistente
function save_new_class($new_class) {
    global $use_mysql, $pdo, $app_data;
    if ($use_mysql) {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (id, title, description, teacher_id, class_date, duration, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $new_class['id'],
                $new_class['title'],
                $new_class['description'],
                $new_class['teacherId'],
                $new_class['date'],
                $new_class['duration'],
                $new_class['status']
            ]);
            return true;
        } catch (Exception $ex) {
            // En caso de error de inserción SQL, continúa con el fallback
        }
    }
    
    // Guardar en JSON fallback
    $app_data = load_from_json();
    $app_data['classes'][] = $new_class;
    save_to_json($app_data);
    return true;
}
