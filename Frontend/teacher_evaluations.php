<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

$set_id = $_GET['set_id'] ?? '';
$part_id = $_GET['participation_id'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// 1. Fetch details of selected set (if modal requested)
$selected_set = null;
if (!empty($set_id)) {
    $stmt = $pdo->prepare("SELECT s.*, u.name as student_name, u.avatar as student_avatar FROM dj_sets s JOIN users u ON s.student_id = u.id WHERE s.id = ?");
    $stmt->execute([$set_id]);
    $selected_set = $stmt->fetch();
}

// 2. Fetch details of selected event participation (if modal requested)
$selected_part = null;
$selected_tracks = [];
if (!empty($part_id)) {
    $stmt = $pdo->prepare("
        SELECT ep.*, u.name as student_name, u.avatar as student_avatar, se.name as event_name, se.music_styles, se.payment, se.description as event_description
        FROM event_participations ep 
        JOIN users u ON ep.student_id = u.id 
        JOIN simulated_events se ON ep.event_id = se.id 
        WHERE ep.id = ?
    ");
    $stmt->execute([$part_id]);
    $selected_part = $stmt->fetch();
    
    if ($selected_part) {
        $stmt_tr = $pdo->prepare("SELECT * FROM setlist_tracks WHERE participation_id = ? ORDER BY position ASC");
        $stmt_tr->execute([$part_id]);
        $selected_tracks = $stmt_tr->fetchAll();
    }
}

// 3. Fetch pending lists for background view
// Fetch pending sets
$stmt_sets = $pdo->prepare("
    SELECT s.*, u.name as student_name, u.avatar as student_avatar 
    FROM dj_sets s 
    JOIN users u ON s.student_id = u.id 
    WHERE s.id NOT IN (SELECT set_id FROM set_evaluations) 
    ORDER BY s.uploaded_at ASC
");
$stmt_sets->execute();
$pending_sets = $stmt_sets->fetchAll();

// Fetch pending event participations
$stmt_parts = $pdo->prepare("
    SELECT ep.*, u.name as student_name, u.avatar as student_avatar, se.name as event_name, se.difficulty, se.required_reputation 
    FROM event_participations ep 
    JOIN users u ON ep.student_id = u.id 
    JOIN simulated_events se ON ep.event_id = se.id 
    WHERE ep.status = 'pending' 
    ORDER BY ep.submitted_at ASC
");
$stmt_parts->execute();
$pending_participations = $stmt_parts->fetchAll();

$active_tab = $_GET['tab'] ?? 'sets';

// Switch active tab if a modal request is active
if (!empty($set_id)) {
    $active_tab = 'sets';
} elseif (!empty($part_id)) {
    $active_tab = 'events';
}

render_header("Evaluaciones Pendientes - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Evaluaciones Pendientes</h1>
            <p class="text-sm text-muted-foreground">Califica trabajos y setlists de simulador entregados por los alumnos</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'set_evaluated'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Set evaluado con éxito! Se han recalculado los niveles y reputación del estudiante.
            </div>
        <?php elseif ($success === 'event_evaluated'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Participación evaluada con éxito! El alumno ha sido notificado con las puntuaciones.
            </div>
        <?php elseif ($error === 'empty'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Error al procesar la evaluación. Todos los campos son obligatorios.
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="space-y-6">
            <div class="flex border-b border-border gap-2">
                <a href="teacher_evaluations.php?tab=sets" class="px-4 py-2 text-sm font-semibold border-b-2 <?php echo $active_tab === 'sets' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Sets DJ (<?php echo count($pending_sets); ?>)
                </a>
                <a href="teacher_evaluations.php?tab=events" class="px-4 py-2 text-sm font-semibold border-b-2 <?php echo $active_tab === 'events' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Eventos de Simulador (<?php echo count($pending_participations); ?>)
                </a>
            </div>

            <!-- List display -->
            <?php if ($active_tab === 'sets'): ?>
                <!-- Pending sets -->
                <?php if (empty($pending_sets)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-12 text-center text-muted-foreground">
                        <i data-lucide="check-circle" class="h-12 w-12 mx-auto text-success opacity-50 mb-3"></i>
                        <p class="text-base">¡No hay sets de DJ pendientes de evaluar!</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($pending_sets as $set): ?>
                            <div class="bg-card border border-border/50 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:border-primary/20 transition-all">
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="music" class="h-6 w-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($set['title']); ?></h4>
                                        <p class="text-xs text-muted-foreground mt-0.5">Alumno: <?php echo htmlspecialchars($set['student_name']); ?> • Género: <?php echo htmlspecialchars($set['genre']); ?></p>
                                        <p class="text-3xs text-muted-foreground mt-1">Subido el <?php echo date('d/m/Y H:i', strtotime($set['uploaded_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="p-2 border border-border rounded text-muted-foreground hover:text-primary transition-all">
                                        <i data-lucide="external-link" class="h-4 w-4"></i>
                                    </a>
                                    <a href="teacher_evaluations.php?set_id=<?php echo $set['id']; ?>" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded text-xs hover:bg-primary/95 transition-all">
                                        Evaluar Set
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Pending events -->
                <?php if (empty($pending_participations)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-12 text-center text-muted-foreground">
                        <i data-lucide="check-circle" class="h-12 w-12 mx-auto text-success opacity-50 mb-3"></i>
                        <p class="text-base">¡No hay participaciones de eventos pendientes de evaluar!</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($pending_participations as $part): ?>
                            <div class="bg-card border border-border/50 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:border-primary/20 transition-all">
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="radio" class="h-6 w-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-foreground"><?php echo htmlspecialchars($part['event_name']); ?></h4>
                                        <p class="text-xs text-muted-foreground mt-0.5">Alumno: <?php echo htmlspecialchars($part['student_name']); ?> • Dificultad: <?php echo htmlspecialchars($part['difficulty']); ?></p>
                                        <p class="text-3xs text-muted-foreground mt-1">Presentado el <?php echo date('d/m/Y H:i', strtotime($part['submitted_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="teacher_evaluations.php?participation_id=<?php echo $part['id']; ?>" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded text-xs hover:bg-primary/95 transition-all">
                                        Evaluar Setlist
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </<!-- ================= MODALS OVERLAYS ================= -->

<!-- Style tags for sliders -->
<style>
  .modal-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 8px;
    border-radius: 9999px;
    background: #252830;
    outline: none;
    cursor: pointer;
  }
  .modal-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2.5px solid #00F2FF;
    cursor: pointer;
    box-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
    transition: transform 0.1s ease;
  }
  .modal-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
  }
  .modal-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2.5px solid #00F2FF;
    cursor: pointer;
    box-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
    transition: transform 0.1s ease;
  }
</style>

<!-- 1. EVALUAR SET MODAL -->
<?php if ($selected_set): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4">
        <!-- Modal panel matching screenshot but larger -->
        <div class="w-full max-w-[540px] bg-[#15181C] border border-[#2D3139] rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-5 border-b border-[#2D3139]/40 flex-shrink-0">
                <h3 class="text-base font-extrabold text-foreground truncate">Evaluar Set: <?php echo htmlspecialchars($selected_set['title']); ?></h3>
                <a href="teacher_evaluations.php" class="text-muted-foreground hover:text-foreground p-1 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </a>
            </div>

            <!-- Modal Content - Scrollable if screen too small -->
            <div class="p-8 overflow-y-auto space-y-6 flex-1 select-none">
                
                <!-- Student Info Card -->
                <div class="bg-muted/10 border border-border/30 rounded-xl p-5 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo htmlspecialchars($selected_set['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-12 h-12 rounded-full border bg-muted flex-shrink-0">
                        <div class="min-w-0">
                            <h4 class="font-bold text-sm text-foreground leading-none truncate"><?php echo htmlspecialchars($selected_set['student_name']); ?></h4>
                            <p class="text-xs text-muted-foreground mt-1.5 leading-none truncate">
                                <?php echo htmlspecialchars($selected_set['genre']); ?> - <?php echo $selected_set['duration']; ?> min
                            </p>
                        </div>
                    </div>
                    <a href="<?php echo htmlspecialchars($selected_set['url']); ?>" target="_blank" class="inline-flex items-center gap-2 border border-border bg-[#252830] hover:bg-[#2D3139] text-foreground text-xs font-semibold px-4 py-2 rounded-lg transition-colors flex-shrink-0">
                        <i data-lucide="external-link" class="h-4 w-4"></i>
                        <span>Escuchar</span>
                    </a>
                </div>

                <form action="../Backend/scripts/actions.php" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="evaluate_set">
                    <input type="hidden" name="set_id" value="<?php echo $selected_set['id']; ?>">

                    <!-- Sliders Grid -->
                    <div class="space-y-5 pt-1">
                        <!-- Técnica -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Técnica</h5>
                                    <p class="text-xs text-muted-foreground leading-none mt-0.5">Beatmatching, mezclas, uso de EQ</p>
                                </div>
                                <span id="tech-val" class="text-2xl font-black text-primary">5</span>
                            </div>
                            <input type="range" id="tech-slider" name="technique" min="1" max="10" value="5" class="modal-slider mt-2" oninput="updateScores()">
                            <div class="flex justify-between text-xs text-muted-foreground px-1 mt-1 leading-none">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>

                        <!-- Coherencia -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Coherencia</h5>
                                    <p class="text-xs text-muted-foreground leading-none mt-0.5">Flujo del set, selección musical</p>
                                </div>
                                <span id="coh-val" class="text-2xl font-black text-primary">5</span>
                            </div>
                            <input type="range" id="coh-slider" name="coherence" min="1" max="10" value="5" class="modal-slider mt-2" oninput="updateScores()">
                            <div class="flex justify-between text-xs text-muted-foreground px-1 mt-1 leading-none">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>

                        <!-- Creatividad -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Creatividad</h5>
                                    <p class="text-xs text-muted-foreground leading-none mt-0.5">Originalidad, riesgos tomados</p>
                                </div>
                                <span id="crea-val" class="text-2xl font-black text-primary">5</span>
                            </div>
                            <input type="range" id="crea-slider" name="creativity" min="1" max="10" value="5" class="modal-slider mt-2" oninput="updateScores()">
                            <div class="flex justify-between text-xs text-muted-foreground px-1 mt-1 leading-none">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>

                        <!-- Adaptación -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Adaptación</h5>
                                    <p class="text-xs text-muted-foreground leading-none mt-0.5">Ajuste al contexto/estilo</p>
                                </div>
                                <span id="adapt-val" class="text-2xl font-black text-primary">5</span>
                            </div>
                            <input type="range" id="adapt-slider" name="adaptation" min="1" max="10" value="5" class="modal-slider mt-2" oninput="updateScores()">
                            <div class="flex justify-between text-xs text-muted-foreground px-1 mt-1 leading-none">
                                <span>1</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>

                    <!-- Final Score Panel matching design but larger -->
                    <div class="bg-[#102A30] border border-primary/20 rounded-xl p-5 flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-base text-foreground">Puntuación Final</h4>
                            <p class="text-xs text-muted-foreground mt-0.5">Promedio de los 4 criterios</p>
                        </div>
                        <div class="text-right space-y-1 flex flex-col items-end">
                            <h3 id="final-score" class="text-4xl font-black text-primary leading-none">5.0</h3>
                            <div class="flex gap-1.5 pt-1.5">
                                <span id="xp-badge" class="px-2.5 py-1 bg-primary/10 border border-primary/20 text-primary text-[11px] font-bold rounded">+125 XP</span>
                                <span id="rep-badge" class="px-2.5 py-1 bg-success/10 border border-success/20 text-success text-[11px] font-bold rounded">+2 Rep</span>
                            </div>
                        </div>
                    </div>

                    <!-- Textarea Feedback -->
                    <div class="space-y-2">
                        <label for="feedback" class="text-sm font-semibold text-foreground">Feedback para el alumno</label>
                        <textarea id="feedback" name="feedback" required rows="4" placeholder="Escribe comentarios constructivos sobre el set..." class="w-full bg-input border border-border rounded-lg px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                    </div>

                    <!-- Actions Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#2D3139]/40 flex-shrink-0">
                        <a href="teacher_evaluations.php" class="bg-transparent hover:bg-muted/10 border border-border text-foreground font-semibold px-5 py-2.5 rounded-lg text-sm transition-colors flex items-center justify-center">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-primary text-primary-foreground font-bold px-5 py-2.5 rounded-lg text-sm hover:bg-primary/95 transition-colors flex items-center justify-center gap-1.5 shadow-lg shadow-primary/10">
                            <i data-lucide="check-circle" class="h-4.5 w-4.5"></i>
                            Enviar Evaluación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 2. EVALUAR EVENT PARTICIPATION MODAL -->
<?php if ($selected_part): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4">
        <!-- Modal panel - Larger -->
        <div class="w-full max-w-[760px] bg-[#15181C] border border-[#2D3139] rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-5 border-b border-[#2D3139]/40 flex-shrink-0">
                <h3 class="text-base font-extrabold text-foreground truncate">Evaluar Evento: <?php echo htmlspecialchars($selected_part['event_name']); ?></h3>
                <a href="teacher_evaluations.php" class="text-muted-foreground hover:text-foreground p-1 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </a>
            </div>

            <!-- Content Area - 2 Column Split inside scrollable modal -->
            <div class="p-8 overflow-y-auto flex-1 select-none space-y-6">
                <div class="grid md:grid-cols-2 gap-8">
                    
                    <!-- Left: Submission details -->
                    <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-1">
                        <!-- Student details -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-5 flex items-center gap-4">
                            <img src="<?php echo htmlspecialchars($selected_part['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-12 h-12 rounded-full border bg-muted flex-shrink-0">
                            <div class="min-w-0">
                                <h4 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($selected_part['student_name']); ?></h4>
                                <p class="text-xs text-muted-foreground leading-none mt-1">Presentado el <?php echo date('d/m/Y H:i', strtotime($selected_part['submitted_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-5 text-xs space-y-1">
                            <p class="text-muted-foreground leading-none text-xs">
                                Estilos del Evento: <strong class="text-foreground"><?php echo htmlspecialchars($selected_part['music_styles']); ?></strong>
                            </p>
                        </div>

                        <!-- Setlist tracks -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-5 space-y-4">
                            <h5 class="text-sm font-bold text-foreground flex items-center gap-2">
                                <i data-lucide="music" class="h-4.5 w-4.5 text-primary"></i>
                                Setlist Propuesto
                            </h5>
                            
                            <div class="space-y-2">
                                <?php foreach ($selected_tracks as $track): ?>
                                    <div class="flex items-center justify-between p-3 rounded bg-muted/20 border border-border/30 text-xs">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-6 h-6 rounded-full bg-primary/10 text-primary border border-primary/20 flex items-center justify-center font-bold text-xs"><?php echo $track['position']; ?></span>
                                            <div class="min-w-0">
                                                <p class="font-bold text-foreground truncate text-xs leading-tight"><?php echo htmlspecialchars($track['track_name']); ?></p>
                                                <p class="text-muted-foreground text-xs truncate leading-none mt-1"><?php echo htmlspecialchars($track['artist']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-xs text-muted-foreground text-right">
                                            <span><?php echo $track['bpm']; ?> BPM</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-5 space-y-2">
                            <h5 class="text-sm font-bold text-foreground">Justificación</h5>
                            <p class="text-xs text-muted-foreground leading-relaxed bg-black/10 p-3 rounded border border-border/30 max-h-[140px] overflow-y-auto">
                                <?php echo nl2br(htmlspecialchars($selected_part['justification'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Right: Evaluation Form -->
                    <form action="../Backend/scripts/actions.php" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="evaluate_event">
                        <input type="hidden" name="participation_id" value="<?php echo $selected_part['id']; ?>">

                        <div class="space-y-4">
                            <h4 class="text-xs font-bold text-foreground uppercase tracking-wider border-b border-[#2D3139] pb-2">Calificaciones (1 al 10)</h4>
                            
                            <!-- Track Selection -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-foreground">Selección de Tracks</span>
                                    <span id="ev-track-val" class="text-base font-bold text-primary">7</span>
                                </div>
                                <input type="range" id="ev-track-slider" name="track_selection" min="1" max="10" value="7" class="modal-slider" oninput="updateEventScores()">
                            </div>

                            <!-- Energy Flow -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-foreground">Flujo de Energía</span>
                                    <span id="ev-energy-val" class="text-base font-bold text-primary">7</span>
                                </div>
                                <input type="range" id="ev-energy-slider" name="energy_flow" min="1" max="10" value="7" class="modal-slider" oninput="updateEventScores()">
                            </div>

                            <!-- Style Match -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-foreground">Coincidencia de Estilo</span>
                                    <span id="ev-style-val" class="text-base font-bold text-primary">7</span>
                                </div>
                                <input type="range" id="ev-style-slider" name="style_match" min="1" max="10" value="7" class="modal-slider" oninput="updateEventScores()">
                            </div>

                            <!-- Transitions -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-foreground">Transiciones</span>
                                    <span id="ev-trans-val" class="text-base font-bold text-primary">7</span>
                                </div>
                                <input type="range" id="ev-trans-slider" name="transitions" min="1" max="10" value="7" class="modal-slider" oninput="updateEventScores()">
                            </div>

                            <!-- Crowd Adaptation -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-foreground">Adaptación de Público</span>
                                    <span id="ev-crowd-val" class="text-base font-bold text-primary">7</span>
                                </div>
                                <input type="range" id="ev-crowd-slider" name="crowd_adaptation" min="1" max="10" value="7" class="modal-slider" oninput="updateEventScores()">
                            </div>
                        </div>

                        <!-- Final score -->
                        <div class="bg-[#102A30] border border-primary/20 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-sm text-foreground leading-none">Puntuación Final</h4>
                                <p class="text-xs text-muted-foreground mt-1">Promedio de los 5 criterios</p>
                            </div>
                            <div class="text-right space-y-1 flex flex-col items-end">
                                <h3 id="ev-final-score" class="text-3xl font-black text-primary leading-none">7.0</h3>
                                <div class="flex gap-1.5 pt-1">
                                    <span id="ev-xp-badge" class="px-2 py-0.5 bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold rounded">+210 XP</span>
                                    <span id="ev-rep-badge" class="px-2 py-0.5 bg-success/10 border border-success/20 text-success text-[10px] font-bold rounded">+10 Rep</span>
                                </div>
                            </div>
                        </div>

                        <!-- Textarea feedback -->
                        <div class="space-y-1.5">
                            <label for="feedback" class="text-xs font-semibold text-foreground">Feedback del Profesor</label>
                            <textarea id="feedback" name="feedback" required rows="3" placeholder="Comenta la progresión de BPMs, transiciones armónicas sugeridas..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                        </div>

                        <!-- Actions Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2D3139]/40 flex-shrink-0">
                            <a href="teacher_evaluations.php" class="bg-transparent hover:bg-muted/10 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors flex items-center justify-center">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-primary text-primary-foreground font-bold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors flex items-center justify-center gap-1.5 shadow-lg shadow-primary/10">
                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                Enviar Evaluación
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Real-time JS range math -->
<script>
function updateSliderBackground(slider) {
    const value = (slider.value - slider.min) / (slider.max - slider.min) * 100;
    slider.style.background = `linear-gradient(to right, #00F2FF 0%, #00F2FF ${value}%, #252830 ${value}%, #252830 100%)`;
}

function updateScores() {
    const techSlider = document.getElementById('tech-slider');
    const cohSlider = document.getElementById('coh-slider');
    const creaSlider = document.getElementById('crea-slider');
    const adaptSlider = document.getElementById('adapt-slider');
    
    if (!techSlider) return;
    
    updateSliderBackground(techSlider);
    updateSliderBackground(cohSlider);
    updateSliderBackground(creaSlider);
    updateSliderBackground(adaptSlider);
    
    const tech = parseInt(techSlider.value);
    const coh = parseInt(cohSlider.value);
    const crea = parseInt(creaSlider.value);
    const adapt = parseInt(adaptSlider.value);
    
    document.getElementById('tech-val').innerText = tech;
    document.getElementById('coh-val').innerText = coh;
    document.getElementById('crea-val').innerText = crea;
    document.getElementById('adapt-val').innerText = adapt;
    
    const avg = (tech + coh + crea + adapt) / 4;
    document.getElementById('final-score').innerText = avg.toFixed(1);
    
    const xp = Math.round(avg * 25);
    const rep = Math.floor(avg / 2);
    
    document.getElementById('xp-badge').innerText = `+${xp} XP`;
    document.getElementById('rep-badge').innerText = `+${rep} Rep`;
}

function updateEventScores() {
    const trackSlider = document.getElementById('ev-track-slider');
    const energySlider = document.getElementById('ev-energy-slider');
    const styleSlider = document.getElementById('ev-style-slider');
    const transSlider = document.getElementById('ev-trans-slider');
    const crowdSlider = document.getElementById('ev-crowd-slider');
    
    if (!trackSlider) return;
    
    updateSliderBackground(trackSlider);
    updateSliderBackground(energySlider);
    updateSliderBackground(styleSlider);
    updateSliderBackground(transSlider);
    updateSliderBackground(crowdSlider);
    
    const track = parseInt(trackSlider.value);
    const energy = parseInt(energySlider.value);
    const style = parseInt(styleSlider.value);
    const trans = parseInt(transSlider.value);
    const crowd = parseInt(crowdSlider.value);
    
    document.getElementById('ev-track-val').innerText = track;
    document.getElementById('ev-energy-val').innerText = energy;
    document.getElementById('ev-style-val').innerText = style;
    document.getElementById('ev-trans-val').innerText = trans;
    document.getElementById('ev-crowd-val').innerText = crowd;
    
    const avg = (track + energy + style + trans + crowd) / 5;
    document.getElementById('ev-final-score').innerText = avg.toFixed(1);
    
    const xp = Math.round(avg * 30);
    const rep = Math.round(avg * 1.5);
    
    document.getElementById('ev-xp-badge').innerText = `+${xp} XP`;
    document.getElementById('ev-rep-badge').innerText = `+${rep} Rep`;
}

// Perform initial execution of sliders styling when loaded
document.addEventListener('DOMContentLoaded', () => {
    updateScores();
    updateEventScores();
});
</script>

<?php
render_footer();
?>
