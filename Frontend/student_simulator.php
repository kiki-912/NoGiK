<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

$id = $_GET['id'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$venueLabels = [
    'bar' => 'Bar Local',
    'club' => 'Club Underground',
    'festival' => 'Festival',
    'private' => 'Fiesta Privada',
    'radio' => 'Radio Show'
];

$venueIcons = [
    'bar' => 'glass-water',
    'club' => 'building-2',
    'festival' => 'tent',
    'private' => 'users',
    'radio' => 'mic'
];

$difficultyLabels = [
    'beginner' => 'Principiante',
    'intermediate' => 'Intermedio',
    'advanced' => 'Avanzado',
    'pro' => 'Profesional'
];

$difficultyColors = [
    'beginner' => 'bg-[#39FF14]/10 text-[#39FF14] border-[#39FF14]/20',
    'intermediate' => 'bg-primary/10 text-primary border-primary/20',
    'advanced' => 'bg-secondary/10 text-secondary border-secondary/20',
    'pro' => 'bg-[#FF6B35]/10 text-[#FF6B35] border-[#FF6B35]/20'
];

// DETAIL & PARTICIPATE VIEW
if (!empty($id)) {
    // Fetch event
    $stmt_ev = $pdo->prepare("SELECT * FROM simulated_events WHERE id = ?");
    $stmt_ev->execute([$id]);
    $event = $stmt_ev->fetch();
    
    if (!$event) {
        header("Location: student_simulator.php");
        exit();
    }
    
    // Check if locked
    $is_locked = ($event['required_reputation'] > $student['reputation']);
    
    // Fetch existing participation
    $stmt_part = $pdo->prepare("SELECT * FROM event_participations WHERE event_id = ? AND student_id = ?");
    $stmt_part->execute([$id, $student['id']]);
    $part = $stmt_part->fetch();
    
    $has_participated = (bool)$part;
    $is_evaluated = $has_participated && ($part['status'] === 'evaluated');
    
    // Fetch setlist tracks if participated
    $tracks = [];
    if ($has_participated) {
        $stmt_tr = $pdo->prepare("SELECT * FROM setlist_tracks WHERE participation_id = ? ORDER BY position ASC");
        $stmt_tr->execute([$part['id']]);
        $tracks = $stmt_tr->fetchAll();
    }
    
    // Fetch event evaluation if evaluated
    $eval = null;
    if ($is_evaluated) {
        $stmt_eval = $pdo->prepare("SELECT ee.*, u.name as teacher_name FROM event_evaluations ee LEFT JOIN users u ON ee.teacher_id = u.id WHERE ee.participation_id = ?");
        $stmt_eval->execute([$part['id']]);
        $eval = $stmt_eval->fetch();
    }
    
    render_header("Detalle de Evento - NogiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground "><?php echo htmlspecialchars($event['name']); ?></h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Detalle del evento y setlist</p>
            </div>
        </div>
            <a href="student_simulator.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            
        </header>

        <!-- Content -->
        <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
            
            <?php if ($error === 'invalid_participation'): ?>
                <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                    Error en la participación. Recuerda agregar al menos 3 tracks válidos y una justificación de al menos 20 caracteres.
                </div>
            <?php endif; ?>

            <!-- Event Card Info -->
            <div class="bg-card border border-border/50 rounded-xl p-6">
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <div class="flex gap-4">
                        <div class="w-16 h-16 rounded-xl flex items-center justify-center bg-primary/10 text-primary flex-shrink-0">
                            <i data-lucide="<?php echo $venueIcons[$event['venue_type']] ?? 'radio'; ?>" class="h-8 w-8"></i>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-lg font-bold text-foreground leading-tight"><?php echo htmlspecialchars($event['name']); ?></h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-3xs font-semibold px-2 py-0.5 rounded border <?php echo $difficultyColors[$event['difficulty']] ?? ''; ?>">
                                    <?php echo $difficultyLabels[$event['difficulty']] ?? ''; ?>
                                </span>
                                <span class="text-3xs font-semibold px-2 py-0.5 bg-muted rounded border border-border text-muted-foreground">
                                    <?php echo $venueLabels[$event['venue_type']] ?? ''; ?>
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground pt-1"><?php echo htmlspecialchars($event['description']); ?></p>
                        </div>
                    </div>
                    
                    <div class="text-left md:text-right flex-shrink-0">
                        <p class="text-2xl font-extrabold text-success">$<?php echo $event['payment']; ?></p>
                        <p class="text-3xs text-muted-foreground font-semibold uppercase tracking-wider">Pago Estimado</p>
                    </div>
                </div>
                
                <!-- Specs -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-border/30 text-xs text-muted-foreground">
                    <div>
                        <p class="text-3xs font-bold uppercase tracking-wider text-muted-foreground">Audiencia</p>
                        <p class="text-sm font-semibold text-foreground mt-0.5"><?php echo $event['audience']; ?> personas</p>
                    </div>
                    <div>
                        <p class="text-3xs font-bold uppercase tracking-wider text-muted-foreground">Duración</p>
                        <p class="text-sm font-semibold text-foreground mt-0.5"><?php echo $event['duration']; ?> min</p>
                    </div>
                    <div>
                        <p class="text-3xs font-bold uppercase tracking-wider text-muted-foreground">Reputación Requerida</p>
                        <p class="text-sm font-semibold text-foreground mt-0.5">Rep: <?php echo $event['required_reputation']; ?>+</p>
                    </div>
                    <div>
                        <p class="text-3xs font-bold uppercase tracking-wider text-muted-foreground">Estilos Musicales</p>
                        <p class="text-sm font-semibold text-foreground mt-0.5"><?php echo htmlspecialchars($event['music_styles']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Participation flow -->
            <?php if ($is_locked): ?>
                <div class="bg-destructive/10 border border-destructive/20 text-destructive rounded-xl p-6 text-center space-y-2">
                    <i data-lucide="lock" class="h-10 w-10 mx-auto"></i>
                    <h4 class="font-bold text-foreground text-base">Evento Bloqueado</h4>
                    <p class="text-sm text-muted-foreground">No cumples con la reputación requerida para este evento. Consigue evaluaciones de sets para aumentar tu reputación.</p>
                </div>
            <?php elseif ($has_participated): ?>
                <!-- Already Submitted setlist -->
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Left: Tracks & Justification -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Setlist Link -->
                        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                            <h4 class="font-bold text-base text-foreground flex items-center gap-2">
                                <i data-lucide="link" class="h-5 w-5 text-primary"></i>
                                Enlace de tu Set
                            </h4>
                            
                            <div class="p-4 rounded-lg bg-muted/20 border border-border/30">
                                <a href="<?php echo htmlspecialchars($part['set_url']); ?>" target="_blank" class="flex items-center gap-3 text-primary hover:text-primary/80 transition-colors font-medium break-all">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="external-link" class="h-5 w-5"></i>
                                    </div>
                                    <?php echo htmlspecialchars($part['set_url']); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                            <h4 class="font-bold text-base text-foreground">Justificación de la Selección</h4>
                            <p class="text-sm text-muted-foreground leading-relaxed bg-muted/10 p-4 rounded-lg border border-border/30">
                                <?php echo nl2br(htmlspecialchars($part['justification'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Right: Evaluation Status -->
                    <div class="space-y-6">
                        <?php if ($is_evaluated && $eval): ?>
                            <!-- Evaluation -->
                            <div class="bg-card border border-[#39FF14]/30 rounded-xl p-6 text-center space-y-4">
                                <div class="inline-flex p-3 bg-[#39FF14]/10 text-[#39FF14] rounded-full">
                                    <i data-lucide="star" class="h-8 w-8 fill-current"></i>
                                </div>
                                <div>
                                    <p class="text-2xs text-muted-foreground uppercase font-bold tracking-wider">Nota de Evento</p>
                                    <h3 class="text-4xl font-extrabold text-[#39FF14] mt-1"><?php echo round($eval['total_score'], 2); ?></h3>
                                    <p class="text-xs text-muted-foreground mt-1">Evaluado por <?php echo htmlspecialchars($eval['teacher_name']); ?></p>
                                </div>
                                
                                <div class="grid grid-cols-1 gap-2 text-left text-xs">
                                    <div class="flex justify-between bg-muted/40 p-2.5 rounded border border-border/30">
                                        <span class="text-muted-foreground">Selección de Tracks:</span>
                                        <strong class="text-foreground"><?php echo $eval['track_selection']; ?>/10</strong>
                                    </div>
                                    <div class="flex justify-between bg-muted/40 p-2.5 rounded border border-border/30">
                                        <span class="text-muted-foreground">Flujo de Energía:</span>
                                        <strong class="text-foreground"><?php echo $eval['energy_flow']; ?>/10</strong>
                                    </div>
                                    <div class="flex justify-between bg-muted/40 p-2.5 rounded border border-border/30">
                                        <span class="text-muted-foreground">Coincidencia de Estilo:</span>
                                        <strong class="text-foreground"><?php echo $eval['style_match']; ?>/10</strong>
                                    </div>
                                    <div class="flex justify-between bg-muted/40 p-2.5 rounded border border-border/30">
                                        <span class="text-muted-foreground">Transiciones:</span>
                                        <strong class="text-foreground"><?php echo $eval['transitions']; ?>/10</strong>
                                    </div>
                                    <div class="flex justify-between bg-muted/40 p-2.5 rounded border border-border/30">
                                        <span class="text-muted-foreground">Adaptación de Público:</span>
                                        <strong class="text-foreground"><?php echo $eval['crowd_adaptation']; ?>/10</strong>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <span class="flex-1 text-center py-1 bg-primary/10 border border-primary/20 text-primary text-xs font-semibold rounded-lg">
                                        +<?php echo $eval['xp_awarded']; ?> XP
                                    </span>
                                    <span class="flex-1 text-center py-1 bg-[#39FF14]/10 border border-[#39FF14]/20 text-[#39FF14] text-xs font-semibold rounded-lg">
                                        +<?php echo $eval['reputation_change']; ?> Rep
                                    </span>
                                </div>
                            </div>

                            <!-- Teacher feedback text -->
                            <div class="bg-card border border-border/50 rounded-xl p-6 space-y-2">
                                <h4 class="font-bold text-sm text-foreground flex items-center gap-1.5">
                                    <i data-lucide="clipboard" class="h-4.5 w-4.5 text-primary"></i>
                                    Feedback del Profesor
                                </h4>
                                <p class="text-sm text-muted-foreground whitespace-pre-wrap leading-relaxed">
                                    <?php echo htmlspecialchars($eval['feedback']); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="bg-card border border-border/50 rounded-xl p-6 text-center py-12 space-y-4">
                                <div class="w-12 h-12 rounded-full bg-secondary/15 flex items-center justify-center text-secondary mx-auto">
                                    <i data-lucide="clock" class="h-6 w-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-base text-foreground">Pendiente de Evaluación</h4>
                                    <p class="text-xs text-muted-foreground mt-1 max-w-[200px] mx-auto">
                                        Tu setlist está siendo analizado por los profesores. Recibirás tu puntuación y reputación pronto.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Participate Form -->
                <form action="../Backend/scripts/actions.php" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="submit_participation">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-border pb-3">
                            <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                                <i data-lucide="music" class="h-5 w-5 text-primary"></i>
                                Enlace de tu Set
                            </h3>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Link de SoundCloud, Mixcloud, YouTube, etc.</label>
                            <input required type="url" name="set_url" placeholder="Ej: https://soundcloud.com/tu-usuario/tu-set" class="w-full bg-input border border-border rounded-lg px-4 py-3 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none transition-shadow">
                            <p class="text-3xs text-muted-foreground mt-1">Asegúrate de que el enlace sea público para que el profesor pueda escucharlo.</p>
                        </div>
                    </div>

                    <!-- Justification -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                        <h4 class="font-bold text-base text-foreground">Justificación de la Selección</h4>
                        <textarea required name="justification" rows="5" placeholder="Explica por qué elegiste estos tracks, cómo planeas manejar la energía del set y adaptarla a este público en particular..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                        <p class="text-3xs text-muted-foreground">Mínimo 20 caracteres.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="student_simulator.php" class="bg-card hover:bg-muted/40 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                            Presentar Participación
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
    <?php
    render_footer();
    exit();
}

// DEFAULT SIMULATOR MAIN LIST VIEW
// Fetch all events
$stmt_events = $pdo->prepare("SELECT * FROM simulated_events ORDER BY required_reputation ASC");
$stmt_events->execute();
$events = $stmt_events->fetchAll();

// Fetch participations by current student
$stmt_p = $pdo->prepare("SELECT ep.*, ee.total_score, ee.xp_awarded, ee.reputation_change FROM event_participations ep LEFT JOIN event_evaluations ee ON ep.id = ee.participation_id WHERE ep.student_id = ?");
$stmt_p->execute([$student['id']]);
$participations = $stmt_p->fetchAll();

$participated_event_ids = array_column($participations, 'event_id');
$completed_events_count = 0;

$available_events = [];
$locked_events = [];

foreach ($events as $event) {
    if ($event['required_reputation'] <= $student['reputation']) {
        $available_events[] = $event;
    } else {
        $locked_events[] = $event;
    }
}

// Count completed events
foreach ($participations as $p) {
    if ($p['status'] === 'evaluated') {
        $completed_events_count++;
    }
}

render_header("Simulador de Carrera - NogiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">Simulador de Carrera</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Participa en eventos simulados y construye tu reputación como DJ profesional</p>
            </div>
        </div>
        
    </header>

    <!-- Content -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <?php if ($success === 'submitted'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Tu setlist ha sido enviado al evento! Espera la puntuación y feedback del profesor.
            </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div class="grid md:grid-cols-3 gap-4">
            <!-- Badge -->
            <div class="bg-card border border-border/50 rounded-xl p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-background font-bold text-sm" style="background-color: <?php echo get_reputation_tier($student['reputation'])['color']; ?>">
                        <i data-lucide="award" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <p class="text-2xs text-muted-foreground uppercase font-bold tracking-wider">Tu Nivel de Reputación</p>
                        <h4 class="font-bold text-foreground text-sm mt-0.5"><?php echo get_reputation_tier($student['reputation'])['name']; ?></h4>
                    </div>
                </div>
            </div>
            
            <!-- Completed -->
            <div class="bg-card border border-border/50 rounded-xl p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div class="p-2 sm:p-3 bg-primary/10 rounded-lg text-primary flex-shrink-0">
                    <i data-lucide="trophy" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-foreground leading-none"><?php echo $completed_events_count; ?></h3>
                    <p class="text-xs text-muted-foreground mt-1 truncate">Eventos completados</p>
                </div>
            </div>
            
            <!-- Available -->
            <div class="bg-card border border-border/50 rounded-xl p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div class="p-2 sm:p-3 bg-success/10 rounded-lg text-success flex-shrink-0">
                    <i data-lucide="radio" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-foreground leading-none"><?php echo count($available_events); ?></h3>
                    <p class="text-xs text-muted-foreground mt-1 truncate">Eventos disponibles</p>
                </div>
            </div>
        </div>

        <!-- Reputation Tiers Progress bar -->
        <div class="bg-card border border-border/50 rounded-xl p-4 space-y-3">
            <h4 class="font-bold text-sm text-foreground">Progreso en los Tiers de Reputación</h4>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <?php 
                $tiers = [
                    ['name' => 'Bedroom', 'color' => '#9CA3AF', 'min' => 0, 'max' => 19],
                    ['name' => 'Warm-up', 'color' => '#00F2FF', 'min' => 20, 'max' => 39],
                    ['name' => 'Resident', 'color' => '#7000FF', 'min' => 40, 'max' => 59],
                    ['name' => 'Headliner', 'color' => '#FF6B35', 'min' => 60, 'max' => 79],
                    ['name' => 'Festival', 'color' => '#39FF14', 'min' => 80, 'max' => 150]
                ];
                
                foreach ($tiers as $t): 
                    $isActive = ($student['reputation'] >= $t['min'] && $student['reputation'] <= $t['max']);
                    $isPast = ($student['reputation'] > $t['max']);
                    $progress = $isActive 
                      ? (($student['reputation'] - $t['min']) / ($t['max'] - $t['min'])) * 100
                      : ($isPast ? 100 : 0);
                ?>
                    <div class="flex-1 min-w-[80px] space-y-1.5">
                        <div class="flex items-center gap-1.5 text-2xs font-semibold">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?php echo $t['color']; ?>"></span>
                            <span class="<?php echo $isActive ? 'text-foreground font-bold' : 'text-muted-foreground'; ?>">
                                <?php echo $t['name']; ?>
                            </span>
                        </div>
                        <div class="w-full h-1 bg-muted rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: <?php echo $progress; ?>%; background-color: <?php echo $t['color']; ?>"></div>
                        </div>
                        <div class="flex justify-between text-3xs text-muted-foreground">
                            <span><?php echo $t['min']; ?></span>
                            <span><?php echo $t['max']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Available Events Section -->
        <div class="space-y-4">
            <h3 class="font-bold text-lg text-foreground flex items-center gap-2">
                <i data-lucide="radio" class="h-5 w-5 text-primary"></i>
                Eventos Disponibles (<?php echo count($available_events); ?>)
            </h3>
            
            <div class="grid gap-4">
                <?php foreach ($available_events as $event): 
                    // Search participation
                    $part_item = null;
                    foreach ($participations as $p) {
                        if ($p['event_id'] === $event['id']) {
                            $part_item = $p;
                            break;
                        }
                    }
                    $has_part = (bool)$part_item;
                    $is_eval = $has_part && ($part_item['status'] === 'evaluated');
                ?>
                    <div class="bg-card border border-border/50 rounded-xl p-3 hover:border-primary/20 transition-all">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex flex-1 items-start gap-2.5 min-w-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-primary/10 text-primary rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="<?php echo $venueIcons[$event['venue_type']] ?? 'radio'; ?>" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-2">
                                        <h4 class="font-bold text-sm text-foreground leading-tight line-clamp-2 min-w-0"><?php echo htmlspecialchars($event['name']); ?></h4>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border <?php echo $difficultyColors[$event['difficulty']] ?? ''; ?> flex-shrink-0 uppercase tracking-wide mt-0.5">
                                            <?php echo $difficultyLabels[$event['difficulty']] ?? ''; ?>
                                        </span>
                                    </div>
                                    
                                    <?php 
                                        $desc = htmlspecialchars($event['description']);
                                        $is_long_mob = mb_strlen($desc) > 55;
                                        $short_desc_mob = $is_long_mob ? mb_substr($desc, 0, 55) . '...' : $desc;
                                        $is_long_desk = mb_strlen($desc) > 160;
                                        $short_desc_desk = $is_long_desk ? mb_substr($desc, 0, 160) . '...' : $desc;
                                    ?>
                                    <div class="text-[11px] sm:text-xs text-muted-foreground mt-1.5 leading-relaxed">
                                        <!-- Mobile View -->
                                        <div class="sm:hidden inline">
                                            <?php if ($is_long_mob): ?>
                                                <span id="desc-short-mob-avail-<?php echo $event['id']; ?>">
                                                    <?php echo $short_desc_mob; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-short-mob-avail-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-full-mob-avail-<?php echo $event['id']; ?>').classList.remove('hidden');">Ver más <i data-lucide="chevron-down" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                                <span id="desc-full-mob-avail-<?php echo $event['id']; ?>" class="hidden">
                                                    <?php echo $desc; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-full-mob-avail-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-short-mob-avail-<?php echo $event['id']; ?>').classList.remove('hidden');">Ocultar <i data-lucide="chevron-up" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                            <?php else: ?>
                                                <span><?php echo $desc; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Desktop View -->
                                        <div class="hidden sm:inline">
                                            <?php if ($is_long_desk): ?>
                                                <span id="desc-short-desk-avail-<?php echo $event['id']; ?>">
                                                    <?php echo $short_desc_desk; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-short-desk-avail-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-full-desk-avail-<?php echo $event['id']; ?>').classList.remove('hidden');">Ver más <i data-lucide="chevron-down" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                                <span id="desc-full-desk-avail-<?php echo $event['id']; ?>" class="hidden">
                                                    <?php echo $desc; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-full-desk-avail-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-short-desk-avail-<?php echo $event['id']; ?>').classList.remove('hidden');">Ocultar <i data-lucide="chevron-up" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                            <?php else: ?>
                                                <span><?php echo $desc; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-2.5 text-[10px] text-muted-foreground font-medium mt-1.5 w-full">
                                        <span class="flex items-center gap-1"><i data-lucide="users" class="h-3 w-3"></i><?php echo $event['audience']; ?></span>
                                        <span class="flex items-center gap-1"><i data-lucide="clock" class="h-3 w-3"></i><?php echo $event['duration']; ?>m</span>
                                        <span class="text-success flex items-center gap-0.5 font-bold"><i data-lucide="dollar-sign" class="h-3 w-3"></i><?php echo $event['payment']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-shrink-0 ml-1">
                                <a href="student_simulator.php?id=<?php echo $event['id']; ?>" class="inline-flex items-center justify-center <?php echo $has_part ? 'bg-muted text-foreground' : 'bg-primary text-primary-foreground'; ?> font-bold px-2.5 py-1.5 rounded-lg text-xs hover:opacity-90 transition-colors whitespace-nowrap">
                                    <?php echo $has_part ? 'Ver' : 'Participar'; ?>
                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5 ml-0.5 -mr-1"></i>
                                </a>
                            </div>
                        </div>
                        
                        <?php if ($has_part): ?>
                            <div class="mt-2.5 pt-2 border-t border-border/30 flex items-center gap-2 text-[10px] sm:text-xs">
                                <?php if ($is_eval): ?>
                                    <span class="text-success font-bold flex items-center gap-1">
                                        <i data-lucide="check" class="h-3.5 w-3.5"></i> Evaluado (<?php echo round($part_item['total_score'], 1); ?>)
                                    </span>
                                    <span class="text-primary font-bold ml-1">+<?php echo $part_item['xp_awarded']; ?> XP</span>
                                    <span class="text-[#39FF14] font-bold ml-1">+<?php echo $part_item['reputation_change']; ?> Rep</span>
                                <?php else: ?>
                                    <span class="text-muted-foreground flex items-center gap-1 font-medium">
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i> Esperando evaluación
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Locked Events Section -->
        <?php if (!empty($locked_events)): ?>
            <div class="space-y-4">
                <h3 class="font-bold text-lg text-foreground flex items-center gap-2">
                    <i data-lucide="lock" class="h-5 w-5 text-muted-foreground"></i>
                    Eventos Bloqueados (<?php echo count($locked_events); ?>)
                </h3>
                
                <div class="grid gap-4">
                    <?php foreach ($locked_events as $event): 
                        $rep_diff = $event['required_reputation'] - $student['reputation'];
                        $progress_pct = min(100, ($student['reputation'] / $event['required_reputation']) * 100);
                    ?>
                        <div class="bg-card border border-border/50 rounded-xl p-3 opacity-60">
                            <div class="flex items-start gap-2.5">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-muted border border-border rounded-lg flex items-center justify-center text-muted-foreground flex-shrink-0 mt-0.5">
                                    <i data-lucide="lock" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-2">
                                        <h4 class="font-bold text-sm text-foreground leading-tight line-clamp-2 min-w-0"><?php echo htmlspecialchars($event['name']); ?></h4>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border <?php echo $difficultyColors[$event['difficulty']] ?? ''; ?> flex-shrink-0 uppercase tracking-wide mt-0.5">
                                            <?php echo $difficultyLabels[$event['difficulty']] ?? ''; ?>
                                        </span>
                                    </div>
                                    <?php 
                                        $desc = htmlspecialchars($event['description']);
                                        $is_long_mob = mb_strlen($desc) > 55;
                                        $short_desc_mob = $is_long_mob ? mb_substr($desc, 0, 55) . '...' : $desc;
                                        $is_long_desk = mb_strlen($desc) > 160;
                                        $short_desc_desk = $is_long_desk ? mb_substr($desc, 0, 160) . '...' : $desc;
                                    ?>
                                    <div class="text-[11px] sm:text-xs text-muted-foreground mt-1.5 leading-relaxed">
                                        <!-- Mobile View -->
                                        <div class="sm:hidden inline">
                                            <?php if ($is_long_mob): ?>
                                                <span id="desc-short-mob-lock-<?php echo $event['id']; ?>">
                                                    <?php echo $short_desc_mob; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-short-mob-lock-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-full-mob-lock-<?php echo $event['id']; ?>').classList.remove('hidden');">Ver más <i data-lucide="chevron-down" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                                <span id="desc-full-mob-lock-<?php echo $event['id']; ?>" class="hidden">
                                                    <?php echo $desc; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-full-mob-lock-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-short-mob-lock-<?php echo $event['id']; ?>').classList.remove('hidden');">Ocultar <i data-lucide="chevron-up" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                            <?php else: ?>
                                                <span><?php echo $desc; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Desktop View -->
                                        <div class="hidden sm:inline">
                                            <?php if ($is_long_desk): ?>
                                                <span id="desc-short-desk-lock-<?php echo $event['id']; ?>">
                                                    <?php echo $short_desc_desk; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-short-desk-lock-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-full-desk-lock-<?php echo $event['id']; ?>').classList.remove('hidden');">Ver más <i data-lucide="chevron-down" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                                <span id="desc-full-desk-lock-<?php echo $event['id']; ?>" class="hidden">
                                                    <?php echo $desc; ?> 
                                                    <span class="text-primary font-bold hover:underline cursor-pointer ml-0.5 whitespace-nowrap inline-flex items-center" onclick="document.getElementById('desc-full-desk-lock-<?php echo $event['id']; ?>').classList.add('hidden'); document.getElementById('desc-short-desk-lock-<?php echo $event['id']; ?>').classList.remove('hidden');">Ocultar <i data-lucide="chevron-up" class="h-3 w-3 ml-0.5"></i></span>
                                                </span>
                                            <?php else: ?>
                                                <span><?php echo $desc; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 max-w-xs space-y-1.5 pr-2">
                                        <div class="flex items-center justify-between gap-2 text-[10px] text-muted-foreground font-semibold flex-wrap">
                                            <div class="flex items-center gap-2">
                                                <span><?php echo $student['reputation']; ?> / <?php echo $event['required_reputation']; ?> Rep</span>
                                                <span>(Faltan <?php echo $rep_diff; ?>)</span>
                                            </div>
                                        </div>
                                        <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div class="h-full bg-primary" style="width: <?php echo $progress_pct; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
render_footer();
?>
