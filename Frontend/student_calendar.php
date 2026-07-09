<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

// Fetch all classes
$stmt_classes = $pdo->prepare("SELECT c.*, u.name as teacher_name FROM classes c LEFT JOIN users u ON c.teacher_id = u.id ORDER BY c.class_date ASC");
$stmt_classes->execute();
$classes = $stmt_classes->fetchAll();

// Fetch classes this student is registered to
$stmt_att = $pdo->prepare("SELECT class_id, status FROM class_attendees WHERE student_id = ?");
$stmt_att->execute([$student['id']]);
$registered_classes_raw = $stmt_att->fetchAll();

$registered_class_ids = [];
$pending_class_ids = [];
foreach ($registered_classes_raw as $rc) {
    if ($rc['status'] === 'approved') {
        $registered_class_ids[] = $rc['class_id'];
    } elseif ($rc['status'] === 'pending') {
        $pending_class_ids[] = $rc['class_id'];
    }
}

// Filter lists
$upcoming_classes = [];
$past_classes = [];

foreach ($classes as $class) {
    if ($class['status'] === 'upcoming') {
        $upcoming_classes[] = $class;
    } else {
        $past_classes[] = $class;
    }
}

$success = $_GET['success'] ?? '';
$info = $_GET['info'] ?? '';
?>

<?php
$class_id = $_GET['class_id'] ?? '';
if (!empty($class_id)) {
    $stmt_class = $pdo->prepare("
        SELECT c.*, u.name as teacher_name, u.avatar as teacher_avatar, u.email as teacher_email 
        FROM classes c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        WHERE c.id = ?
    ");
    $stmt_class->execute([$class_id]);
    $class_details = $stmt_class->fetch();
    
    if (!$class_details) {
        header("Location: student_calendar.php");
        exit();
    }
    
    $is_registered = in_array($class_id, $registered_class_ids);
    
    // Fetch skills
    $stmt_sk = $pdo->prepare("SELECT s.name FROM class_skills cs JOIN skills s ON cs.skill_id = s.id WHERE cs.class_id = ?");
    $stmt_sk->execute([$class_id]);
    $class_skills = $stmt_sk->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch materials
    $stmt_mat = $pdo->prepare("SELECT * FROM class_materials WHERE class_id = ?");
    $stmt_mat->execute([$class_id]);
    $class_materials = $stmt_mat->fetchAll();
    
    // Fetch classmates
    $stmt_classmates = $pdo->prepare("
        SELECT u.name, u.avatar, s.level 
        FROM class_attendees ca
        JOIN users u ON ca.student_id = u.id
        JOIN students s ON u.id = s.user_id
        WHERE ca.class_id = ? AND ca.student_id != ? AND ca.status = 'approved'
    ");
    $stmt_classmates->execute([$class_id, $student['id']]);
    $classmates = $stmt_classmates->fetchAll();
    
    $cl_date = new DateTime($class_details['class_date']);
    
    render_header("Detalle de Clase - NogiK");
    render_sidebar();
    ?>
    <div class="flex-1 flex flex-col min-w-0 bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6 py-4">
            <a href="student_calendar.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-foreground"><?php echo htmlspecialchars($class_details['title']); ?></h1>
                <p class="text-sm text-muted-foreground">
                    Dictada por <?php echo htmlspecialchars($class_details['teacher_name']); ?> • 
                    <?php echo $cl_date->format('d/m/Y H:i'); ?> hs • 
                    <?php echo $class_details['duration']; ?> min
                </p>
            </div>
            
            <div class="flex-shrink-0">
                <?php if ($class_details['status'] === 'upcoming'): ?>
                    <?php if ($is_registered): ?>
                        <span class="inline-flex items-center gap-1.5 bg-success/15 border border-success/30 text-success text-xs font-bold px-4 py-2 rounded-lg">
                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                            Inscrito
                        </span>
                    <?php elseif (in_array($class_id, $pending_class_ids)): ?>
                        <span class="inline-flex items-center gap-1.5 bg-warning/15 border border-warning/30 text-warning text-xs font-bold px-4 py-2 rounded-lg">
                            <i data-lucide="clock" class="h-4 w-4"></i>
                            Pendiente de Aprobación
                        </span>
                    <?php else: ?>
                        <form action="../Backend/scripts/actions.php" method="POST">
                            <input type="hidden" name="action" value="join_class">
                            <input type="hidden" name="class_id" value="<?php echo $class_details['id']; ?>">
                            <button type="submit" class="bg-primary text-primary-foreground font-bold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors shadow-lg shadow-primary/10">
                                Solicitar Unirse
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 bg-muted border border-border text-muted-foreground text-xs px-3 py-1.5 rounded-lg">
                        Finalizada
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Class Details (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                        <h3 class="font-bold text-sm text-foreground uppercase tracking-wider border-b border-border/30 pb-2 flex items-center gap-2">
                            <i data-lucide="info" class="h-4.5 w-4.5 text-primary"></i>
                            Descripción de la Clase
                        </h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($class_details['description'])); ?>
                        </p>
                    </div>

                    <!-- Skills -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                        <h3 class="font-bold text-sm text-foreground uppercase tracking-wider border-b border-border/30 pb-2 flex items-center gap-2">
                            <i data-lucide="zap" class="h-4.5 w-4.5 text-primary"></i>
                            Habilidades a Desarrollar
                        </h3>
                        <?php if (empty($class_skills)): ?>
                            <p class="text-xs text-muted-foreground">No se especificaron habilidades para esta clase.</p>
                        <?php else: ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($class_skills as $sk_name): ?>
                                    <span class="text-xs font-semibold px-2.5 py-1 bg-muted rounded-lg border border-border text-foreground">
                                        <?php echo htmlspecialchars($sk_name); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Materials -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                        <h3 class="font-bold text-sm text-foreground uppercase tracking-wider border-b border-border/30 pb-2 flex items-center gap-2">
                            <i data-lucide="file-text" class="h-4.5 w-4.5 text-primary"></i>
                            Materiales Didácticos
                        </h3>
                        <?php if (empty($class_materials)): ?>
                            <p class="text-xs text-muted-foreground">No hay materiales de apoyo registrados para esta clase.</p>
                        <?php else: ?>
                            <div class="grid gap-2">
                                <?php foreach ($class_materials as $mat): 
                                    $icon = 'link';
                                    if ($mat['type'] === 'video') $icon = 'play-circle';
                                    if ($mat['type'] === 'document') $icon = 'file-text';
                                ?>
                                    <a href="<?php echo htmlspecialchars($mat['url']); ?>" target="_blank" class="flex items-center justify-between p-3.5 bg-muted/20 hover:bg-muted/40 border border-border/30 rounded-xl transition-colors">
                                        <div class="flex items-center gap-3">
                                            <i data-lucide="<?php echo $icon; ?>" class="h-5 w-5 text-primary"></i>
                                            <span class="text-xs font-semibold text-foreground"><?php echo htmlspecialchars($mat['title']); ?></span>
                                        </div>
                                        <span class="text-[10px] bg-primary/10 text-primary border border-primary/20 px-2 py-0.5 rounded-full capitalize"><?php echo $mat['type']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Teacher & Classmates (1 Col) -->
                <div class="space-y-6">
                    <!-- Profesor de la clase -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h3 class="font-bold text-sm text-foreground uppercase tracking-wider border-b border-border/30 pb-2 flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="h-4.5 w-4.5 text-primary"></i>
                            Profesor de la Clase
                        </h3>
                        <div class="flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($class_details['teacher_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted flex-shrink-0">
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-foreground truncate"><?php echo htmlspecialchars($class_details['teacher_name']); ?></h4>
                                <span class="text-[10px] text-muted-foreground block truncate"><?php echo htmlspecialchars($class_details['teacher_email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h3 class="font-bold text-sm text-foreground uppercase tracking-wider border-b border-border/30 pb-2 flex items-center gap-2">
                            <i data-lucide="users" class="h-4.5 w-4.5 text-secondary"></i>
                            Compañeros Inscritos (<?php echo count($classmates); ?>)
                        </h3>
                        
                        <?php if (empty($classmates)): ?>
                            <div class="text-center py-6 text-muted-foreground">
                                <i data-lucide="users" class="h-8 w-8 mx-auto opacity-30 mb-2"></i>
                                <p class="text-xs">Eres el único alumno inscrito por ahora.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($classmates as $cm): ?>
                                    <div class="flex items-center justify-between p-3 bg-muted/10 border border-border/30 rounded-xl">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <img src="<?php echo htmlspecialchars($cm['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0">
                                            <span class="text-xs font-semibold text-foreground truncate"><?php echo htmlspecialchars($cm['name']); ?></span>
                                        </div>
                                        <span class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 border border-primary/20 text-primary rounded-full">Nivel <?php echo $cm['level']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    render_footer();
    exit();
}

render_header("Calendario de Clases - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Calendario de Clases</h1>
            <p class="text-sm text-muted-foreground">Mantente al tanto de tus próximas lecciones y sesiones formativas</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'joined'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Te has registrado correctamente en la clase!
            </div>
        <?php elseif ($info === 'already_joined'): ?>
            <div class="bg-primary/10 border border-primary/20 text-primary text-sm rounded-lg p-3">
                Ya estás registrado en esta clase.
            </div>
        <?php endif; ?>

        <!-- Layout Tabs -->
        <div class="space-y-6">
            <!-- Tabs Headers -->
            <div class="flex border-b border-border">
                <button onclick="switchTab('upcoming')" id="tab-btn-upcoming" class="tab-btn border-b-2 border-primary text-primary px-4 py-2 text-sm font-semibold focus:outline-none">
                    Próximas Clases (<?php echo count($upcoming_classes); ?>)
                </button>
                <button onclick="switchTab('past')" id="tab-btn-past" class="tab-btn text-muted-foreground border-b-2 border-transparent hover:text-foreground px-4 py-2 text-sm font-semibold focus:outline-none">
                    Clases Completadas / Historial (<?php echo count($past_classes); ?>)
                </button>
            </div>

            <!-- Tab: Upcoming -->
            <div id="tab-content-upcoming" class="tab-content space-y-4">
                <?php if (empty($upcoming_classes)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-10 text-center text-muted-foreground">
                        <i data-lucide="calendar" class="h-12 w-12 mx-auto opacity-30 mb-2"></i>
                        <p>No hay próximas clases programadas</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($upcoming_classes as $class): 
                            $class_date = new DateTime($class['class_date']);
                            $is_registered = in_array($class['id'], $registered_class_ids);
                            
                            // Get class skills
                            $stmt_sk = $pdo->prepare("SELECT s.name FROM class_skills cs JOIN skills s ON cs.skill_id = s.id WHERE cs.class_id = ?");
                            $stmt_sk->execute([$class['id']]);
                            $skills = $stmt_sk->fetchAll(PDO::FETCH_COLUMN);
                        ?>
                            <div class="bg-card border border-border/50 rounded-xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div class="flex gap-4">
                                    <div class="w-14 h-14 bg-primary/10 rounded-xl flex flex-col items-center justify-center text-primary flex-shrink-0">
                                        <span class="text-xs font-bold leading-none"><?php echo $class_date->format('M'); ?></span>
                                        <span class="text-lg font-extrabold leading-none mt-1"><?php echo $class_date->format('d'); ?></span>
                                    </div>
                                    <div class="space-y-1">
                                        <h4 class="font-bold text-base text-foreground">
                                            <a href="student_calendar.php?class_id=<?php echo $class['id']; ?>" class="hover:text-primary transition-colors"><?php echo htmlspecialchars($class['title']); ?></a>
                                        </h4>
                                        <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($class['description']); ?></p>
                                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground pt-1">
                                            <span class="flex items-center gap-1">
                                                <i data-lucide="user" class="h-3.5 w-3.5"></i>
                                                <?php echo htmlspecialchars($class['teacher_name']); ?>
                                            </span>
                                            <span>•</span>
                                            <span class="flex items-center gap-1">
                                                <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                                <?php echo $class['duration']; ?> minutos
                                            </span>
                                            <span>•</span>
                                            <span class="flex items-center gap-1">
                                                <i data-lucide="watch" class="h-3.5 w-3.5"></i>
                                                <?php echo $class_date->format('H:i'); ?> hs
                                            </span>
                                        </div>
                                        <?php if (!empty($skills)): ?>
                                            <div class="flex flex-wrap gap-1.5 pt-2">
                                                <?php foreach ($skills as $skill_name): ?>
                                                    <span class="text-3xs font-semibold px-2 py-0.5 bg-muted rounded border border-border text-muted-foreground">
                                                        <?php echo htmlspecialchars($skill_name); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <a href="student_calendar.php?class_id=<?php echo $class['id']; ?>" class="inline-flex items-center border border-border bg-muted/20 hover:bg-muted/40 text-foreground text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        Ver Detalles
                                    </a>
                                    <?php if ($is_registered): ?>
                                        <span class="inline-flex items-center gap-1 bg-success/15 border border-success/30 text-success text-xs font-semibold px-3 py-1.5 rounded-lg">
                                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                                            Registrado
                                        </span>
                                    <?php elseif (in_array($class['id'], $pending_class_ids)): ?>
                                        <span class="inline-flex items-center gap-1 bg-warning/15 border border-warning/30 text-warning text-xs font-semibold px-3 py-1.5 rounded-lg">
                                            <i data-lucide="clock" class="h-4 w-4"></i>
                                            Pendiente
                                        </span>
                                    <?php else: ?>
                                        <form action="../Backend/scripts/actions.php" method="POST">
                                            <input type="hidden" name="action" value="join_class">
                                            <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                            <button type="submit" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                                                Unirse a Clase
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Past -->
            <div id="tab-content-past" class="tab-content hidden space-y-4">
                <?php if (empty($past_classes)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-10 text-center text-muted-foreground">
                        <i data-lucide="calendar" class="h-12 w-12 mx-auto opacity-30 mb-2"></i>
                        <p>No tienes clases completadas en el historial</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($past_classes as $class): 
                            $class_date = new DateTime($class['class_date']);
                            $is_attended = in_array($class['id'], $registered_class_ids);
                            
                            // Fetch materials
                            $stmt_m = $pdo->prepare("SELECT * FROM class_materials WHERE class_id = ?");
                            $stmt_m->execute([$class['id']]);
                            $materials = $stmt_m->fetchAll();
                        ?>
                            <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-muted rounded-lg flex items-center justify-center text-muted-foreground">
                                            <i data-lucide="check-square" class="h-5 w-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-base text-foreground">
                                                <a href="student_calendar.php?class_id=<?php echo $class['id']; ?>" class="hover:text-primary transition-colors"><?php echo htmlspecialchars($class['title']); ?></a>
                                            </h4>
                                            <p class="text-xs text-muted-foreground mt-0.5">
                                                Clase completada el <?php echo $class_date->format('d/m/Y'); ?> por <?php echo htmlspecialchars($class['teacher_name']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <a href="student_calendar.php?class_id=<?php echo $class['id']; ?>" class="inline-flex items-center border border-border bg-muted/20 hover:bg-muted/40 text-foreground text-xs font-semibold px-2 py-1 rounded transition-colors">
                                            Ver Detalles
                                        </a>
                                        <?php if ($is_attended): ?>
                                            <span class="inline-flex items-center gap-1 bg-success/10 border border-success/20 text-success text-xs font-semibold px-2 py-1 rounded">
                                                Asistido
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-muted border border-border text-muted-foreground text-xs px-2 py-1 rounded">
                                                No registrado
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Materials -->
                                <?php if (!empty($materials)): ?>
                                    <div class="pt-3 border-t border-border/30">
                                        <p class="text-xs font-semibold text-foreground mb-2">Materiales de la Clase:</p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($materials as $mat): 
                                                $icon = 'link';
                                                if ($mat['type'] === 'video') $icon = 'play-circle';
                                                if ($mat['type'] === 'document') $icon = 'file-text';
                                            ?>
                                                <a href="<?php echo htmlspecialchars($mat['url']); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline bg-primary/5 border border-primary/20 px-2.5 py-1 rounded-md">
                                                    <i data-lucide="<?php echo $icon; ?>" class="h-3.5 w-3.5"></i>
                                                    <?php echo htmlspecialchars($mat['title']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('tab-content-' + tabId).classList.remove('hidden');
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-primary', 'text-primary');
            btn.classList.add('text-muted-foreground', 'border-transparent');
        });
        
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        activeBtn.classList.remove('text-muted-foreground', 'border-transparent');
        activeBtn.classList.add('border-primary', 'text-primary');
    }
</script>

<?php
render_footer();
?>
