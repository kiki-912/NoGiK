<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

$create = isset($_GET['create']) || isset($_GET['action']) && $_GET['action'] === 'create';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// CREATE CLASS FORM VIEW
if ($create) {
    // Fetch all skills
    $stmt_s = $pdo->prepare("SELECT * FROM skills");
    $stmt_s->execute();
    $skills = $stmt_s->fetchAll();
    
    render_header("Programar Clase - NogiK");
    render_sidebar();
    ?>
    <div class="flex-1 flex flex-col min-w-0 bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6 py-4">
            <a href="teacher_classes.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-foreground">Programar Nueva Clase</h1>
                <p class="text-sm text-muted-foreground">Programa una lección para los estudiantes</p>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 max-w-3xl mx-auto space-y-6 overflow-y-auto max-h-[calc(100vh-80px)] w-full">
            <form action="../Backend/scripts/actions.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="create_class">
                
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="calendar" class="h-5 w-5 text-primary"></i>
                        Detalles de la Clase
                    </h3>
                    
                    <div class="space-y-1.5">
                        <label for="title" class="text-sm font-medium text-foreground">Título de la Clase</label>
                        <input id="title" name="title" required placeholder="Ej: Fundamentos del Beatmatching" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>

                    <div class="space-y-1.5">
                        <label for="description" class="text-sm font-medium text-foreground">Descripción</label>
                        <textarea id="description" name="description" rows="4" required placeholder="Describe lo que se enseñará en esta lección..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="date" class="text-sm font-medium text-foreground">Fecha y Hora</label>
                            <input id="date" name="date" type="datetime-local" required class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label for="duration" class="text-sm font-medium text-foreground">Duración (minutos)</label>
                            <input id="duration" name="duration" type="number" min="15" max="360" value="90" required class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Skill Tags Selection -->
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="zap" class="h-5 w-5 text-primary"></i>
                        Habilidades a Desarrollar
                    </h3>
                    <p class="text-xs text-muted-foreground">Selecciona las habilidades que los estudiantes entrenarán asistiendo a esta clase.</p>
                    
                    <div class="flex flex-wrap gap-2 pt-2">
                        <?php foreach ($skills as $sk): ?>
                            <label class="flex items-center gap-2 bg-muted/20 border border-border hover:border-primary/30 p-2.5 rounded-lg text-xs cursor-pointer select-none">
                                <input type="checkbox" name="skills[]" value="<?php echo $sk['id']; ?>" class="rounded border-border text-primary focus:ring-0">
                                <span class="text-foreground font-semibold"><?php echo htmlspecialchars($sk['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Materials Builder -->
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                            <i data-lucide="file-text" class="h-5 w-5 text-primary"></i>
                            Materiales de Apoyo (Opcional)
                        </h3>
                        <button type="button" onclick="addMaterialRow()" class="text-xs bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded-lg hover:bg-primary/95 transition-colors">
                            + Agregar Material
                        </button>
                    </div>
                    
                    <!-- Materials list container -->
                    <div id="materials-container" class="space-y-3">
                        <!-- Template row generated by JS -->
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="teacher_classes.php" class="bg-card hover:bg-muted/40 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                        Programar Clase
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Material Row Builder script -->
    <script>
        function addMaterialRow() {
            const container = document.getElementById('materials-container');
            const row = document.createElement('div');
            row.className = 'material-row flex flex-col sm:flex-row gap-3 p-3 rounded bg-muted/20 border border-border/30 items-center justify-between';
            row.innerHTML = `
                <div class="flex-1 w-full space-y-1">
                    <label class="text-3xs font-semibold text-muted-foreground uppercase">Título</label>
                    <input required name="mat_title[]" placeholder="Ej: Video tutorial beatmatching" class="w-full bg-input border border-border rounded-lg px-2.5 py-1.5 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                </div>
                <div class="w-full sm:w-32 space-y-1">
                    <label class="text-3xs font-semibold text-muted-foreground uppercase">Tipo</label>
                    <select name="mat_type[]" class="w-full bg-input border border-border rounded-lg px-2.5 py-1.5 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                        <option value="video">Video</option>
                        <option value="document">Documento</option>
                        <option value="link">Enlace</option>
                    </select>
                </div>
                <div class="flex-1 w-full space-y-1">
                    <label class="text-3xs font-semibold text-muted-foreground uppercase">URL del Material</label>
                    <input required name="mat_url[]" type="url" placeholder="https://..." class="w-full bg-input border border-border rounded-lg px-2.5 py-1.5 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                </div>
                <button type="button" onclick="removeMaterialRow(this)" class="p-2 text-destructive hover:bg-destructive/10 rounded-lg mt-4 sm:mt-5">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </button>
            `;
            container.appendChild(row);
            lucide.createIcons();
        }

        function removeMaterialRow(button) {
            button.closest('.material-row').remove();
        }
    </script>
    <?php
    render_footer();
    exit();
}

// CLASS DETAILS VIEW
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
        header("Location: teacher_classes.php");
        exit();
    }
    
    // Fetch skills
    $stmt_sk = $pdo->prepare("SELECT s.name FROM class_skills cs JOIN skills s ON cs.skill_id = s.id WHERE cs.class_id = ?");
    $stmt_sk->execute([$class_id]);
    $class_skills = $stmt_sk->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch materials
    $stmt_mat = $pdo->prepare("SELECT * FROM class_materials WHERE class_id = ?");
    $stmt_mat->execute([$class_id]);
    $class_materials = $stmt_mat->fetchAll();
    
    // Fetch enrolled students
    $stmt_students = $pdo->prepare("
        SELECT u.id, u.name, u.avatar, u.email, s.level, s.reputation 
        FROM class_attendees ca
        JOIN users u ON ca.student_id = u.id
        JOIN students s ON u.id = s.user_id
        WHERE ca.class_id = ? AND ca.status = 'approved'
    ");
    $stmt_students->execute([$class_id]);
    $enrolled_students = $stmt_students->fetchAll();
    
    $cl_date = new DateTime($class_details['class_date']);
    
    render_header("Detalle de Clase - NogiK");
    render_sidebar();
    ?>
    <div class="flex-1 flex flex-col min-w-0 bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6 py-4">
            <a href="teacher_classes.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
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
                            Habilidades Entrenadas
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

                <!-- Right: Teacher & Enrolled Students (1 Col) -->
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
                            Alumnos Inscritos (<?php echo count($enrolled_students); ?>)
                        </h3>
                        
                        <?php if (empty($enrolled_students)): ?>
                            <div class="text-center py-6 text-muted-foreground">
                                <i data-lucide="users" class="h-8 w-8 mx-auto opacity-30 mb-2"></i>
                                <p class="text-xs">No hay alumnos inscritos aún.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($enrolled_students as $std): ?>
                                    <div class="flex items-center justify-between p-3 bg-muted/10 border border-border/30 rounded-xl hover:border-primary/20 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <img src="<?php echo htmlspecialchars($std['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-9 h-9 rounded-full border bg-muted flex-shrink-0">
                                            <div class="min-w-0">
                                                <a href="teacher_students.php?student_id=<?php echo $std['id']; ?>" class="text-xs font-bold text-foreground hover:text-primary transition-colors block truncate">
                                                    <?php echo htmlspecialchars($std['name']); ?>
                                                </a>
                                                <span class="text-[10px] text-muted-foreground block truncate"><?php echo htmlspecialchars($std['email']); ?></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                            <span class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 border border-primary/20 text-primary rounded-full">Nivel <?php echo $std['level']; ?></span>
                                            <span class="text-4xs text-muted-foreground">Rep: <?php echo $std['reputation']; ?></span>
                                        </div>
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

// DEFAULT CLASSES LISTING VIEW
$stmt = $pdo->prepare("
    SELECT c.*, u.name as teacher_name, 
           (SELECT COUNT(*) FROM class_attendees WHERE class_id = c.id AND status = 'approved') as attendees_count 
    FROM classes c 
    LEFT JOIN users u ON c.teacher_id = u.id 
    ORDER BY c.class_date DESC
");
$stmt->execute();
$classes = $stmt->fetchAll();

$all_classes = $classes;
$my_classes = [];
foreach ($all_classes as $cl) {
    if ($cl['teacher_id'] === $teacher['id']) {
        $my_classes[] = $cl;
    }
}

function render_classes_list($classes_list, $pdo) {
    if (empty($classes_list)) {
        ?>
        <div class="bg-card border border-border/50 rounded-xl p-12 text-center text-muted-foreground">
            <i data-lucide="calendar" class="h-12 w-12 mx-auto opacity-30 mb-4"></i>
            <p class="text-lg">No hay clases programadas en esta lista</p>
        </div>
        <?php
        return;
    }
    ?>
    <div class="grid gap-4">
        <?php foreach ($classes_list as $cl): 
            $cl_date = new DateTime($cl['class_date']);
            
            // Fetch skills
            $stmt_sk = $pdo->prepare("SELECT s.name FROM class_skills cs JOIN skills s ON cs.skill_id = s.id WHERE cs.class_id = ?");
            $stmt_sk->execute([$cl['id']]);
            $skills = $stmt_sk->fetchAll(PDO::FETCH_COLUMN);
            
            // Fetch materials count
            $stmt_m_cnt = $pdo->prepare("SELECT COUNT(*) FROM class_materials WHERE class_id = ?");
            $stmt_m_cnt->execute([$cl['id']]);
            $materials_count = $stmt_m_cnt->fetchColumn();
        ?>
            <div class="bg-card border border-border/50 rounded-xl p-6 flex flex-col md:flex-row md:items-start justify-between gap-6 hover:border-primary/20 transition-all">
                <div class="flex gap-4">
                    <div class="w-14 h-14 bg-muted border border-border text-muted-foreground rounded-xl flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-3xs font-bold leading-none"><?php echo $cl_date->format('M'); ?></span>
                        <span class="text-lg font-extrabold leading-none mt-1"><?php echo $cl_date->format('d'); ?></span>
                    </div>
                    
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-base text-foreground leading-none">
                                <a href="teacher_classes.php?class_id=<?php echo $cl['id']; ?>" class="hover:text-primary transition-colors"><?php echo htmlspecialchars($cl['title']); ?></a>
                            </h4>
                            <?php if ($cl['status'] === 'upcoming'): ?>
                                <span class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded-full">Próxima</span>
                            <?php elseif ($cl['status'] === 'completed'): ?>
                                <span class="text-3xs font-semibold px-2 py-0.5 bg-success/10 text-success border border-success/20 rounded-full">Completada</span>
                            <?php else: ?>
                                <span class="text-3xs font-semibold px-2 py-0.5 bg-destructive/10 text-destructive border border-destructive/20 rounded-full">Cancelada</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-muted-foreground leading-relaxed pt-1"><?php echo htmlspecialchars($cl['description']); ?></p>
                        
                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground pt-2">
                            <span>Profesor: <strong><?php echo htmlspecialchars($cl['teacher_name']); ?></strong></span>
                            <span>•</span>
                            <span>Duración: <strong><?php echo $cl['duration']; ?> min</strong></span>
                            <span>•</span>
                            <span>Alumnos inscritos: <strong><?php echo $cl['attendees_count']; ?></strong></span>
                            <span>•</span>
                            <span>Materiales: <strong><?php echo $materials_count; ?></strong></span>
                        </div>
                        
                        <?php if (!empty($skills)): ?>
                            <div class="flex flex-wrap gap-1.5 pt-2">
                                <?php foreach ($skills as $sk_name): ?>
                                    <span class="text-3xs font-semibold px-2 py-0.5 bg-muted rounded border border-border text-muted-foreground"><?php echo htmlspecialchars($sk_name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex-shrink-0 flex items-center md:self-center">
                    <a href="teacher_classes.php?class_id=<?php echo $cl['id']; ?>" class="inline-flex items-center gap-1.5 border border-border bg-muted/20 hover:bg-muted/40 text-foreground text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
                        <span>Ver Detalles</span>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

render_header("Clases - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Clases</h1>
            <p class="text-sm text-muted-foreground"><?php echo count($classes); ?> clases registradas en la academia</p>
        </div>
        <a href="teacher_classes.php?create=1" class="inline-flex items-center gap-2 bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-sm hover:bg-primary/90 transition-colors">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Programar Clase
        </a>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'class_created'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Clase programada con éxito! Los alumnos ya pueden verla e inscribirse.
            </div>
        <?php elseif ($error === 'empty'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Por favor, completa los campos obligatorios de la clase.
            </div>
        <?php endif; ?>

        <!-- Layout Tabs -->
        <div class="space-y-6">
            <!-- Tabs Headers -->
            <div class="flex border-b border-border">
                <button onclick="switchClassesTab('all')" id="tab-btn-all" class="tab-btn border-b-2 border-primary text-primary px-4 py-2 text-sm font-semibold focus:outline-none">
                    Todas las Clases (<?php echo count($all_classes); ?>)
                </button>
                <button onclick="switchClassesTab('my')" id="tab-btn-my" class="tab-btn text-muted-foreground border-b-2 border-transparent hover:text-foreground px-4 py-2 text-sm font-semibold focus:outline-none">
                    Mis Clases (<?php echo count($my_classes); ?>)
                </button>
            </div>

            <!-- Tab content: All Classes -->
            <div id="tab-content-all" class="classes-tab-content space-y-4">
                <?php render_classes_list($all_classes, $pdo); ?>
            </div>

            <!-- Tab content: My Classes -->
            <div id="tab-content-my" class="classes-tab-content hidden space-y-4">
                <?php render_classes_list($my_classes, $pdo); ?>
            </div>
        </div>

        <script>
        function switchClassesTab(tabId) {
            document.querySelectorAll('.classes-tab-content').forEach(el => el.classList.add('hidden'));
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

    </div>
</div>

<?php
render_footer();
?>
