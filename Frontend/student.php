<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

// 1. Fetch counts & stats
// Total sets uploaded
$stmt_sets = $pdo->prepare("SELECT COUNT(*) FROM dj_sets WHERE student_id = ?");
$stmt_sets->execute([$student['id']]);
$total_sets = $stmt_sets->fetchColumn();

// Total classes attended (completed)
$stmt_classes = $pdo->prepare("SELECT COUNT(*) FROM class_attendees ca JOIN classes c ON ca.class_id = c.id WHERE ca.student_id = ? AND c.status = 'completed'");
$stmt_classes->execute([$student['id']]);
$completed_classes = $stmt_classes->fetchColumn();

// Average score from evaluations
$stmt_avg = $pdo->prepare("SELECT AVG(se.overall_score) FROM set_evaluations se JOIN dj_sets s ON se.set_id = s.id WHERE s.student_id = ?");
$stmt_avg->execute([$student['id']]);
$average_score = $stmt_avg->fetchColumn();
$average_score = $average_score ? round($average_score, 1) : 0.0;

// Total evaluations done
$stmt_eval_count = $pdo->prepare("SELECT COUNT(*) FROM set_evaluations se JOIN dj_sets s ON se.set_id = s.id WHERE s.student_id = ?");
$stmt_eval_count->execute([$student['id']]);
$evaluated_sets_count = $stmt_eval_count->fetchColumn();

// XP percent progress
$xp_pct = ($student['xp'] / $student['xp_to_next_level']) * 100;

// 2. Fetch upcoming classes (limit 3)
$stmt_up_cl = $pdo->prepare("SELECT c.*, u.name as teacher_name FROM classes c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.status = 'upcoming' ORDER BY c.class_date ASC LIMIT 3");
$stmt_up_cl->execute();
$upcoming_classes = $stmt_up_cl->fetchAll();

// 3. Fetch skills progress list
$stmt_skills = $pdo->prepare("SELECT ss.*, s.name, s.category FROM student_skills ss JOIN skills s ON ss.skill_id = s.id WHERE ss.student_id = ?");
$stmt_skills->execute([$student['id']]);
$student_skills = $stmt_skills->fetchAll();

// 4. Fetch recent sets (limit 3)
$stmt_recent_sets = $pdo->prepare("SELECT s.*, se.overall_score FROM dj_sets s LEFT JOIN set_evaluations se ON s.id = se.set_id WHERE s.student_id = ? ORDER BY s.uploaded_at DESC LIMIT 3");
$stmt_recent_sets->execute([$student['id']]);
$recent_sets = $stmt_recent_sets->fetchAll();

$tier = get_reputation_tier($student['reputation']);

render_header("Dashboard - NoGiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background w-full max-w-full md:max-w-none">
    <!-- Header -->
    <header class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-y-3 border-b border-border bg-background/95 backdrop-blur px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center gap-3 flex-auto min-w-[200px]">
            <button id="mobile-menu-toggle" type="button" class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-foreground hover:bg-muted/80 shrink-0 transition-colors" aria-label="Abrir menú">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>
            <div class="flex-auto min-w-0">
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">
                Bienvenido, <?php echo htmlspecialchars(explode(' ', $student['name'])[0]); ?>
            </h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Tu resumen de progreso como DJ</p>
            </div>
        </div>
        
        <a href="student_sets.php" class="inline-flex items-center gap-2 bg-primary text-primary-foreground font-semibold px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs sm:text-sm hover:bg-primary/90 transition-colors shrink-0">
            <i data-lucide="music" class="h-4 w-4"></i>
            Subir Set
        </a>
    </header>

    <!-- Content -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <!-- Level & XP Banner -->
        <div class="rounded-xl border border-border/50 bg-gradient-to-r from-card via-card to-primary/5 p-4 sm:p-6 overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <!-- Level Circle -->
                <div class="relative flex-shrink-0 mx-auto md:mx-0">
                    <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center border-4 border-primary">
                        <div class="text-center">
                            <span class="text-3xl font-extrabold text-primary"><?php echo $student['level']; ?></span>
                            <p class="text-3xs font-semibold text-primary/80 uppercase tracking-wider">Nivel</p>
                        </div>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-white">
                        <i data-lucide="zap" class="h-4 w-4"></i>
                    </div>
                </div>

                <!-- XP Progress -->
                <div class="flex-1 space-y-2 text-center md:text-left">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <span class="text-sm font-medium text-foreground">
                            Experiencia hasta Nivel <?php echo $student['level'] + 1; ?>
                        </span>
                        <span class="text-xs text-muted-foreground">
                            <?php echo $student['xp']; ?> / <?php echo $student['xp_to_next_level']; ?> XP
                        </span>
                    </div>
                    <div class="w-full h-3 bg-muted rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full transition-all progress-fill" style="width: <?php echo $xp_pct; ?>%"></div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Te faltan <?php echo $student['xp_to_next_level'] - $student['xp']; ?> XP para subir de nivel
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-primary/10 rounded-lg text-primary">
                    <i data-lucide="trophy" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Nivel</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $student['level']; ?></h3>
                    <p class="text-2xs text-muted-foreground">DJ en formación</p>
                </div>
            </div>
            <!-- Stat 2 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-secondary/10 rounded-lg text-secondary">
                    <i data-lucide="music" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Sets Subidos</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $total_sets; ?></h3>
                    <p class="text-2xs text-muted-foreground"><?php echo $evaluated_sets_count; ?> evaluados</p>
                </div>
            </div>
            <!-- Stat 3 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-success/10 rounded-lg text-success">
                    <i data-lucide="graduation-cap" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Clases</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $completed_classes; ?></h3>
                    <p class="text-2xs text-muted-foreground">completadas</p>
                </div>
            </div>
            <!-- Stat 4 -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                <div class="p-3 bg-[#FF6B35]/10 rounded-lg text-[#FF6B35]">
                    <i data-lucide="star" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Nota Media</p>
                    <h3 class="text-xl font-bold text-foreground mt-0.5"><?php echo $average_score; ?></h3>
                    <p class="text-2xs text-muted-foreground">en evaluaciones</p>
                </div>
            </div>
        </div>

        <!-- Reputation Info Banner -->
        <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-background font-bold text-lg flex-shrink-0" style="background-color: <?php echo $tier['color']; ?>">
                    <i data-lucide="award" class="h-6 w-6"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-foreground truncate"><?php echo $tier['name']; ?></h4>
                        <span class="text-xs text-muted-foreground px-2 py-0.5 bg-muted rounded-full flex-shrink-0">Rep: <?php echo $student['reputation']; ?></span>
                    </div>
                    <p class="text-sm text-muted-foreground mt-1"><?php echo $tier['desc']; ?></p>
                </div>
            </div>
        </div>

        <!-- Main Grid (Skills Progress + Upcoming Events) -->
        <div class="grid lg:grid-cols-2 gap-6">
            
            <!-- Skills list -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-lg text-foreground">Progreso de Habilidades</h3>
                    <a href="student_skills.php" class="text-xs text-primary hover:underline flex items-center gap-0.5">
                        Ver detalles
                        <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    </a>
                </div>
                
                <div class="space-y-4">
                    <?php foreach ($student_skills as $skill): 
                        $skill_pct = ($skill['xp'] / $skill['xp_to_next_level']) * 100;
                    ?>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs">
                                <span class="font-semibold text-foreground"><?php echo htmlspecialchars($skill['name']); ?></span>
                                <span class="text-muted-foreground">Nivel <?php echo $skill['level']; ?>/5 (<?php echo $skill['xp']; ?>/<?php echo $skill['xp_to_next_level']; ?> XP)</span>
                            </div>
                            <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: <?php echo $skill_pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Upcoming Events / Classes -->
            <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-6 space-y-4">
                <h3 class="font-bold text-lg text-foreground">Próximas Clases</h3>
                
                <?php if (empty($upcoming_classes)): ?>
                    <div class="text-center py-6 text-muted-foreground">
                        <p class="text-sm">No hay clases programadas próximamente</p>
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
                                        <?php echo htmlspecialchars($class['teacher_name']); ?> • <?php echo $class['duration']; ?> min
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs font-semibold text-primary"><?php echo $class_date->format('d M'); ?></p>
                                    <p class="text-2xs text-muted-foreground mt-0.5"><?php echo $class_date->format('H:i'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Recent Sets uploaded -->
        <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg text-foreground">Mis Sets Recientes</h3>
                <a href="student_sets.php" class="text-xs text-primary hover:underline flex items-center gap-0.5">
                    Ver todos
                    <i data-lucide="chevron-right" class="h-3 w-3"></i>
                </a>
            </div>

            <?php if (empty($recent_sets)): ?>
                <div class="text-center py-10 text-muted-foreground">
                    <i data-lucide="music-4" class="h-12 w-12 mx-auto opacity-30 mb-2"></i>
                    <p class="text-sm">Aún no has subido ningún set</p>
                    <a href="student_sets.php" class="mt-4 inline-flex text-xs bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded-lg">Subir mi primer set</a>
                </div>
            <?php else: ?>
                <div class="grid gap-3">
                    <?php foreach ($recent_sets as $set): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-muted/20 border border-border/30 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded bg-secondary/10 flex items-center justify-center text-secondary">
                                    <i data-lucide="play" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-sm text-foreground truncate"><?php echo htmlspecialchars($set['title']); ?></h4>
                                    <p class="text-xs text-muted-foreground flex items-center gap-2 mt-0.5">
                                        <span class="px-1.5 py-0.5 bg-muted rounded text-2xs"><?php echo htmlspecialchars($set['genre']); ?></span>
                                        <span><?php echo $set['duration']; ?> min</span>
                                    </p>
                                </div>
                            </div>
                            
                            <div>
                                <?php if ($set['overall_score']): ?>
                                    <div class="flex items-center gap-1 text-[#39FF14] text-sm font-bold bg-[#39FF14]/10 px-2 py-1 rounded">
                                        <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                                        <span><?php echo round($set['overall_score'], 1); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-muted-foreground px-2.5 py-1 bg-muted rounded-full">Pendiente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions links -->
        <div class="grid md:grid-cols-3 gap-4">
            <a href="student_simulator.php" class="bg-card border border-border/50 hover:border-primary/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mx-auto mb-3">
                    <i data-lucide="trending-up" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Simulador de Carrera</h4>
                <p class="text-xs text-muted-foreground mt-1">Participa en eventos simulados y gana reputación</p>
            </a>
            
            <a href="student_community.php" class="bg-card border border-border/50 hover:border-secondary/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-secondary mx-auto mb-3">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Comunidad</h4>
                <p class="text-xs text-muted-foreground mt-1">Escucha sets de otros alumnos y deja comentarios</p>
            </a>

            <a href="student_calendar.php" class="bg-card border border-border/50 hover:border-success/40 rounded-xl p-5 text-center transition-colors">
                <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center text-success mx-auto mb-3">
                    <i data-lucide="graduation-cap" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-foreground">Calendario</h4>
                <p class="text-xs text-muted-foreground mt-1">Consulta las próximas clases y eventos</p>
            </a>
        </div>

    </div>
</div>

<?php
render_footer();
?>
