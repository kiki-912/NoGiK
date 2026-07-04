<?php
session_start();
require_once __DIR__ . '/Backend/data.php';
require_once __DIR__ . '/Backend/functions.php';

$page = $_GET['page'] ?? 'home';
$error = '';

if (($_POST['action'] ?? '') === 'login') {
    $user = login_user(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''), $students, $teachers);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php?page=' . ($user['role'] === 'teacher' ? 'teacher' : 'student'));
        exit;
    }
    $error = 'Credenciales invalidas. Usa una cuenta demo.';
}

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

$currentUser = current_user($students, $teachers);
if (!$currentUser && $page !== 'home') {
    header('Location: index.php');
    exit;
}
if ($currentUser && $page === 'home') {
    header('Location: index.php?page=' . ($currentUser['role'] === 'teacher' ? 'teacher' : 'student'));
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NogiK - Plataforma DJ</title>
    <link rel="stylesheet" href="Frontend/Styles/main.css">
</head>
<body>
<?php if ($page === 'home'): ?>
<main class="landing">
    <section class="hero">
        <div class="hero-copy">
            <div class="brand-mark"><span class="disc">◉</span><h1><span>Nogi</span>K</h1></div>
            <p>La plataforma para formacion y simulacion de carrera DJ. Aprende, practica y construye tu camino hacia el escenario.</p>
            <div class="feature-grid"><span>Clases interactivas</span><span>Habilidades reales</span><span>Sistema de reputacion</span><span>Simulador de carrera</span></div>
        </div>
        <form class="login-card" method="post">
            <input type="hidden" name="action" value="login">
            <h2>Iniciar sesion</h2>
            <p>Accede a tu cuenta de NogiK</p>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
            <label>Email</label><input name="email" type="email" placeholder="tu@email.com" required>
            <label>Contrasena</label><input name="password" type="password" placeholder="demo123" required>
            <button class="btn primary" type="submit">Entrar</button>
            <div class="demo-buttons">
                <button class="btn ghost" type="button" data-demo-email="demo@nogik.com" data-demo-password="demo123">Alumno demo</button>
                <button class="btn ghost alt" type="button" data-demo-email="carlos@nogik.com" data-demo-password="teacher123">Profesor demo</button>
            </div>
        </form>
    </section>
    <section class="wide-section">
        <h2>Todo lo que necesitas para ser DJ profesional</h2>
        <div class="cards three">
            <article class="card"><b>Aprende con expertos</b><p>Clases estructuradas, materiales y seguimiento personalizado.</p></article>
            <article class="card"><b>Progresa visualmente</b><p>Cinco competencias clave con indicadores claros.</p></article>
            <article class="card"><b>Simula tu carrera</b><p>Eventos de bares, clubs, radio y festivales para ganar reputacion.</p></article>
        </div>
    </section>
</main>
<?php else: ?>
<div class="app-shell">
    <?php render_sidebar($currentUser); ?>
    <main class="content">
        <?php
        if ($page === 'student' && $currentUser['role'] === 'student') render_student_dashboard($currentUser, $students, $classes, $sets, $events, $skills, $tiers);
        elseif ($page === 'skills' && $currentUser['role'] === 'student') render_skills($currentUser, $skills);
        elseif ($page === 'sets' && $currentUser['role'] === 'student') render_sets($currentUser, $sets);
        elseif ($page === 'calendar' && $currentUser['role'] === 'student') render_classes($classes, $teachers, 'Calendario de clases');
        elseif ($page === 'simulator' && $currentUser['role'] === 'student') render_student_simulator($currentUser, $events);
        elseif ($page === 'simulator' && $currentUser['role'] === 'teacher') render_teacher_simulator($events);
        elseif ($page === 'community' && $currentUser['role'] === 'student') render_community($sets, $students);
        elseif ($page === 'teacher' && $currentUser['role'] === 'teacher') render_teacher_dashboard($currentUser, $students, $classes, $sets, $tiers);
        elseif ($page === 'students' && $currentUser['role'] === 'teacher') render_students($students, $tiers);
        elseif ($page === 'classes' && $currentUser['role'] === 'teacher') render_classes($classes, $teachers, 'Mis clases', $currentUser['id']);
        elseif ($page === 'evaluations' && $currentUser['role'] === 'teacher') render_evaluations($sets, $students);
        else render_not_found();
        ?>
    </main>
</div>
<?php endif; ?>
<script src="Frontend/app.js"></script>
</body>
</html>
