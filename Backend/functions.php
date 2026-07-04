<?php
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function login_user($email, $password, $students, $teachers) {
    if ($email === 'demo@nogik.com' && $password === 'demo123') return find_by_id($students, 'student-1');
    if ($email === 'carlos@nogik.com' && $password === 'teacher123') return find_by_id($teachers, 'teacher-1');
    foreach (array_merge($students, $teachers) as $user) if ($user['email'] === $email) return $user;
    return null;
}
function find_by_id($items, $id) { foreach ($items as $item) if (($item['id'] ?? '') === $id) return $item; return null; }
function current_user($students, $teachers) {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) return null;
    return $_SESSION['role'] === 'teacher' ? find_by_id($teachers, $_SESSION['user_id']) : find_by_id($students, $_SESSION['user_id']);
}
function icon_svg($name, $class = '') {
    $icons = [
        'layout' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
        'users' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'graduation' => '<svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
        'clipboard' => '<svg viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>',
        'radio' => '<svg viewBox="0 0 24 24"><path d="M4.9 19.1a10 10 0 0 1 0-14.2M7.8 16.2a6 6 0 0 1 0-8.4M10.6 13.4a2 2 0 0 1 0-2.8"/><circle cx="12" cy="12" r="1"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/></svg>',
        'panel' => '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 4v16"/></svg>',
        'play' => '<svg viewBox="0 0 24 24"><path d="m7 4 14 8-14 8V4Z"/></svg>',
        'external' => '<svg viewBox="0 0 24 24"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
        'star' => '<svg viewBox="0 0 24 24"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z"/></svg>',
        'chevron' => '<svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>'
    ];
    $svg = $icons[$name] ?? $icons['layout'];
    return '<span class="ico '.e($class).'">'.$svg.'</span>';
}
function initials($name) { $parts = preg_split('/\s+/', trim($name)); return strtoupper(substr($parts[0],0,1).substr($parts[count($parts)-1],0,1)); }
function avatar_for($name) {
    $map = ['Carlos Mendoza'=>'🧑‍🏫','Ana Rivera'=>'🎧','Demo Student'=>'👩🏻','Miguel Torres'=>'🧑🏼‍🎤','Laura Sanchez'=>'👨🏻‍🦲','Diego Ramirez'=>'🧔🏾','Sofia Herrera'=>'👩🏾'];
    return $map[$name] ?? initials($name);
}
function tier_for($reputation, $tiers) { foreach ($tiers as $tier) if ($reputation >= $tier['min'] && $reputation <= $tier['max']) return $tier; return $tiers[0]; }
function student_sets($studentId, $sets) { return array_values(array_filter($sets, fn($set) => $set['studentId'] === $studentId)); }
function skill_name($skillId, $skills) { foreach ($skills as $skill) if ($skill['id'] === $skillId) return $skill['name']; return $skillId; }
function completed_skills($student) { return count(array_filter($student['skills'], fn($s) => $s['status'] === 'completed')); }
function avg_skill($student) { return count($student['skills']) ? array_sum(array_map(fn($s) => $s['level'], $student['skills'])) / count($student['skills']) : 0; }
function upcoming_classes($classes, $teacherId = null) { return array_values(array_filter($classes, fn($c) => $c['status'] === 'upcoming' && (!$teacherId || $c['teacherId'] === $teacherId))); }
function completed_classes($classes, $teacherId = null) { return array_values(array_filter($classes, fn($c) => $c['status'] === 'completed' && (!$teacherId || $c['teacherId'] === $teacherId))); }
function render_header($title, $subtitle='', $action='') {
    echo '<header class="page-header"><div class="head-left">'.icon_svg('panel','panel-svg').'<div><h1>'.e($title).'</h1>';
    if ($subtitle) echo '<p>'.e($subtitle).'</p>';
    echo '</div></div>'.$action.'</header>';
}
function render_sidebar($user) {
    $role = $user['role'];
    $active = $_GET['page'] ?? ($role === 'teacher' ? 'teacher' : 'student');
    echo '<aside class="sidebar"><div class="side-brand"><span class="logo-mark"><span></span></span><div><strong>NogiK</strong><small>'.($role === 'teacher' ? 'Profesor' : 'Alumno').'</small></div></div>';
    echo '<div class="side-section"><small>Navegación</small><nav>';
    if ($role === 'student') $links = ['student'=>['Dashboard','layout'], 'skills'=>['Habilidades','radio'], 'sets'=>['Mis sets','play'], 'calendar'=>['Clases','calendar'], 'simulator'=>['Simulador','radio'], 'community'=>['Comunidad','users']];
    else $links = ['teacher'=>['Dashboard','layout'], 'students'=>['Alumnos','graduation'], 'classes'=>['Clases','calendar'], 'evaluations'=>['Evaluaciones','clipboard'], 'simulator'=>['Simulador','radio']];
    foreach ($links as $page=>$meta) {
        $class = $active === $page ? ' class="active"' : '';
        echo '<a'.$class.' href="index.php?page='.$page.'">'.icon_svg($meta[1]).'<span>'.e($meta[0]).'</span></a>';
    }
    echo '</nav></div>';
    echo '<div class="side-section quick"><small>Acciones Rápidas</small><a href="index.php?page=classes">'.icon_svg('plus').'<span>Nueva Clase</span></a><a href="index.php?page=simulator">'.icon_svg('plus').'<span>Nuevo Evento</span></a></div>';
    echo '<div class="side-user"><span class="avatar">'.avatar_for($user['name']).'</span><div><b>'.e($user['name']).'</b><small>'.e($user['email']).'</small></div><a href="index.php?page=logout">⌃</a></div></aside>';
}
function stat_card($label, $value, $sub='', $tone='cyan', $icon='') { echo '<article class="stat '.$tone.'"><div><span>'.e($label).'</span><b>'.e($value).'</b><small>'.e($sub).'</small></div><i>'.e($icon).'</i></article>'; }
function progress_bar($value) { $value = max(0, min(100, $value)); echo '<div class="progress"><span style="width:'.$value.'%"></span></div>'; }
function pill($text, $color = '') { echo '<span class="pill"'.($color ? ' style="--pill:'.$color.'"' : '').'>'.e($text).'</span>'; }
function render_student_dashboard($student, $students, $classes, $sets, $events, $skills, $tiers) {
    $mySets = student_sets($student['id'], $sets); $evaluated = array_filter($mySets, fn($s)=>$s['evaluation']);
    $avg = count($evaluated) ? array_sum(array_map(fn($s)=>$s['evaluation']['overallScore'], $evaluated)) / count($evaluated) : 0;
    render_header('Bienvenido, '.explode(' ', $student['name'])[0], 'Tu resumen de progreso como DJ', '<a class="btn primary" href="index.php?page=sets">♪ Subir Set</a>');
    echo '<section class="level-card"><div class="level-badge"><small>NIVEL</small><b>'.e($student['level']).'</b></div><div><div class="split"><h3>Experiencia hasta Nivel '.e($student['level']+1).'</h3><span>'.e($student['xp']).' / '.e($student['xpToNextLevel']).' XP</span></div>'; progress_bar(($student['xp']/$student['xpToNextLevel'])*100); echo '<p>Te faltan '.e($student['xpToNextLevel']-$student['xp']).' XP para subir de nivel</p></div></section>';
    echo '<section class="stats-grid">'; stat_card('Nivel Actual',$student['level'],'DJ en formación','cyan','▣'); stat_card('Sets Subidos',$student['totalSets'],count($evaluated).' evaluados','violet','♪'); stat_card('Clases Completadas',count($student['completedClassIds']),'clases tomadas','green','⌁'); stat_card('Puntuación Media',number_format($avg,1),'en evaluaciones','orange','★'); echo '</section>';
    $tier = tier_for($student['reputation'], $tiers); echo '<section class="card reputation"><div><h2>Reputación</h2><b style="color:'.$tier['color'].'">'.e($tier['name']).'</b><p>'.e($student['reputation']).'/100 puntos</p></div>'; progress_bar($student['reputation']); echo '</section>';
    echo '<section class="dash-grid"><article class="card"><div class="card-head"><h2>Progreso de Habilidades</h2><a href="index.php?page=skills">Ver detalle ›</a></div>'; foreach ($student['skills'] as $s) { echo '<div class="skill-row"><div><b>'.e(skill_name($s['skillId'],$skills)).'</b><small>Nivel '.e($s['level']).'/5</small></div>'; progress_bar(($s['level']/5)*100); echo '</div>'; } echo '</article><article class="card"><div class="card-head"><h2>Próximas Clases</h2><a href="index.php?page=calendar">Ver todas ›</a></div>'; render_class_list(upcoming_classes($classes), null, 2); echo '</article></section>';
    echo '<section class="card"><div class="card-head"><h2>Mis Sets Recientes</h2><a href="index.php?page=sets">Ver todos ›</a></div>'; render_set_list(array_slice($mySets,0,3), $students, false); echo '</section>';
}
function render_skills($student, $skills) {
    render_header('Mis Habilidades','Progreso detallado de tus competencias como DJ');
    echo '<section class="stats-grid">'; stat_card('Progreso Total', round((array_sum(array_map(fn($s)=>$s['level'],$student['skills']))/(count($skills)*5))*100).'%', 'habilidades', 'cyan', '◎'); stat_card('Completadas', completed_skills($student), 'finalizadas', 'green', '✓'); stat_card('En Progreso', count($skills)-completed_skills($student), 'activas', 'violet', '◷'); stat_card('Niveles Totales', array_sum(array_map(fn($s)=>$s['level'],$student['skills'])).'/'.(count($skills)*5), 'acumulados', 'orange', '↗'); echo '</section><section class="cards">';
    foreach ($student['skills'] as $s) { echo '<article class="student-card"><h2>'.e(skill_name($s['skillId'],$skills)).'</h2><p>'.e($s['status']).'</p><div class="split"><span>Nivel '.e($s['level']).'/5</span><span>'.e($s['xp']).'/'.e($s['xpToNextLevel']).' XP</span></div>'; progress_bar(($s['level']/5)*100); echo '</article>'; }
    echo '</section>';
}
function render_sets($student, $sets) { render_header('Mis Sets', count(student_sets($student['id'],$sets)).' sets subidos'); echo '<div class="tabs"><button class="tab active" data-tab="all">Todos</button><button class="tab" data-tab="evaluated">Evaluados</button><button class="tab" data-tab="pending">Pendientes</button></div>'; render_set_list(student_sets($student['id'], $sets), [], false, true); }
function render_set_list($sets, $students, $teacherMode=false, $filterable=false) {
    echo '<div class="set-list">';
    foreach ($sets as $set) {
        $student = find_by_id($students, $set['studentId']); $status = $set['evaluation'] ? 'evaluated' : 'pending';
        echo '<article class="set-card" data-status="'.$status.'"><div class="play-tile">'.icon_svg('play').'</div><div class="set-body"><h3>'.e($set['title']).'</h3>';
        if ($student) echo '<p class="set-student"><span class="mini-avatar">'.avatar_for($student['name']).'</span>'.e($student['name']).'</p>';
        echo '<p>'.e($set['description']).'</p><div class="meta">'; pill($set['genre']); echo '<span>'.icon_svg('clock').' '.e($set['duration']).' min</span><span>'.icon_svg('calendar').' '.e($set['uploadedAt']).'</span></div><div class="actions"><a class="btn dark" href="'.e($set['url']).'" target="_blank">'.icon_svg('external').' Escuchar</a>';
        if ($teacherMode && !$set['evaluation']) echo '<button class="btn primary js-evaluate" data-title="'.e($set['title']).'" data-student="'.e($student['name'] ?? 'Alumno').'" data-genre="'.e($set['genre']).'" data-duration="'.e($set['duration']).'" data-url="'.e($set['url']).'">'.icon_svg('clipboard').' Evaluar</button>';
        echo '</div>';
        if ($set['evaluation']) echo '<div class="score-line"><span>'.icon_svg('star').' '.e($set['evaluation']['overallScore']).'</span><small>+'.e($set['evaluation']['xpAwarded']).' XP</small></div>';
        echo '</div></article>';
    }
    echo '</div>';
}
function render_classes($classes, $teachers, $title, $teacherId=null) {
    $up = upcoming_classes($classes, $teacherId); $done = completed_classes($classes, $teacherId);
    $action = $teacherId ? '<button class="btn primary">⊕ Nueva Clase</button>' : '';
    render_header($title, count($teacherId ? array_filter($classes, fn($c)=>$c['teacherId']===$teacherId) : $classes).' clases totales', $action);
    echo '<div class="tabs"><button class="tab active" data-tab="upcoming">Próximas ('.count($up).')</button><button class="tab" data-tab="completed">Completadas ('.count($done).')</button></div><section class="class-list">';
    render_class_group($up, $teachers, 'upcoming'); render_class_group($done, $teachers, 'completed hidden'); echo '</section>';
}
function render_class_group($classes, $teachers, $tabClass) { echo '<div class="tab-panel '.$tabClass.'">'; foreach ($classes as $class) { $teacher = find_by_id($teachers, $class['teacherId']); echo '<article class="class-card"><h2>'.e($class['title']).'</h2><p>'.e($class['description']).'</p><div class="meta"><span>□ '.e($class['date']).'</span><span>◷ '.e($class['duration']).' min</span><span>⌁ 0 asistentes</span></div><div>'; pill('Creatividad'); pill('Transiciones'); echo '</div><a href="#">Ver detalles ›</a></article>'; } echo '</div>'; }
function render_student_simulator($student, $events) {
    render_header('Simulador de Carrera','Eventos disponibles según tu reputación');
    echo '<section class="cards three">'; foreach ($events as $event) { $locked = $event['requiredReputation'] > $student['reputation']; echo '<article class="student-card '.($locked?'locked':'').'"><div class="event-icon">◉</div><h2>'.e($event['name']).'</h2><p>'.e($event['venue']).' · '.e($event['audience']).' personas</p><p>'.e($event['styles']).'</p><div class="split"><b>$'.e($event['payment']).'</b><span>'.($locked?'Rep. '.$event['requiredReputation']:'Disponible').'</span></div></article>'; } echo '</section>';
}
function render_teacher_simulator($events) {
    render_header('Simulador de Carrera','Gestiona eventos y evalúa participaciones','<button class="btn primary">⊕ Nuevo Evento</button>');
    echo '<section class="stats-grid three-only">'; stat_card('Eventos totales',count($events),'','cyan','◉'); stat_card('Pendientes de evaluar',0,'','violet','▣'); stat_card('Evaluadas',0,'','green','✓'); echo '</section><div class="tabs"><button class="tab active">Pendientes (0)</button><button class="tab">Evaluadas (0)</button><button class="tab">Eventos ('.count($events).')</button></div><section class="empty-state"><div>✓</div><p>No hay participaciones pendientes de evaluacion</p></section>';
}
function render_community($sets, $students) { render_header('Comunidad','Escucha sets de otros alumnos'); render_set_list($sets, $students, false); }
function render_teacher_dashboard($teacher, $students, $classes, $sets, $tiers) {
    $pending = array_values(array_filter($sets, fn($s)=>!$s['evaluation'])); $mine = array_filter($classes, fn($c)=>$c['teacherId']===$teacher['id']); $doneMine = completed_classes($classes, $teacher['id']);
    render_header('Panel de Profesor','Bienvenido, '.$teacher['name'], '<div class="head-actions"><button class="btn dark">⊕ Nueva Clase</button><a class="btn primary" href="index.php?page=evaluations">▣ '.count($pending).' Pendientes</a></div>');
    echo '<section class="alert-card"><div><span>▣</span><div><h3>Tienes '.count($pending).' evaluaciones pendientes</h3><p>'.count($pending).' sets y 0 participaciones de eventos</p></div></div><a class="btn primary" href="index.php?page=evaluations">Evaluar ahora</a></section>';
    echo '<section class="stats-grid">'; stat_card('Total Alumnos',count($students),'alumnos activos','cyan',''); stat_card('Mis Clases',count($mine),count($doneMine).' completadas','violet',''); stat_card('Sets Evaluados',count($sets)-count($pending),'evaluaciones realizadas','green','♪'); stat_card('Pendientes',count($pending),'por evaluar','orange','▣'); echo '</section>';
    echo '<section class="dash-grid"><article class="card"><div class="card-head"><h2>Top Alumnos por Reputación</h2><a href="index.php?page=students">Ver todos ›</a></div>'; usort($students, fn($a,$b)=>$b['reputation']<=>$a['reputation']); $i=1; foreach (array_slice($students,0,5) as $s) { $tier=tier_for($s['reputation'],$tiers); echo '<div class="rank-row"><span class="rank">'.$i++.'</span><span class="avatar">'.avatar_for($s['name']).'</span><div><b>'.e($s['name']).'</b><small><i style="background:'.$tier['color'].'"></i>'.e($tier['name']).'</small></div><strong>Nv. '.e($s['level']).'<small>Rep: '.e($s['reputation']).'</small></strong></div>'; } echo '</article><article class="card"><div class="card-head"><h2>Próximas Clases</h2><a href="index.php?page=classes">Ver todas ›</a></div>'; render_class_list(upcoming_classes($classes, $teacher['id']), null, 2); echo '</article></section>';
    echo '<section class="card"><div class="card-head"><h2>Progreso de Alumnos</h2><a href="index.php?page=students">Ver detalle ›</a></div><div class="student-mini-grid">'; foreach ($students as $s) render_student_mini($s, $tiers); echo '</div></section>';
    echo '<section class="card"><div class="card-head"><h2>Evaluaciones Recientes</h2><a href="index.php?page=evaluations">Ver todas ›</a></div>'; foreach (array_filter($sets, fn($s)=>$s['evaluation']) as $set) { $student=find_by_id($students,$set['studentId']); echo '<div class="eval-row"><span class="avatar">'.avatar_for($student['name']).'</span><div><b>'.e($set['title']).'</b><small>'.e($student['name']).' - '.e($set['genre']).'</small></div><strong>★ '.e($set['evaluation']['overallScore']).' <small>+'.e($set['evaluation']['xpAwarded']).' XP</small></strong></div>'; } echo '</section><section class="quick-grid"><a class="quick-card" href="index.php?page=classes"><span>□</span><b>Crear Clase</b><small>Programa una nueva sesión de formación</small></a><a class="quick-card" href="index.php?page=simulator"><span>↗</span><b>Crear Evento</b><small>Diseña un evento simulado para los alumnos</small></a><a class="quick-card" href="index.php?page=students"><span>⌁</span><b>Ver Alumnos</b><small>Consulta el progreso de tus estudiantes</small></a></section>';
}
function render_class_list($classes, $teacherId=null, $limit=null) { $shown=0; foreach ($classes as $class) { if ($teacherId && $class['teacherId'] !== $teacherId) continue; if ($limit && $shown >= $limit) break; echo '<article class="mini-class"><div class="date-box"><b>MAR</b><strong>12</strong><b>NOV</b></div><div><h3>'.e($class['title']).'</h3><p>◷ 18:00 &nbsp; □ '.e($class['duration']).' min &nbsp; ⌁ 0</p></div></article>'; $shown++; } }
function render_student_mini($s, $tiers) { $tier=tier_for($s['reputation'],$tiers); $avg=avg_skill($s); echo '<article class="student-mini"><div class="mini-head"><span class="avatar">'.avatar_for($s['name']).'</span><div><b>'.e($s['name']).'</b><small>Nivel '.e($s['level']).'</small></div>'; pill($tier['name'], $tier['color']); echo '</div><div class="split"><span>Habilidades</span><b>'.number_format($avg,1).'/5</b></div>'; progress_bar(($avg/5)*100); echo '<div class="mini-foot"><span>'.count($s['completedClassIds']).' clases</span><span>'.e($s['totalSets']).' sets</span><b>'.e($s['xp']).' XP</b></div></article>'; }
function render_students($students, $tiers) {
    usort($students, fn($a,$b)=>$b['reputation']<=>$a['reputation']); render_header('Alumnos', count($students).' alumnos registrados');
    echo '<section class="filter-card"><input id="studentSearch" type="search" placeholder="⌕  Buscar por nombre o email..."><select><option>Reputación</option><option>Nivel</option><option>Nombre</option></select><select><option>Todos los tiers</option></select></section><section class="student-grid">';
    foreach ($students as $s) { $tier=tier_for($s['reputation'],$tiers); $avg=avg_skill($s); echo '<article class="student-card" data-student-card data-name="'.e(strtolower($s['name'].' '.$s['email'])).'"><div class="student-title"><span class="big-avatar">'.avatar_for($s['name']).'</span><div><h2>'.e($s['name']).'</h2><p>'.e($s['email']).'</p>'; pill($tier['name'],$tier['color']); echo '</div></div><div class="big-stats"><div><b>'.e($s['level']).'</b><span>Nivel</span></div><div><b style="color:'.$tier['color'].'">'.e($s['reputation']).'</b><span>Reputación</span></div><div><b>'.e($s['totalSets']).'</b><span>Sets</span></div></div><div class="split"><span>Habilidades</span><b>'.completed_skills($s).'/5 completadas</b></div>'; progress_bar(($avg/5)*100); echo '<div class="split"><span>XP</span><b>'.e($s['xp']).'/'.e($s['xpToNextLevel']).'</b></div>'; progress_bar(($s['xp']/$s['xpToNextLevel'])*100); echo '<footer>'.count($s['completedClassIds']).' clases completadas <span>›</span></footer></article>'; } echo '</section>';
}
function render_evaluations($sets, $students) { $teacherId = $_SESSION['user_id'] ?? null; $pending=array_values(array_filter($sets, fn($s)=>!$s['evaluation'])); $done=array_values(array_filter($sets, fn($s)=>$s['evaluation'] && (!$teacherId || ($s['evaluation']['teacherId'] ?? '') === $teacherId))); $visible = array_merge($pending, $done); render_header('Evaluaciones', count($pending).' sets pendientes de evaluación'); echo '<div class="tabs"><button class="tab active" data-tab="pending">Pendientes ('.count($pending).')</button><button class="tab" data-tab="evaluated">Evaluados ('.count($done).')</button></div>'; render_set_list($visible, $students, true); render_eval_modal(); }
function render_eval_modal() { echo '<div class="modal" id="evalModal" aria-hidden="true"><div class="modal-panel"><button class="modal-close" data-close-modal>×</button><h2>Evaluar Set: <span id="modalSetTitle">Deep Vibes Session</span></h2><div class="modal-set"><span class="avatar">👩🏻</span><div><b id="modalStudent">Demo Student</b><p><span id="modalGenre">Deep House</span> - <span id="modalDuration">45</span> min</p></div><a class="btn dark" id="modalListen" target="_blank">↗ Escuchar</a></div>'; $fields=[['tech','Técnica','Beatmatching, mezclas, uso de EQ',7],['coh','Coherencia','Flujo del set, selección musical',5],['cre','Creatividad','Originalidad, riesgos tomados',3],['ada','Adaptación','Ajuste al contexto/estilo',5]]; foreach($fields as $f){ echo '<label class="range-row"><span><b>'.$f[1].'</b><small>'.$f[2].'</small></span><output>'.$f[3].'</output><input type="range" min="1" max="10" value="'.$f[3].'" data-score></label>'; } echo '<div class="final-score"><div><b>Puntuación Final</b><p>Promedio de los 4 criterios</p></div><strong id="finalScore">5.0</strong><span>+100 XP</span><span>+0 Rep</span></div><label class="feedback-label">Feedback para el alumno<textarea placeholder="Muy bueno!"></textarea></label><div class="modal-actions"><button class="btn dark" data-close-modal>Cancelar</button><button class="btn primary" data-close-modal>✓ Enviar Evaluación</button></div></div></div>'; }
function render_not_found() { render_header('Página no disponible','La ruta solicitada no existe para este usuario.'); }
