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
    <div class="flex-1 flex flex-col min-w-0 bg-background w-full max-w-full md:max-w-none">
        <!-- Header -->
        <header class="sticky top-0 z-40 flex flex-wrap items-center gap-4 gap-y-3 border-b border-border bg-background/95 backdrop-blur px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center gap-3 flex-auto min-w-[200px]">
            <button id="mobile-menu-toggle" type="button" class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-foreground hover:bg-muted/80 shrink-0 transition-colors" aria-label="Abrir menú">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>
            <div class="flex-auto min-w-0">
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground "><?php echo htmlspecialchars($class_details['title']); ?></h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">
                    Dictada por <?php echo htmlspecialchars($class_details['teacher_name']); ?> • 
                    <?php echo $cl_date->format('d/m/Y H:i'); ?> hs • 
                    <?php echo $class_details['duration']; ?> min
                </p>
            </div>
        </div>
            <a href="student_calendar.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground shrink-0">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            
            
            <div class="shrink-0">
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
        <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
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
<div class="flex-1 flex flex-col min-w-0 bg-background w-full max-w-full md:max-w-none">
    <!-- Header -->
    <header class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-y-3 border-b border-border bg-background/95 backdrop-blur px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center gap-3 flex-auto min-w-[200px]">
            <button id="mobile-menu-toggle" type="button" class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-foreground hover:bg-muted/80 shrink-0 transition-colors" aria-label="Abrir menú">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>
            <div class="flex-auto min-w-0">
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">Calendario de Clases</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Mantente al tanto de tus próximas lecciones y sesiones formativas</p>
            </div>
        </div>
        
    </header>

    <!-- Content -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <?php if ($success === 'joined'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Te has registrado correctamente en la clase!
            </div>
        <?php elseif ($info === 'already_joined'): ?>
            <div class="bg-primary/10 border border-primary/20 text-primary text-sm rounded-lg p-3">
                Ya estás registrado en esta clase.
            </div>
        <?php endif; ?>
        <?php
        // Persistencia de Vista y Fecha del Calendario
        if (isset($_GET['view'])) {
            $view = $_GET['view'];
            $_SESSION['classes_view_mode'] = $view;
            setcookie('classes_view_mode', $view, time() + (86400 * 30), '/');
        } else {
            $view = $_SESSION['classes_view_mode'] ?? $_COOKIE['classes_view_mode'] ?? 'calendar';
        }

        if ($view === 'calendar'):
            // Calendar math
            if (isset($_GET['month'])) {
                $month = intval($_GET['month']);
                $_SESSION['calendar_month'] = $month;
                setcookie('calendar_month', $month, time() + (86400 * 30), '/');
            } else {
                $month = $_SESSION['calendar_month'] ?? $_COOKIE['calendar_month'] ?? intval(date('m'));
            }

            if (isset($_GET['year'])) {
                $year = intval($_GET['year']);
                $_SESSION['calendar_year'] = $year;
                setcookie('calendar_year', $year, time() + (86400 * 30), '/');
            } else {
                $year = $_SESSION['calendar_year'] ?? $_COOKIE['calendar_year'] ?? intval(date('Y'));
            }

            $prev_month = $month - 1;
            $prev_year = $year;
            if ($prev_month < 1) {
                $prev_month = 12;
                $prev_year--;
            }
            $next_month = $month + 1;
            $next_year = $year;
            if ($next_month > 12) {
                $next_month = 1;
                $next_year++;
            }

            $first_day_ts = @mktime(0, 0, 0, $month, 1, $year);
            $first_day_of_week = intval(date('w', $first_day_ts)); // 0 = Sunday
            $days_in_month = intval(date('t', $first_day_ts));

            $prev_month_ts = @mktime(0, 0, 0, $prev_month, 1, $prev_year);
            $days_in_prev_month = intval(date('t', $prev_month_ts));

            $grid_cells = [];
            for ($i = $first_day_of_week - 1; $i >= 0; $i--) {
                $day_num = $days_in_prev_month - $i;
                $grid_cells[] = [
                    'day' => $day_num,
                    'month' => $prev_month,
                    'year' => $prev_year,
                    'current_month' => false,
                    'date_string' => sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $day_num)
                ];
            }
            for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
                $grid_cells[] = [
                    'day' => $day_num,
                    'month' => $month,
                    'year' => $year,
                    'current_month' => true,
                    'date_string' => sprintf('%04d-%02d-%02d', $year, $month, $day_num)
                ];
            }
            $cells_left = 42 - count($grid_cells);
            for ($day_num = 1; $day_num <= $cells_left; $day_num++) {
                $grid_cells[] = [
                    'day' => $day_num,
                    'month' => $next_month,
                    'year' => $next_year,
                    'current_month' => false,
                    'date_string' => sprintf('%04d-%02d-%02d', $next_year, $next_month, $day_num)
                ];
            }

            $classes_by_date = [];
            foreach ($classes as $cl) {
                $cl_date_only = date('Y-m-d', strtotime($cl['class_date']));
                $classes_by_date[$cl_date_only][] = $cl;
            }
        ?>
            <!-- Calendar Navigation Header -->
            <div class="flex items-center justify-between bg-card border border-border/50 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <a href="?view=calendar&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="p-2 hover:bg-muted/40 rounded-lg border border-border text-foreground transition-colors">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </a>
                    <div class="relative inline-block text-left" id="datepicker-container">
                        <button onclick="toggleDatePickerPopover()" class="flex items-center gap-1.5 text-lg font-bold text-foreground hover:text-primary transition-colors focus:outline-none py-1 px-2 rounded-lg hover:bg-muted/20">
                            <span class="capitalize">
                                <?php 
                                $months_es = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
                                echo $months_es[$month] . ' ' . $year; 
                                ?>
                            </span>
                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                        </button>
                        
                        <!-- Popover Datepicker -->
                        <div id="datepicker-popover" onclick="event.stopPropagation()" class="hidden absolute left-0 mt-2 z-50 w-72 bg-card border border-border rounded-xl p-4 shadow-2xl space-y-4">
                            <!-- Popover Header -->
                            <div class="flex items-center justify-between gap-2 border-b border-border/30 pb-3">
                                <div class="flex items-center gap-1.5 flex-1">
                                    <select id="popover-month-select" onchange="onPopoverSelectChange()" class="bg-muted hover:bg-muted/70 border border-border text-foreground font-bold text-xs px-2 py-1 rounded-lg focus:outline-none cursor-pointer flex-1">
                                        <option value="1">Enero</option>
                                        <option value="2">Febrero</option>
                                        <option value="3">Marzo</option>
                                        <option value="4">Abril</option>
                                        <option value="5">Mayo</option>
                                        <option value="6">Junio</option>
                                        <option value="7">Julio</option>
                                        <option value="8">Agosto</option>
                                        <option value="9">Septiembre</option>
                                        <option value="10">Octubre</option>
                                        <option value="11">Noviembre</option>
                                        <option value="12">Diciembre</option>
                                    </select>
                                    <select id="popover-year-select" onchange="onPopoverSelectChange()" class="bg-muted hover:bg-muted/70 border border-border text-foreground font-bold text-xs px-2 py-1 rounded-lg focus:outline-none cursor-pointer w-20">
                                    </select>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button onclick="navigatePopoverMonth(-1)" title="Mes anterior" class="p-1 hover:bg-muted/40 rounded-lg border border-border/50 text-foreground transition-colors">
                                        <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button onclick="navigatePopoverMonth(1)" title="Mes siguiente" class="p-1 hover:bg-muted/40 rounded-lg border border-border/50 text-foreground transition-colors">
                                        <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Popover Weekdays -->
                            <div class="grid grid-cols-7 gap-1 text-center font-bold text-[10px] text-muted-foreground uppercase">
                                <div>D</div>
                                <div>L</div>
                                <div>M</div>
                                <div>X</div>
                                <div>J</div>
                                <div>V</div>
                                <div>S</div>
                            </div>
                            
                            <!-- Popover Days Grid -->
                            <div id="popover-days-grid" class="grid grid-cols-7 gap-1 text-center text-xs">
                            </div>
                        </div>
                    </div>
                    <a href="?view=calendar&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="p-2 hover:bg-muted/40 rounded-lg border border-border text-foreground transition-colors">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <a href="?view=list" class="bg-muted hover:bg-muted/70 text-foreground font-semibold px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5">
                        <i data-lucide="list" class="h-4 w-4"></i>
                        Ver Lista
                    </a>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="space-y-2">
                <div class="grid grid-cols-7 gap-1 text-center font-bold text-xs text-muted-foreground uppercase tracking-wider mb-2">
                    <div>Dom</div>
                    <div>Lun</div>
                    <div>Mar</div>
                    <div>Mié</div>
                    <div>Jue</div>
                    <div>Vie</div>
                    <div>Sáb</div>
                </div>
                
                <div class="grid grid-cols-7 gap-1 bg-border/20 border border-border/30 rounded-xl overflow-hidden">
                    <?php foreach ($grid_cells as $cell): 
                        $cell_classes = $classes_by_date[$cell['date_string']] ?? [];
                        $is_today = $cell['date_string'] === date('Y-m-d');
                        $is_current = $cell['current_month'];
                        $has_classes = count($cell_classes) > 0;
                        
                        // Determinar color de fondo y bordes
                        if (!$is_current) {
                            $cell_bg = 'bg-[#0f1115]/50 opacity-40 hover:opacity-60';
                        } else {
                            if ($has_classes) {
                                $cell_bg = 'bg-primary/[0.04] border border-primary/20 hover:bg-primary/[0.07]';
                            } else {
                                $cell_bg = 'bg-card hover:bg-muted/10';
                            }
                        }
                    ?>
                        <div class="min-h-[110px] p-2 flex flex-col justify-between border-t border-r border-border/30 last-of-type:border-r-0 transition-all cursor-pointer <?php echo $cell_bg; ?>" data-day-cell="<?php echo $cell['date_string']; ?>" onclick="selectCalendarDay('<?php echo $cell['date_string']; ?>')">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold <?php echo $is_current ? 'text-foreground' : 'text-muted-foreground/35'; ?> <?php echo $is_today ? 'bg-primary text-primary-foreground h-5 w-5 rounded-full flex items-center justify-center' : ''; ?>">
                                    <?php echo $cell['day']; ?>
                                </span>
                                <?php if ($has_classes): ?>
                                    <span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1 space-y-1 overflow-y-auto max-h-[80px] custom-scrollbar">
                                <?php foreach ($cell_classes as $cl): 
                                    $cl_time = new DateTime($cl['class_date']);
                                    $cl_end = clone $cl_time;
                                    $cl_end->modify('+' . intval($cl['duration']) . ' minutes');
                                    $horario = $cl_time->format('H:i') . '-' . $cl_end->format('H:i');
                                    $color_class = $cl['status'] === 'completed' ? 'bg-success/10 border-success/30 text-success' : 'bg-primary/10 border-primary/20 text-primary';
                                ?>
                                    <div class="text-[10px] p-1 border rounded truncate font-medium transition-all hover:scale-[1.02] <?php echo $color_class; ?>" 
                                         onclick="event.stopPropagation(); openClassDetailModal(<?php echo htmlspecialchars(json_encode($cl)); ?>)">
                                        <strong><?php echo $horario; ?></strong> <?php echo htmlspecialchars($cl['title']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Selected Day Panel -->
            <div id="selected-day-panel" class="hidden bg-card border border-border/50 rounded-xl p-6 mt-6 space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="calendar-days" class="h-5 w-5 text-primary"></i>
                        Clases para el <span id="selected-day-title"></span>
                    </h3>
                    <button onclick="closeSelectedDayPanel()" class="text-xs text-muted-foreground hover:text-foreground">Cerrar</button>
                </div>
                
                <div id="selected-day-classes" class="grid gap-3">
                </div>
            </div>

        <?php else: ?>
            <!-- Layout Tabs (Lista) -->
            <div class="flex items-center justify-between border-b border-border flex-wrap gap-4">
                <div class="flex">
                    <button onclick="switchTab('upcoming')" id="tab-btn-upcoming" class="tab-btn border-b-2 border-primary text-primary px-4 py-2 text-sm font-semibold focus:outline-none">
                        Próximas Clases (<?php echo count($upcoming_classes); ?>)
                    </button>
                    <button onclick="switchTab('past')" id="tab-btn-past" class="tab-btn text-muted-foreground border-b-2 border-transparent hover:text-foreground px-4 py-2 text-sm font-semibold focus:outline-none">
                        Historial / Completadas (<?php echo count($past_classes); ?>)
                    </button>
                </div>
                <a href="?view=calendar" class="bg-muted hover:bg-muted/70 text-foreground font-semibold px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5 mb-2">
                    <i data-lucide="calendar" class="h-4 w-4"></i>
                    Ver Calendario
                </a>
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
        <?php endif; ?>

        <!-- Modal de Ampliación de Clase -->
        <div id="classDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="event.target.id === 'classDetailModal' && closeClassDetailModal()">
            <div class="bg-card border border-border rounded-xl w-full max-w-lg p-6 shadow-2xl space-y-4 relative transform scale-95 opacity-0 transition-all duration-300">
                <button onclick="closeClassDetailModal()" class="absolute top-4 right-4 text-muted-foreground hover:text-foreground">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
                
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span id="modal-detail-status" class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded-full"></span>
                        <span id="modal-detail-duration" class="text-xs text-muted-foreground"></span>
                    </div>
                    <h2 id="modal-detail-title" class="text-xl font-extrabold text-foreground"></h2>
                    <p id="modal-detail-time" class="text-xs text-primary font-semibold"></p>
                </div>
                
                <div class="space-y-2 border-t border-border/30 pt-4">
                    <h4 class="text-xs font-bold text-foreground uppercase tracking-wider">Descripción</h4>
                    <p id="modal-detail-desc" class="text-xs text-muted-foreground leading-relaxed"></p>
                </div>
                
                <div class="flex items-center justify-end gap-3 border-t border-border/30 pt-4 mt-6">
                    <button onclick="closeClassDetailModal()" class="bg-muted/40 hover:bg-muted/65 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                        Cerrar
                    </button>
                    <a id="modal-detail-link" class="bg-primary text-primary-foreground font-bold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors flex items-center gap-1.5 shadow-lg shadow-primary/10">
                        <span>Ver Detalles completos</span>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
            </div>
        </div>

        <script>
        const allProjectClasses = <?php echo json_encode($classes); ?>;

        function selectCalendarDay(dateString) {
            const dayClasses = allProjectClasses.filter(cl => {
                const clDate = (cl.class_date || cl.date).split(' ')[0];
                return clDate === dateString;
            });
            
            const panel = document.getElementById('selected-day-panel');
            const titleEl = document.getElementById('selected-day-title');
            const listEl = document.getElementById('selected-day-classes');
            
            const parts = dateString.split('-');
            const formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
            titleEl.textContent = formattedDate;
            
            listEl.innerHTML = '';
            
            // Highlight selected day cell in the calendar grid
            activeSelectedDay = dateString;
            document.querySelectorAll('[data-day-cell]').forEach(el => {
                el.classList.remove('ring-2', 'ring-primary', 'border-primary', 'z-10');
            });
            const selectedCell = document.querySelector(`[data-day-cell="${dateString}"]`);
            if (selectedCell) {
                selectedCell.classList.add('ring-2', 'ring-primary', 'border-primary', 'z-10');
            }
            
            if (dayClasses.length === 0) {
                listEl.innerHTML = `
                    <div class="text-center py-6 text-muted-foreground text-sm">
                        No hay clases programadas para este día.
                    </div>
                `;
            } else {
                dayClasses.forEach(cl => {
                    const timeStr = cl.class_date || cl.date;
                    
                    // Parse starting date safely
                    const dateParts = timeStr.split(' ')[0].split('-');
                    const timeParts = timeStr.split(' ')[1].split(':');
                    const dateObj = new Date(
                        parseInt(dateParts[0]),
                        parseInt(dateParts[1]) - 1,
                        parseInt(dateParts[2]),
                        parseInt(timeParts[0]),
                        parseInt(timeParts[1])
                    );
                    
                    const durationMin = parseInt(cl.duration || 0);
                    const endDateObj = new Date(dateObj.getTime() + durationMin * 60000);
                    
                    const formatTime = (d) => {
                        const h = String(d.getHours()).padStart(2, '0');
                        const m = String(d.getMinutes()).padStart(2, '0');
                        return `${h}:${m}`;
                    };
                    const horarioStr = `${formatTime(dateObj)} - ${formatTime(endDateObj)} hs`;
                    
                    const statusBadge = cl.status === 'completed' 
                        ? '<span class="text-3xs font-semibold px-2 py-0.5 bg-success/15 border border-success/30 text-success rounded-full">Completada</span>'
                        : '<span class="text-3xs font-semibold px-2 py-0.5 bg-primary/15 border border-primary/20 text-primary rounded-full">Próxima</span>';
                        
                    const card = document.createElement('div');
                    card.className = 'bg-muted/10 border border-border/30 rounded-xl p-4 flex items-center justify-between hover:border-primary/20 cursor-pointer transition-all';
                    card.onclick = () => openClassDetailModal(cl);
                    card.innerHTML = `
                        <div class="flex items-center gap-4">
                            <div class="text-sm font-bold text-primary">${horarioStr}</div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground flex items-center gap-2">
                                    ${cl.title}
                                    ${statusBadge}
                                </h4>
                                <p class="text-xs text-muted-foreground mt-0.5">${cl.description}</p>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground flex items-center gap-1">
                            <span>Ampliar</span>
                            <i data-lucide="maximize-2" class="h-3.5 w-3.5"></i>
                        </div>
                    `;
                    listEl.appendChild(card);
                });
            }
            
            panel.classList.remove('hidden');
            lucide.createIcons();
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeSelectedDayPanel() {
            document.getElementById('selected-day-panel').classList.add('hidden');
            // Remove selection highlight when closing the panel
            document.querySelectorAll('[data-day-cell]').forEach(el => {
                el.classList.remove('ring-2', 'ring-primary', 'border-primary', 'z-10');
            });
        }

        function openClassDetailModal(classObj) {
            document.getElementById('modal-detail-title').textContent = classObj.title;
            document.getElementById('modal-detail-desc').textContent = classObj.description;
            
            const dateObj = new Date(classObj.class_date || classObj.date);
            const dateStr = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' hs';
            document.getElementById('modal-detail-time').textContent = dateStr;
            document.getElementById('modal-detail-duration').textContent = classObj.duration + ' minutos';
            
            const statusEl = document.getElementById('modal-detail-status');
            statusEl.textContent = classObj.status === 'completed' ? 'Completada' : 'Próxima';
            if (classObj.status === 'completed') {
                statusEl.className = 'text-3xs font-semibold px-2 py-0.5 bg-success/15 text-success border border-success/30 rounded-full capitalize';
            } else {
                statusEl.className = 'text-3xs font-semibold px-2 py-0.5 bg-primary/15 text-primary border border-primary/20 rounded-full capitalize';
            }
            
            const linkEl = document.getElementById('modal-detail-link');
            const currentFile = window.location.pathname.split('/').pop();
            linkEl.href = currentFile + '?class_id=' + classObj.id;
            
            const modalEl = document.getElementById('classDetailModal');
            modalEl.classList.remove('hidden');
            modalEl.classList.add('flex');
            
            // Trigger transition with a tiny timeout
            setTimeout(() => {
                modalEl.classList.remove('opacity-0');
                modalEl.classList.add('opacity-100');
                
                const panel = modalEl.querySelector('.bg-card');
                if (panel) {
                    panel.classList.remove('scale-95', 'opacity-0');
                    panel.classList.add('scale-100', 'opacity-100');
                }
            }, 20);
        }

        function closeClassDetailModal() {
            const modalEl = document.getElementById('classDetailModal');
            modalEl.classList.remove('opacity-100');
            modalEl.classList.add('opacity-0');
            
            const panel = modalEl.querySelector('.bg-card');
            if (panel) {
                panel.classList.remove('scale-100', 'opacity-100');
                panel.classList.add('scale-95', 'opacity-0');
            }
            
            setTimeout(() => {
                modalEl.classList.remove('flex');
                modalEl.classList.add('hidden');
            }, 300);
        }

        // --- SISTEMA DE DATEPICKER POPOVER ---
        let popoverMonth = <?php echo $month; ?>;
        let popoverYear = <?php echo $year; ?>;
        const currentSelectedMonth = <?php echo $month; ?>;
        const currentSelectedYear = <?php echo $year; ?>;
        let activeSelectedDay = '<?php echo $_GET["select_date"] ?? ""; ?>';
        
        function toggleDatePickerPopover() {
            const popover = document.getElementById('datepicker-popover');
            if (popover.classList.contains('hidden')) {
                popoverMonth = currentSelectedMonth;
                popoverYear = currentSelectedYear;
                
                // Populate year select once
                const yearSelect = document.getElementById('popover-year-select');
                if (yearSelect && yearSelect.options.length === 0) {
                    const startYear = 2020;
                    const endYear = 2035;
                    for (let y = startYear; y <= endYear; y++) {
                        const opt = document.createElement('option');
                        opt.value = y;
                        opt.textContent = y;
                        yearSelect.appendChild(opt);
                    }
                }
                
                renderPopoverCalendar();
                popover.classList.remove('hidden');
                lucide.createIcons();
            } else {
                popover.classList.add('hidden');
            }
        }
        
        // Close popover when clicking outside
        document.addEventListener('click', (e) => {
            const container = document.getElementById('datepicker-container');
            const popover = document.getElementById('datepicker-popover');
            
            // Si el elemento clickeado ya no está en el documento (por ejemplo, reconstruido en JS), ignoramos
            if (!document.contains(e.target)) return;
            
            if (container && !container.contains(e.target) && popover && !popover.classList.contains('hidden')) {
                popover.classList.add('hidden');
            }
        });
        
        function onPopoverSelectChange() {
            popoverMonth = parseInt(document.getElementById('popover-month-select').value);
            popoverYear = parseInt(document.getElementById('popover-year-select').value);
            renderPopoverCalendar();
            lucide.createIcons();
        }
        
        function navigatePopoverMonth(direction) {
            popoverMonth += direction;
            if (popoverMonth < 1) {
                popoverMonth = 12;
                popoverYear--;
            } else if (popoverMonth > 12) {
                popoverMonth = 1;
                popoverYear++;
            }
            renderPopoverCalendar();
            lucide.createIcons();
        }
        
        function renderPopoverCalendar() {
            // Update selected values in select elements
            const monthSelect = document.getElementById('popover-month-select');
            const yearSelect = document.getElementById('popover-year-select');
            if (monthSelect) monthSelect.value = popoverMonth;
            if (yearSelect) yearSelect.value = popoverYear;
            
            const grid = document.getElementById('popover-days-grid');
            grid.innerHTML = '';
            
            const firstDayTs = new Date(popoverYear, popoverMonth - 1, 1);
            let firstDayOfWeek = firstDayTs.getDay(); // 0 = Sunday
            const daysInMonth = new Date(popoverYear, popoverMonth, 0).getDate();
            const daysInPrevMonth = new Date(popoverYear, popoverMonth - 1, 0).getDate();
            
            // Previous month trailing days
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const dayNum = daysInPrevMonth - i;
                const prevM = popoverMonth === 1 ? 12 : popoverMonth - 1;
                const prevY = popoverMonth === 1 ? popoverYear - 1 : popoverYear;
                const dateStr = `${prevY}-${String(prevM).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                
                const dayEl = document.createElement('button');
                dayEl.className = 'p-1 hover:bg-muted rounded text-muted-foreground/30 focus:outline-none transition-colors w-8 h-8 flex items-center justify-center mx-auto';
                dayEl.textContent = dayNum;
                dayEl.onclick = () => selectPopoverDate(dateStr, prevM, prevY);
                grid.appendChild(dayEl);
            }
            
            // Current month days
            for (let dayNum = 1; dayNum <= daysInMonth; dayNum++) {
                const dateStr = `${popoverYear}-${String(popoverMonth).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                
                const dayEl = document.createElement('button');
                dayEl.textContent = dayNum;
                
                let btnClass = 'p-1 hover:bg-muted rounded text-foreground font-semibold focus:outline-none transition-colors w-8 h-8 flex items-center justify-center mx-auto';
                
                // Highlight today or active select
                const cellClasses = allProjectClasses.filter(cl => (cl.class_date || cl.date).split(' ')[0] === dateStr);
                const hasClasses = cellClasses.length > 0;
                
                if (hasClasses) {
                    btnClass += ' text-primary bg-primary/10 border border-primary/20';
                }
                
                if (dateStr === activeSelectedDay) {
                    btnClass = 'p-1 bg-primary text-primary-foreground rounded-full font-bold scale-105 shadow-lg shadow-primary/20 focus:outline-none w-8 h-8 flex items-center justify-center mx-auto';
                }
                
                dayEl.className = btnClass;
                dayEl.onclick = () => selectPopoverDate(dateStr, popoverMonth, popoverYear);
                grid.appendChild(dayEl);
            }
            
            // Next month days to fill 42 cells grid
            const totalCellsUsed = firstDayOfWeek + daysInMonth;
            const cellsLeft = 42 - totalCellsUsed;
            for (let dayNum = 1; dayNum <= cellsLeft; dayNum++) {
                const nextM = popoverMonth === 12 ? 1 : popoverMonth + 1;
                const nextY = popoverMonth === 12 ? popoverYear + 1 : popoverYear;
                const dateStr = `${nextY}-${String(nextM).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                
                const dayEl = document.createElement('button');
                dayEl.className = 'p-1 hover:bg-muted rounded text-muted-foreground/30 focus:outline-none transition-colors w-8 h-8 flex items-center justify-center mx-auto';
                dayEl.textContent = dayNum;
                dayEl.onclick = () => selectPopoverDate(dateStr, nextM, nextY);
                grid.appendChild(dayEl);
            }
        }
        
        function selectPopoverDate(dateStr, m, y) {
            document.getElementById('datepicker-popover').classList.add('hidden');
            if (m !== currentSelectedMonth || y !== currentSelectedYear) {
                window.location.href = `?view=calendar&month=${m}&year=${y}&select_date=${dateStr}`;
            } else {
                selectCalendarDay(dateStr);
            }
        }

        // Trigger selection on load if parameter is present
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const selectDate = urlParams.get('select_date');
            if (selectDate) {
                selectCalendarDay(selectDate);
            }
        });
        </script>
    </div>
</div>

<?php
render_footer();
?>
