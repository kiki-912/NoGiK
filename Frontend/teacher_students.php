<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

$id = $_GET['id'] ?? '';

$categoryColors = [
    'technical' => 'bg-primary/10 text-primary border-primary/20',
    'creative' => 'bg-secondary/10 text-secondary border-secondary/20',
    'performance' => 'bg-success/10 text-success border-success/20'
];

$categoryLabels = [
    'technical' => 'Técnica',
    'creative' => 'Creatividad',
    'performance' => 'Performance'
];

// DETAIL STUDENT VIEW
if (!empty($id)) {
    // Fetch student
    $stmt_stud = $pdo->prepare("SELECT u.name, u.email, u.avatar, st.* FROM students st JOIN users u ON st.user_id = u.id WHERE st.user_id = ?");
    $stmt_stud->execute([$id]);
    $student = $stmt_stud->fetch();
    
    if (!$student) {
        header("Location: teacher_students.php");
        exit();
    }
    
    $tier = get_reputation_tier($student['reputation']);
    
    // Fetch skills
    $stmt_sk = $pdo->prepare("SELECT ss.*, s.name, s.category, s.icon FROM student_skills ss JOIN skills s ON ss.skill_id = s.id WHERE ss.student_id = ?");
    $stmt_sk->execute([$id]);
    $skills = $stmt_sk->fetchAll();
    
    // Map skill levels for Radar Chart
    $skill_levels = [
        'beatmatching' => 1,
        'loops-effects' => 1,
        'transitions' => 1,
        'creativity' => 1,
        'energy-management' => 1
    ];
    foreach ($skills as $sk) {
        if (isset($skill_levels[$sk['skill_id']])) {
            $skill_levels[$sk['skill_id']] = intval($sk['level']);
        }
    }
    
    $centerX = 175;
    $centerY = 175;
    $maxRadius = 110;
    $get_radar_point = function($index, $level, $max_level = 5) use ($centerX, $centerY, $maxRadius) {
        $angle = -M_PI / 2 + ($index * 2 * M_PI / 5);
        $radius = $maxRadius * ($level / $max_level);
        $x = $centerX + $radius * cos($angle);
        $y = $centerY + $radius * sin($angle);
        return ['x' => $x, 'y' => $y];
    };
    
    // Fetch sets uploaded
    $stmt_sets = $pdo->prepare("SELECT s.*, se.overall_score FROM dj_sets s LEFT JOIN set_evaluations se ON s.id = se.set_id WHERE s.student_id = ? ORDER BY s.uploaded_at DESC");
    $stmt_sets->execute([$id]);
    $sets = $stmt_sets->fetchAll();
    
    // Fetch classes attended
    $stmt_cl = $pdo->prepare("SELECT c.*, u.name as teacher_name FROM class_attendees ca JOIN classes c ON ca.class_id = c.id LEFT JOIN users u ON c.teacher_id = u.id WHERE ca.student_id = ? ORDER BY c.class_date DESC");
    $stmt_cl->execute([$id]);
    $attended_classes = $stmt_cl->fetchAll();
    
    render_header("Detalle de Alumno - NogiK");
    render_sidebar();
    ?>
    <div class="flex-1 flex flex-col min-w-0 bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6 py-4">
            <a href="teacher_students.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-foreground"><?php echo htmlspecialchars($student['name']); ?></h1>
                <p class="text-sm text-muted-foreground">Progreso detallado del estudiante</p>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
            
            <!-- Student Header Profile Card -->
            <div class="bg-card border border-border/50 rounded-xl p-6">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <img src="<?php echo htmlspecialchars($student['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-20 h-20 rounded-full border bg-muted">
                    
                    <div class="flex-1 text-center md:text-left space-y-1">
                        <div class="flex flex-col md:flex-row md:items-center gap-2">
                            <h3 class="text-xl font-bold text-foreground"><?php echo htmlspecialchars($student['name']); ?></h3>
                            <span class="inline-block text-3xs font-semibold px-2 py-0.5 rounded border" style="color: <?php echo $tier['color']; ?>; border-color: <?php echo $tier['color']; ?>; background-color: <?php echo $tier['color']; ?>10;">
                                <?php echo $tier['name']; ?>
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground"><?php echo htmlspecialchars($student['email']); ?></p>
                        
                        <div class="flex items-center justify-center md:justify-start gap-4 text-xs text-muted-foreground pt-2">
                            <span>Sets: <strong><?php echo $student['total_sets']; ?></strong></span>
                            <span>•</span>
                            <span>Reputación: <strong><?php echo $student['reputation']; ?></strong></span>
                            <span>•</span>
                            <span>Nivel actual: <strong class="text-primary"><?php echo $student['level']; ?></strong></span>
                        </div>
                    </div>
                    
                    <!-- Level stats -->
                    <div class="w-full md:w-60 bg-muted/20 p-4 rounded-xl border border-border/30 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Nivel <?php echo $student['level']; ?></span>
                            <span class="text-muted-foreground"><?php echo $student['xp']; ?>/<?php echo $student['xp_to_next_level']; ?> XP</span>
                        </div>
                        <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-primary" style="width: <?php echo ($student['xp'] / $student['xp_to_next_level']) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Layout: Skills + History -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Skills (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h4 class="font-bold text-base text-foreground">Habilidades del Alumno</h4>
                        
                        <div class="grid sm:grid-cols-2 gap-4">
                            <?php foreach ($skills as $sk): 
                                $pct = ($sk['xp'] / $sk['xp_to_next_level']) * 100;
                                $color = $categoryColors[$sk['category']] ?? '';
                                $label = $categoryLabels[$sk['category']] ?? '';
                            ?>
                                <div class="bg-muted/10 border border-border/30 rounded-lg p-4 space-y-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h5 class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($sk['name']); ?></h5>
                                            <span class="text-3xs font-semibold px-2 py-0.5 rounded border mt-1 inline-block <?php echo $color; ?>">
                                                <?php echo $label; ?>
                                            </span>
                                        </div>
                                        <span class="text-sm font-bold text-primary">Nv. <?php echo $sk['level']; ?>/5</span>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div class="h-full bg-primary" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                        <span class="text-3xs text-muted-foreground"><?php echo $sk['xp']; ?>/<?php echo $sk['xp_to_next_level']; ?> XP</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sets List -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h4 class="font-bold text-base text-foreground">Historial de Sets subidos</h4>
                        
                        <?php if (empty($sets)): ?>
                            <p class="text-sm text-muted-foreground">Este alumno no ha subido ningún set aún.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($sets as $set): ?>
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-muted/20 border border-border/30">
                                        <div>
                                            <h5 class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($set['title']); ?></h5>
                                            <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                                                <span class="px-1.5 py-0.5 bg-muted rounded text-2xs"><?php echo htmlspecialchars($set['genre']); ?></span>
                                                <span><?php echo $set['duration']; ?> min</span>
                                                <span><?php echo date('d/m/Y', strtotime($set['uploaded_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <?php if ($set['overall_score']): ?>
                                                <span class="flex items-center gap-0.5 text-xs text-[#39FF14] font-bold bg-[#39FF14]/10 px-2 py-0.5 rounded">
                                                    <i data-lucide="star" class="h-3.5 w-3.5 fill-current"></i>
                                                    <?php echo round($set['overall_score'], 1); ?>
                                                </span>
                                            <?php else: ?>
                                                <a href="teacher_evaluations.php" class="text-xs bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded hover:opacity-90">Evaluar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Classes Attended & Radar (1 Col) -->
                <div class="space-y-6">
                    <!-- Radar de Habilidades Card -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h4 class="font-bold text-base text-foreground">Radar de Habilidades</h4>
                        <div class="relative w-full flex justify-center items-center py-4 bg-muted/5 rounded-xl border border-border/20">
                            <svg id="skills-radar-svg" viewBox="0 0 350 350" class="w-full max-w-[280px]" onmousemove="handleRadarMouseMove(event)" onmouseleave="handleRadarMouseLeave()">
                                <!-- Pentagon Grid lines - Much more visible -->
                                <?php
                                for ($lvl = 1; $lvl <= 5; $lvl++) {
                                    $points = [];
                                    for ($i = 0; $i < 5; $i++) {
                                        $pt = $get_radar_point($i, $lvl);
                                        $points[] = "{$pt['x']},{$pt['y']}";
                                    }
                                    $points_str = implode(' ', $points);
                                    echo "<polygon points='{$points_str}' stroke='#444B5A' stroke-width='0.9' fill='none' />\n";
                                    
                                    // Number labels - much brighter
                                    $pt_label = $get_radar_point(0, $lvl);
                                    echo "<text x='{$pt_label['x']}' y='" . ($pt_label['y'] + 10) . "' fill='#718096' font-size='9' font-weight='bold' text-anchor='middle'>{$lvl}</text>\n";
                                }
                                // Center 0 label
                                echo "<text x='{$centerX}' y='" . ($centerY + 4) . "' fill='#718096' font-size='9' font-weight='bold' text-anchor='middle'>0</text>\n";
                                
                                // Axis lines (direction arrows) - much more visible
                                for ($i = 0; $i < 5; $i++) {
                                    $pt = $get_radar_point($i, 5);
                                    echo "<line x1='{$centerX}' y1='{$centerY}' x2='{$pt['x']}' y2='{$pt['y']}' stroke='#444B5A' stroke-width='0.9' />\n";
                                }
                                
                                // Axis Labels - brighter and bold
                                $labels = ['Beatmatching', 'Loops', 'Transiciones', 'Creatividad', 'Manejo'];
                                for ($i = 0; $i < 5; $i++) {
                                    $pt = $get_radar_point($i, 5);
                                    $angle = -M_PI / 2 + ($i * 2 * M_PI / 5);
                                    $offsetX = cos($angle) * 18;
                                    $offsetY = sin($angle) * 12;
                                    
                                    $anchor = 'middle';
                                    if (cos($angle) > 0.1) $anchor = 'start';
                                    elseif (cos($angle) < -0.1) $anchor = 'end';
                                    
                                    $textX = $pt['x'] + $offsetX;
                                    $textY = $pt['y'] + $offsetY;
                                    
                                    echo "<text x='{$textX}' y='{$textY}' fill='#E2E8F0' font-size='10' font-weight='bold' text-anchor='{$anchor}'>{$labels[$i]}</text>\n";
                                }
                                
                                // Student actual filled polygon - Glowing cyan border & styling
                                $student_points = [];
                                $raw_levels = [
                                    $skill_levels['beatmatching'],
                                    $skill_levels['loops-effects'],
                                    $skill_levels['transitions'],
                                    $skill_levels['creativity'],
                                    $skill_levels['energy-management']
                                ];
                                for ($i = 0; $i < 5; $i++) {
                                    $pt = $get_radar_point($i, $raw_levels[$i]);
                                    $student_points[] = "{$pt['x']},{$pt['y']}";
                                }
                                $student_points_str = implode(' ', $student_points);
                                echo "<polygon points='{$student_points_str}' fill='rgba(0, 242, 255, 0.12)' stroke='#00F2FF' stroke-width='2' />\n";
                                
                                // Generate JS points array for closest point calculation
                                $js_points = [];
                                for ($i = 0; $i < 5; $i++) {
                                    $pt = $get_radar_point($i, $raw_levels[$i]);
                                    $js_points[] = "{ name: '{$labels[$i]}', level: {$raw_levels[$i]}, x: {$pt['x']}, y: {$pt['y']} }";
                                }
                                $js_points_str = "[" . implode(", ", $js_points) . "]";
                                ?>
                                
                                <!-- Hover interaction elements -->
                                <g id="radar-hover-indicator" style="display: none;">
                                    <line id="radar-hover-line" x1="<?php echo $centerX; ?>" y1="<?php echo $centerY; ?>" x2="<?php echo $centerX; ?>" y2="<?php echo $centerY; ?>" stroke="#00F2FF" stroke-width="1.2" stroke-dasharray="2,2" />
                                    <circle id="radar-hover-dot" cx="<?php echo $centerX; ?>" cy="<?php echo $centerY; ?>" r="5" fill="#0F1115" stroke="#00F2FF" stroke-width="2" />
                                    <foreignObject id="radar-tooltip-fo" x="<?php echo $centerX; ?>" y="<?php echo $centerY; ?>" width="130" height="60" pointer-events="none">
                                        <div class="bg-[#1A1D23] border border-[#2D3139] rounded-lg p-2 shadow-lg text-left select-none leading-tight space-y-0.5">
                                            <p id="radar-tooltip-title" class="text-3xs font-bold text-foreground">Transiciones</p>
                                            <p id="radar-tooltip-level" class="text-[10px] text-primary">Nivel: 4/5</p>
                                        </div>
                                    </foreignObject>
                                </g>
                            </svg>
                        </div>
                    </div>
                    
                    <script>
                    const radarPoints = <?php echo $js_points_str; ?>;

                    function handleRadarMouseMove(event) {
                        const svg = document.getElementById('skills-radar-svg');
                        if (!svg) return;
                        
                        const rect = svg.getBoundingClientRect();
                        const svgX = (event.clientX - rect.left) * (350 / rect.width);
                        const svgY = (event.clientY - rect.top) * (350 / rect.height);
                        
                        let minDistance = Infinity;
                        let closestPoint = null;
                        
                        for (const pt of radarPoints) {
                            const dist = Math.hypot(svgX - pt.x, svgY - pt.y);
                            if (dist < minDistance) {
                                minDistance = dist;
                                closestPoint = pt;
                            }
                        }
                        
                        // Show tooltip if within active radar bounds
                        const distFromCenter = Math.hypot(svgX - 175, svgY - 175);
                        if (closestPoint && distFromCenter < 145) {
                            showRadarTooltip(closestPoint.name, closestPoint.level, closestPoint.x, closestPoint.y);
                        } else {
                            hideRadarTooltip();
                        }
                    }

                    function handleRadarMouseLeave() {
                        hideRadarTooltip();
                    }

                    function showRadarTooltip(name, level, x, y) {
                        const ind = document.getElementById('radar-hover-indicator');
                        const line = document.getElementById('radar-hover-line');
                        const dot = document.getElementById('radar-hover-dot');
                        const fo = document.getElementById('radar-tooltip-fo');
                        const title = document.getElementById('radar-tooltip-title');
                        const lvl = document.getElementById('radar-tooltip-level');
                        
                        if (!ind || !line || !dot || !fo || !title || !lvl) return;
                        
                        title.innerText = name;
                        lvl.innerText = `Nivel: ${level}/5`;
                        
                        line.setAttribute('x2', x);
                        line.setAttribute('y2', y);
                        
                        dot.setAttribute('cx', x);
                        dot.setAttribute('cy', y);
                        
                        let tooltipX = x + 12;
                        let tooltipY = y + 12;
                        if (x > 175) {
                            tooltipX = x - 142;
                        }
                        if (y > 175) {
                            tooltipY = y - 72;
                        }
                        
                        fo.setAttribute('x', tooltipX);
                        fo.setAttribute('y', tooltipY);
                        
                        ind.style.display = 'block';
                    }

                    function hideRadarTooltip() {
                        const ind = document.getElementById('radar-hover-indicator');
                        if (ind) ind.style.display = 'none';
                    }
                    </script>

                    <!-- Classes Attended Card -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h4 class="font-bold text-base text-foreground">Clases Asistidas</h4>
                        
                        <?php if (empty($attended_classes)): ?>
                            <p class="text-sm text-muted-foreground">No ha completado clases en el sistema aún.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($attended_classes as $class): 
                                    $cl_date = new DateTime($class['class_date']);
                                ?>
                                    <div class="p-3 rounded-lg bg-muted/20 border border-border/30 text-xs">
                                        <h5 class="font-semibold text-sm text-foreground truncate"><?php echo htmlspecialchars($class['title']); ?></h5>
                                        <p class="text-muted-foreground mt-1">Impartida por <?php echo htmlspecialchars($class['teacher_name']); ?></p>
                                        <p class="text-muted-foreground mt-0.5"><?php echo $cl_date->format('d/m/Y H:i'); ?> • <?php echo $class['duration']; ?> min</p>
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

// DEFAULT LIST OF ALL STUDENTS VIEW
$stmt = $pdo->prepare("
    SELECT st.*, u.name, u.email, u.avatar, (SELECT AVG(level) FROM student_skills WHERE student_id = st.user_id) as avg_skill 
    FROM students st 
    JOIN users u ON st.user_id = u.id 
    ORDER BY u.name ASC
");
$stmt->execute();
$students = $stmt->fetchAll();

render_header("Alumnos - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Alumnos</h1>
            <p class="text-sm text-muted-foreground">Listado general de alumnos registrados en la academia</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <!-- Students list grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($students as $stud): 
                $tier = get_reputation_tier($stud['reputation']);
                $avg_val = floatval($stud['avg_skill']);
            ?>
                <div class="bg-card border border-border/50 rounded-xl p-5 hover:border-primary/30 transition-all flex flex-col justify-between gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($stud['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-12 h-12 rounded-full border bg-muted flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($stud['name']); ?></h3>
                                <p class="text-xs text-muted-foreground truncate"><?php echo htmlspecialchars($stud['email']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 border border-primary/20 text-primary rounded-full">
                                Nivel <?php echo $stud['level']; ?>
                            </span>
                            <span class="text-3xs font-semibold px-2 py-0.5 rounded border" style="color: <?php echo $tier['color']; ?>; border-color: <?php echo $tier['color']; ?>; background-color: <?php echo $tier['color']; ?>10;">
                                <?php echo $tier['name']; ?>
                            </span>
                        </div>
                        
                        <!-- Skill meter -->
                        <div class="space-y-1 pt-1">
                            <div class="flex justify-between text-3xs text-muted-foreground">
                                <span>Habilidades promedio</span>
                                <span class="font-bold text-foreground"><?php echo number_format($avg_val, 1); ?>/5</span>
                            </div>
                            <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: <?php echo ($avg_val / 5) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-3 border-t border-border/30 flex items-center justify-between">
                        <div class="text-3xs text-muted-foreground">
                            <span>Reputación: <strong><?php echo $stud['reputation']; ?></strong></span>
                            <span class="ml-2">Sets: <strong><?php echo $stud['total_sets']; ?></strong></span>
                        </div>
                        <a href="teacher_students.php?id=<?php echo $stud['user_id']; ?>" class="bg-muted hover:bg-muted/80 text-foreground text-xs font-semibold px-3 py-1.5 rounded transition-all">
                            Ver Perfil
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php
render_footer();
?>
