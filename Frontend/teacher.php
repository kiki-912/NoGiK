<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

// Fetch counts & stats
// Total students
$stmt_stud = $pdo->prepare("SELECT COUNT(*) FROM students");
$stmt_stud->execute();
$total_students = $stmt_stud->fetchColumn();

// My classes created
$stmt_my_cl = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ?");
$stmt_my_cl->execute([$teacher['id']]);
$total_classes = $stmt_my_cl->fetchColumn();

$stmt_my_cl_comp = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND status = 'completed'");
$stmt_my_cl_comp->execute([$teacher['id']]);
$completed_classes = $stmt_my_cl_comp->fetchColumn();

// Sets evaluated by me
$stmt_evaled = $pdo->prepare("SELECT COUNT(*) FROM set_evaluations WHERE teacher_id = ?");
$stmt_evaled->execute([$teacher['id']]);
$total_sets_evaluated = $stmt_evaled->fetchColumn();

// Pending sets to evaluate (not evaluated yet)
$stmt_p_sets = $pdo->prepare("SELECT COUNT(*) FROM dj_sets WHERE id NOT IN (SELECT set_id FROM set_evaluations)");
$stmt_p_sets->execute();
$pending_sets_count = $stmt_p_sets->fetchColumn();

// Pending events to evaluate
$stmt_p_events = $pdo->prepare("SELECT COUNT(*) FROM event_participations WHERE status = 'pending'");
$stmt_p_events->execute();
$pending_events_count = $stmt_p_events->fetchColumn();

$total_pending = $pending_sets_count + $pending_events_count;

// Fetch Top students by reputation (limit 5)
$stmt_top = $pdo->prepare("
    SELECT st.*, u.name, u.avatar 
    FROM students st 
    JOIN users u ON st.user_id = u.id 
    ORDER BY st.reputation DESC 
    LIMIT 5
");
$stmt_top->execute();
$top_students = $stmt_top->fetchAll();

// Fetch my upcoming classes
$stmt_up_cl = $pdo->prepare("
    SELECT c.*, u.name as teacher_name 
    FROM classes c 
    LEFT JOIN users u ON c.teacher_id = u.id 
    WHERE c.status = 'upcoming' AND c.teacher_id = ? 
    ORDER BY c.class_date ASC 
    LIMIT 3
");
$stmt_up_cl->execute([$teacher['id']]);
$upcoming_classes = $stmt_up_cl->fetchAll();

// Fetch all students brief overview
$stmt_all_stud = $pdo->prepare("
    SELECT st.*, u.name, u.avatar, (SELECT AVG(level) FROM student_skills WHERE student_id = st.user_id) as avg_skill
    FROM students st
    JOIN users u ON st.user_id = u.id
    LIMIT 6
");
$stmt_all_stud->execute();
$students_overview = $stmt_all_stud->fetchAll();

// Fetch recent evaluations evaluated by me (limit 3)
$stmt_recent_evals = $pdo->prepare("
    SELECT se.*, s.title, s.genre, u.name as student_name
    FROM set_evaluations se
    JOIN dj_sets s ON se.set_id = s.id
    JOIN users u ON s.student_id = u.id
    WHERE se.teacher_id = ?
    ORDER BY se.evaluated_at DESC
    LIMIT 3
");
$stmt_recent_evals->execute([$teacher['id']]);
$recent_evaluations = $stmt_recent_evals->fetchAll();

render_header("Panel de Profesor - NoGiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-4 border-b border-border bg-background/95 backdrop-blur px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center gap-3 flex-auto min-w-[200px]">
            <button id="mobile-menu-toggle" type="button" class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-foreground hover:bg-muted/80 shrink-0 transition-colors" aria-label="Abrir menú">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>
            <div class="flex-auto min-w-0">
                <h1 class="whitespace-normal text-lg sm:text-xl font-bold text-foreground ">Panel de Profesor</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Bienvenido, <?php echo htmlspecialchars($teacher['name']); ?></p>
            </div>
        </div>
        
        <div class="flex items-center gap-2 flex-wrap justify-end shrink-0">
            <a href="teacher_classes.php?create=1" class="inline-flex items-center gap-1.5 border border-border bg-card hover:bg-muted/40 font-semibold px-4 py-2 rounded-lg text-xs text-foreground transition-colors">
                <i data-lucide="plus-circle" class="h-4 w-4 text-primary"></i>
                Nueva Clase
            </a>
            <?php if ($total_pending > 0): ?>
                <a href="teacher_evaluations.php" class="inline-flex items-center gap-1.5 bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                    <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    <?php echo $total_pending; ?> Pendientes
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <!-- Pending Evaluations alert banner -->
        <?php if ($total_pending > 0): ?>
            <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                        <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-foreground text-sm">Tienes <?php echo $total_pending; ?> evaluaciones pendientes</h4>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            <?php echo $pending_sets_count; ?> sets de DJ y <?php echo $pending_events_count; ?> participaciones en eventos simulados.
                        </p>
                    </div>
                </div>
                <a href="teacher_evaluations.php" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors flex-shrink-0 text-center">
                    Evaluar ahora
                </a>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-primary/10 rounded-lg text-primary">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Total Alumnos</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $total_students; ?></h3>
                    <p class="text-2xs text-muted-foreground">activos</p>
                </div>
            </div>
            <!-- Stat 2 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-secondary/10 rounded-lg text-secondary">
                    <i data-lucide="calendar" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Mis Clases</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $total_classes; ?></h3>
                    <p class="text-2xs text-muted-foreground"><?php echo $completed_classes; ?> completadas</p>
                </div>
            </div>
            <!-- Stat 3 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-success/10 rounded-lg text-success">
                    <i data-lucide="music" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Evaluados</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $total_sets_evaluated; ?></h3>
                    <p class="text-2xs text-muted-foreground">sets calificados</p>
                </div>
            </div>
            <!-- Stat 4 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-[#FF6B35]/10 rounded-lg text-[#FF6B35]">
                    <i data-lucide="clipboard-check" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Pendientes</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $total_pending; ?></h3>
                    <p class="text-2xs text-muted-foreground">por evaluar</p>
                </div>
            </div>
        </div>

        <!-- Main Grid (Top Students + Upcoming Classes) -->
        <div class="grid lg:grid-cols-2 gap-6">
            
            <!-- Top Students List -->
            <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-lg text-foreground">Top Alumnos por Reputación</h3>
                    <a href="teacher_students.php" class="text-xs text-primary hover:underline flex items-center gap-0.5">
                        Ver todos
                        <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    </a>
                </div>
                
                <div class="space-y-3">
                    <?php 
                    $pos = 1;
                    foreach ($top_students as $stud): 
                        $st_tier = get_reputation_tier($stud['reputation']);
                    ?>
                        <a href="teacher_students.php?id=<?php echo $stud['user_id']; ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/20 border border-border/30 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-xs font-bold text-muted-foreground flex-shrink-0">
                                    <?php echo $pos++; ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($stud['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($stud['name']); ?></h4>
                                    <div class="flex items-center gap-1.5 text-3xs text-muted-foreground">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?php echo $st_tier['color']; ?>"></span>
                                        <span><?php echo $st_tier['name']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-sm font-bold text-primary">Nv. <?php echo $stud['level']; ?></p>
                                <p class="text-3xs text-muted-foreground font-semibold">Rep: <?php echo $stud['reputation']; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- My Upcoming Classes -->
            <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                <h3 class="font-bold text-lg text-foreground">Mis Clases Programadas</h3>
                
                <?php if (empty($upcoming_classes)): ?>
                    <div class="text-center py-10 text-muted-foreground">
                        <i data-lucide="calendar" class="h-10 w-10 mx-auto opacity-30 mb-2"></i>
                        <p class="text-sm">No tienes clases programadas próximamente</p>
                        <a href="teacher_classes.php?create=1" class="mt-4 inline-flex text-xs bg-primary text-primary-foreground font-semibold px-3.5 py-2 rounded-lg">Programar Nueva Clase</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($upcoming_classes as $class): 
                            $class_date = new DateTime($class['class_date']);
                        ?>
                            <div class="flex items-center gap-4 p-3 rounded-lg bg-muted/20 border border-border/30">
                                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary flex-shrink-0">
                                    <i data-lucide="calendar" class="h-5 w-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-sm text-foreground truncate"><?php echo htmlspecialchars($class['title']); ?></h4>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        <?php echo $class['duration']; ?> minutos • <?php echo $class_date->format('H:i'); ?> hs
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs font-semibold text-primary"><?php echo $class_date->format('d M'); ?></p>
                                    <a href="teacher_classes.php" class="text-3xs text-primary hover:underline block mt-0.5">Ver detalles</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Progress Summary (grid of cards) -->
        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg text-foreground">Progreso de Alumnos</h3>
                <a href="teacher_students.php" class="text-xs text-primary hover:underline flex items-center gap-0.5">
                    Ver todos los alumnos
                    <i data-lucide="chevron-right" class="h-3 w-3"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <?php foreach ($students_overview as $stud): 
                    $st_tier = get_reputation_tier($stud['reputation']);
                    $avg_sk_val = floatval($stud['avg_skill']);
                ?>
                    <a href="teacher_students.php?id=<?php echo $stud['user_id']; ?>" class="p-4 rounded-lg border border-border/50 bg-muted/10 hover:border-primary/30 transition-all space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($stud['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-sm text-foreground truncate leading-tight"><?php echo htmlspecialchars($stud['name']); ?></h4>
                                <p class="text-xs text-muted-foreground leading-tight mt-0.5">Nivel <?php echo $stud['level']; ?></p>
                            </div>
                            <span class="text-3xs font-semibold px-2 py-0.5 rounded border" style="color: <?php echo $st_tier['color']; ?>; border-color: <?php echo $st_tier['color']; ?>; background-color: <?php echo $st_tier['color']; ?>10;">
                                <?php echo explode(' ', $st_tier['name'])[0]; ?>
                            </span>
                        </div>

                        <!-- Skill meter -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-3xs text-muted-foreground">
                                <span>Habilidades</span>
                                <span class="font-bold text-foreground"><?php echo number_format($avg_sk_val, 1); ?>/5</span>
                            </div>
                            <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: <?php echo ($avg_sk_val / 5) * 100; ?>%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-border/30 text-3xs text-muted-foreground">
                            <span><?php echo $stud['total_sets']; ?> sets</span>
                            <span class="text-primary font-bold"><?php echo $stud['xp']; ?> XP</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Evaluations -->
        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
            <h3 class="font-bold text-lg text-foreground">Evaluaciones Recientes Realizadas</h3>
            
            <?php if (empty($recent_evaluations)): ?>
                <p class="text-xs text-muted-foreground">No has realizado ninguna evaluación recientemente.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_evaluations as $reval): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-muted/20 border border-border/30">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-success/10 text-success rounded-lg">
                                    <i data-lucide="check-square" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($reval['title']); ?></h4>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        <?php echo htmlspecialchars($reval['student_name']); ?> • <?php echo htmlspecialchars($reval['genre']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-0.5 text-xs text-[#39FF14] font-bold bg-[#39FF14]/10 px-2 py-0.5 rounded">
                                    <i data-lucide="star" class="h-3.5 w-3.5 fill-current"></i>
                                    <?php echo round($reval['overall_score'], 1); ?>
                                </span>
                                <span class="text-3xs font-semibold px-2 py-0.5 bg-muted rounded border border-border text-muted-foreground">
                                    +<?php echo $reval['xp_awarded']; ?> XP
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick actions buttons -->
        <div class="grid md:grid-cols-3 gap-4">
            <a href="teacher_classes.php?create=1" class="bg-card border border-border/50 hover:border-primary/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mx-auto mb-3">
                    <i data-lucide="calendar" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Crear Clase</h4>
                <p class="text-xs text-muted-foreground mt-1">Programa una nueva sesión de formación</p>
            </a>
            
            <a href="teacher_evaluations.php" class="bg-card border border-border/50 hover:border-secondary/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-secondary mx-auto mb-3">
                    <i data-lucide="clipboard-check" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Evaluar Trabajos</h4>
                <p class="text-xs text-muted-foreground mt-1">Revisa setlists y mezclas presentadas</p>
            </a>

            <a href="teacher_students.php" class="bg-card border border-border/50 hover:border-success/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center text-success mx-auto mb-3">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Ver Alumnos</h4>
                <p class="text-xs text-muted-foreground mt-1">Consulta el perfil y evolución de tus estudiantes</p>
            </a>
        </div>

    </div>
</div>

<?php
render_footer();
?>
