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
    <div class="flex-1 flex flex-col min-w-0 bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6 py-4">
            <a href="student_simulator.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-foreground"><?php echo htmlspecialchars($event['name']); ?></h1>
                <p class="text-sm text-muted-foreground">Detalle del evento y setlist</p>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
            
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
                        <!-- Setlist -->
                        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                            <h4 class="font-bold text-base text-foreground flex items-center gap-2">
                                <i data-lucide="list" class="h-5 w-5 text-primary"></i>
                                Tu Setlist Presentado
                            </h4>
                            
                            <div class="space-y-2">
                                <?php foreach ($tracks as $track): ?>
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-muted/20 border border-border/30">
                                        <div class="flex items-center gap-3">
                                            <span class="w-7 h-7 rounded-full bg-primary/10 text-primary border border-primary/20 flex items-center justify-center text-xs font-bold">
                                                <?php echo $track['position']; ?>
                                            </span>
                                            <div>
                                                <p class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($track['track_name']); ?></p>
                                                <p class="text-xs text-muted-foreground mt-0.5"><?php echo htmlspecialchars($track['artist']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right text-xs text-muted-foreground">
                                            <span><?php echo $track['bpm']; ?> BPM</span>
                                            <?php if (!empty($track['track_key'])): ?>
                                                <span class="ml-2 px-1.5 py-0.5 bg-muted rounded text-3xs font-semibold"><?php echo htmlspecialchars($track['track_key']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
                                Crea tu Setlist (Mínimo 3 Tracks)
                            </h3>
                            <button type="button" onclick="addTrackRow()" class="text-xs bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded-lg hover:bg-primary/95 transition-colors">
                                + Agregar Track
                            </button>
                        </div>
                        
                        <!-- Tracks container -->
                        <div id="tracks-container" class="space-y-4">
                            <!-- Track Row template -->
                            <div class="track-row p-4 rounded-lg bg-muted/20 border border-border/30 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-foreground flex items-center gap-2">
                                        <span class="track-number w-5 h-5 rounded-full bg-primary/20 text-primary text-2xs flex items-center justify-center font-bold">1</span>
                                        Track
                                    </span>
                                    <button type="button" onclick="removeTrackRow(this)" class="remove-btn text-destructive hover:bg-destructive/10 p-1.5 rounded-lg hidden">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                                
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Nombre del Track</label>
                                        <input required name="track_name[]" placeholder="Ej: Sandstorm" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Artista</label>
                                        <input required name="artist[]" placeholder="Ej: Darude" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-2xs font-semibold text-muted-foreground uppercase">BPM</label>
                                        <input required name="bpm[]" type="number" min="50" max="250" value="128" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Tonalidad (Key)</label>
                                        <input required name="key[]" placeholder="Ej: 8A, Am" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Notas de Mezcla</label>
                                        <input name="notes[]" placeholder="Opcional" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    </div>
                                </div>
                            </div>
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

    <!-- Script for Dynamic Track Row Builder -->
    <script>
        function addTrackRow() {
            const container = document.getElementById('tracks-container');
            const rowCount = container.getElementsByClassName('track-row').length;
            
            // Create element
            const newRow = document.createElement('div');
            newRow.className = 'track-row p-4 rounded-lg bg-muted/20 border border-border/30 space-y-3';
            newRow.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-foreground flex items-center gap-2">
                        <span class="track-number w-5 h-5 rounded-full bg-primary/20 text-primary text-2xs flex items-center justify-center font-bold">${rowCount + 1}</span>
                        Track
                    </span>
                    <button type="button" onclick="removeTrackRow(this)" class="remove-btn text-destructive hover:bg-destructive/10 p-1.5 rounded-lg">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Nombre del Track</label>
                        <input required name="track_name[]" placeholder="Ej: Sandstorm" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Artista</label>
                        <input required name="artist[]" placeholder="Ej: Darude" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-2xs font-semibold text-muted-foreground uppercase">BPM</label>
                        <input required name="bpm[]" type="number" min="50" max="250" value="128" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Tonalidad (Key)</label>
                        <input required name="key[]" placeholder="Ej: 8A, Am" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-semibold text-muted-foreground uppercase">Notas de Mezcla</label>
                        <input name="notes[]" placeholder="Opcional" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>
                </div>
            `;
            
            container.appendChild(newRow);
            lucide.createIcons();
            updateRemoveButtons();
        }

        function removeTrackRow(button) {
            const row = button.closest('.track-row');
            row.remove();
            
            // Recalculate numbers
            const container = document.getElementById('tracks-container');
            const rows = container.getElementsByClassName('track-row');
            for (let i = 0; i < rows.length; i++) {
                rows[i].querySelector('.track-number').innerText = i + 1;
            }
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const container = document.getElementById('tracks-container');
            const rows = container.getElementsByClassName('track-row');
            const removeButtons = container.getElementsByClassName('remove-btn');
            
            if (rows.length <= 1) {
                removeButtons[0].classList.add('hidden');
            } else {
                for (let i = 0; i < removeButtons.length; i++) {
                    removeButtons[i].classList.remove('hidden');
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            // Initial check
            updateRemoveButtons();
        });
    </script>
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
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Simulador de Carrera</h1>
            <p class="text-sm text-muted-foreground">Participa en eventos simulados y construye tu reputación como DJ profesional</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
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
            <div class="bg-card border border-border/50 rounded-xl p-5 flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-lg text-primary">
                    <i data-lucide="trophy" class="h-6 w-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-foreground leading-none"><?php echo $completed_events_count; ?></h3>
                    <p class="text-2xs text-muted-foreground mt-1">Eventos completados</p>
                </div>
            </div>
            
            <!-- Available -->
            <div class="bg-card border border-border/50 rounded-xl p-5 flex items-center gap-4">
                <div class="p-3 bg-success/10 rounded-lg text-success">
                    <i data-lucide="radio" class="h-6 w-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-foreground leading-none"><?php echo count($available_events); ?></h3>
                    <p class="text-2xs text-muted-foreground mt-1">Eventos disponibles</p>
                </div>
            </div>
        </div>

        <!-- Reputation Tiers Progress bar -->
        <div class="bg-card border border-border/50 rounded-xl p-5 space-y-3">
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
                    <div class="flex-1 min-w-[90px] space-y-1.5">
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
                    <div class="bg-card border border-border/50 rounded-xl p-6 flex flex-col md:flex-row md:items-start justify-between gap-6 hover:border-primary/20 transition-all">
                        <div class="flex gap-4">
                            <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center flex-shrink-0">
                                <i data-lucide="<?php echo $venueIcons[$event['venue_type']] ?? 'radio'; ?>" class="h-7 w-7"></i>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-base text-foreground leading-none"><?php echo htmlspecialchars($event['name']); ?></h4>
                                    <span class="text-3xs font-semibold px-2 py-0.5 rounded border <?php echo $difficultyColors[$event['difficulty']] ?? ''; ?>">
                                        <?php echo $difficultyLabels[$event['difficulty']] ?? ''; ?>
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground mt-2 leading-relaxed max-w-xl"><?php echo htmlspecialchars($event['description']); ?></p>
                                
                                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground pt-3">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                        <?php echo $event['audience']; ?> personas
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                        <?php echo $event['duration']; ?> min
                                    </span>
                                    <span>•</span>
                                    <span class="text-success font-semibold flex items-center gap-0.5">
                                        <i data-lucide="dollar-sign" class="h-3.5 w-3.5"></i>
                                        <?php echo $event['payment']; ?>
                                    </span>
                                </div>
                                
                                <!-- Participation mini status banner inside available card -->
                                <?php if ($has_part): ?>
                                    <div class="mt-4 pt-3 border-t border-border/30 flex items-center gap-3 text-xs">
                                        <?php if ($is_eval): ?>
                                            <span class="text-success font-bold flex items-center gap-1">
                                                <i data-lucide="check" class="h-4 w-4"></i> Evaluado (Nota: <?php echo round($part_item['total_score'], 1); ?>)
                                            </span>
                                            <span class="text-primary font-medium">+<?php echo $part_item['xp_awarded']; ?> XP</span>
                                            <span class="text-[#39FF14] font-medium">+<?php echo $part_item['reputation_change']; ?> Rep</span>
                                        <?php else: ?>
                                            <span class="text-muted-foreground flex items-center gap-1">
                                                <i data-lucide="clock" class="h-4 w-4"></i> Esperando evaluación de setlist
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <a href="student_simulator.php?id=<?php echo $event['id']; ?>" class="inline-flex items-center gap-1.5 <?php echo $has_part ? 'bg-muted text-foreground' : 'bg-primary text-primary-foreground'; ?> font-semibold px-4 py-2 rounded-lg text-xs hover:opacity-90 transition-colors">
                                <?php echo $has_part ? 'Ver detalles' : 'Participar'; ?>
                                <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>
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
                        <div class="bg-card border border-border/50 rounded-xl p-6 flex flex-col sm:flex-row justify-between gap-6 opacity-60">
                            <div class="flex gap-4">
                                <div class="w-14 h-14 bg-muted border border-border rounded-xl flex items-center justify-center text-muted-foreground flex-shrink-0">
                                    <i data-lucide="lock" class="h-7 w-7"></i>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-base text-foreground"><?php echo htmlspecialchars($event['name']); ?></h4>
                                    <span class="inline-block text-3xs font-semibold px-2 py-0.5 rounded border <?php echo $difficultyColors[$event['difficulty']] ?? ''; ?>">
                                        <?php echo $difficultyLabels[$event['difficulty']] ?? ''; ?>
                                    </span>
                                    <p class="text-xs text-muted-foreground pt-1"><?php echo htmlspecialchars($event['description']); ?></p>
                                    
                                    <div class="pt-2 max-w-xs space-y-1">
                                        <div class="flex justify-between text-3xs text-muted-foreground font-semibold">
                                            <span>Progreso de Reputación</span>
                                            <span><?php echo $student['reputation']; ?> / <?php echo $event['required_reputation']; ?> Rep</span>
                                        </div>
                                        <div class="w-full h-1 bg-muted rounded-full overflow-hidden">
                                            <div class="h-full bg-primary" style="width: <?php echo $progress_pct; ?>%"></div>
                                        </div>
                                        <p class="text-3xs text-muted-foreground">Necesitas <?php echo $rep_diff; ?> puntos más de reputación</p>
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
