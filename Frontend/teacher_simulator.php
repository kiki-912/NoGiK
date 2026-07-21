<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

$active_tab = $_GET['tab'] ?? 'pending';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$show_create_modal = isset($_GET['create_event']) && $_GET['create_event'] === '1';

$part_id = $_GET['participation_id'] ?? '';

// 1. Fetch details of selected event participation for evaluation modal
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
        // No extra queries needed since set_url is in ep
    }
}

// 2. Fetch pending event participations
$stmt_pend = $pdo->prepare("
    SELECT ep.*, u.name as student_name, u.avatar as student_avatar, se.name as event_name, se.difficulty 
    FROM event_participations ep 
    JOIN users u ON ep.student_id = u.id 
    JOIN simulated_events se ON ep.event_id = se.id 
    WHERE ep.status = 'pending' 
    ORDER BY ep.submitted_at ASC
");
$stmt_pend->execute();
$pending_parts = $stmt_pend->fetchAll();

// 3. Fetch evaluated event participations
$stmt_eval = $pdo->prepare("
    SELECT ep.*, u.name as student_name, u.avatar as student_avatar, se.name as event_name, se.difficulty, ee.total_score, ee.evaluated_at 
    FROM event_participations ep 
    JOIN users u ON ep.student_id = u.id 
    JOIN simulated_events se ON ep.event_id = se.id 
    JOIN event_evaluations ee ON ep.id = ee.participation_id 
    WHERE ep.status = 'evaluated' 
    ORDER BY ee.evaluated_at DESC
");
$stmt_eval->execute();
$evaluated_parts = $stmt_eval->fetchAll();

// 4. Fetch all simulated events
$stmt_evs = $pdo->prepare("
    SELECT se.*, 
           (SELECT COUNT(*) FROM event_participations WHERE event_id = se.id) as submissions_count 
    FROM simulated_events se 
    ORDER BY se.id DESC
");
$stmt_evs->execute();
$events = $stmt_evs->fetchAll();

// Calculate counts
$total_events = count($events);
$total_pending = count($pending_parts);
$total_evaluated = count($evaluated_parts);

render_header("Simulador de Carrera - NoGiK");
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
                <h1 class="whitespace-normal text-xl sm:text-2xl font-bold text-foreground ">Simulador</h1>
                <p class="text-xs sm:text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Gestiona eventos y evalúa participaciones</p>
            </div>
        </div>
        
        <a href="teacher_simulator.php?create_event=1" class="inline-flex items-center gap-1.5 bg-[#00F2FF] text-[#0F1115] font-bold px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs sm:text-sm hover:opacity-90 transition-colors shrink-0">
            <i data-lucide="globe" class="h-4 w-4"></i>
            <span>Nuevo Evento</span>
        </a>
    </header>

    <!-- Content Area -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <?php if ($success === 'event_created'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Evento de simulación creado con éxito! Los alumnos ya pueden postular sus sets.
            </div>
        <?php elseif ($success === 'event_evaluated'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Participación evaluada con éxito! El alumno ha sido notificado con las puntuaciones.
            </div>
        <?php elseif ($error === 'empty'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Por favor, completa los campos requeridos para el evento.
            </div>
        <?php endif; ?>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Eventos Totales -->
            <div class="bg-card border border-border/50 rounded-xl p-6 flex justify-between items-start">
                <div class="space-y-2">
                    <p class="text-xs text-muted-foreground font-semibold">Eventos totales</p>
                    <h3 class="text-3xl font-extrabold text-foreground leading-none"><?php echo $total_events; ?></h3>
                </div>
                <div class="w-6 h-6 rounded-lg bg-[#00F2FF]"></div>
            </div>

            <!-- Pendientes de Evaluar -->
            <div class="bg-card border border-border/50 rounded-xl p-6 flex justify-between items-start">
                <div class="space-y-2">
                    <p class="text-xs text-muted-foreground font-semibold">Pendientes de evaluar</p>
                    <h3 class="text-3xl font-extrabold text-foreground leading-none"><?php echo $total_pending; ?></h3>
                </div>
                <div class="w-6 h-6 rounded-lg bg-[#7000FF]"></div>
            </div>

            <!-- Evaluadas -->
            <div class="bg-card border border-border/50 rounded-xl p-6 flex justify-between items-start">
                <div class="space-y-2">
                    <p class="text-xs text-muted-foreground font-semibold">Evaluadas</p>
                    <h3 class="text-3xl font-extrabold text-foreground leading-none"><?php echo $total_evaluated; ?></h3>
                </div>
                <div class="text-muted-foreground p-1">
                    <i data-lucide="music-4" class="h-6 w-6"></i>
                </div>
            </div>
        </div>

        <!-- Tabs Pill bar -->
        <div class="flex gap-2 bg-[#101216] p-1 rounded-xl w-full sm:w-max border border-border/30 overflow-x-auto scrollbar-none flex-nowrap">
            <a href="teacher_simulator.php?tab=pending" class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex-shrink-0 <?php echo $active_tab === 'pending' ? 'bg-[#1D2026] text-foreground' : 'text-muted-foreground hover:text-foreground'; ?>">
                Pendientes (<?php echo $total_pending; ?>)
            </a>
            <a href="teacher_simulator.php?tab=evaluated" class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex-shrink-0 <?php echo $active_tab === 'evaluated' ? 'bg-[#1D2026] text-foreground' : 'text-muted-foreground hover:text-foreground'; ?>">
                Evaluadas (<?php echo $total_evaluated; ?>)
            </a>
            <a href="teacher_simulator.php?tab=events" class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex-shrink-0 <?php echo $active_tab === 'events' ? 'bg-[#1D2026] text-foreground' : 'text-muted-foreground hover:text-foreground'; ?>">
                Eventos (<?php echo $total_events; ?>)
            </a>
        </div>

        <!-- Main Tab Content Card -->
        <div class="bg-card border border-border/50 rounded-xl p-4 sm:p-6 min-h-[300px] flex flex-col justify-center">
            
            <!-- PENDENT PARTICIPATIONS TAB -->
            <?php if ($active_tab === 'pending'): ?>
                <?php if (empty($pending_parts)): ?>
                    <div class="flex flex-col items-center justify-center py-16 space-y-4">
                        <i data-lucide="check" class="h-16 w-16 text-[#39FF14] stroke-[1.5]"></i>
                        <p class="text-base text-muted-foreground font-medium text-center">No hay participaciones pendientes de evaluacion</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 w-full self-start">
                        <?php foreach ($pending_parts as $part): ?>
                            <div class="bg-[#15181C]/50 border border-border/30 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:border-primary/20 transition-all w-full max-w-full">
                                <div class="flex gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="radio" class="h-6 w-6"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($part['event_name']); ?></h4>
                                        <p class="text-xs text-muted-foreground mt-0.5 truncate">Alumno: <?php echo htmlspecialchars($part['student_name']); ?> • Dificultad: <?php echo htmlspecialchars($part['difficulty']); ?></p>
                                        <p class="text-3xs text-muted-foreground mt-1">Presentado el <?php echo date('d/m/Y H:i', strtotime($part['submitted_at'])); ?></p>
                                    </div>
                                </div>
                                <a href="teacher_simulator.php?tab=pending&participation_id=<?php echo $part['id']; ?>" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-all text-center self-end sm:self-auto flex-shrink-0">
                                    Evaluar Setlist
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- EVALUATED PARTICIPATIONS TAB -->
            <?php elseif ($active_tab === 'evaluated'): ?>
                <?php if (empty($evaluated_parts)): ?>
                    <div class="flex flex-col items-center justify-center py-16 space-y-3 text-muted-foreground">
                        <i data-lucide="radio" class="h-12 w-12 opacity-35 mb-2"></i>
                        <p class="text-sm">No hay participaciones evaluadas aún.</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 w-full self-start">
                        <?php foreach ($evaluated_parts as $part): ?>
                            <div class="bg-[#15181C]/50 border border-border/30 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-6 w-full max-w-full">
                                <div class="flex gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 bg-success/10 text-success rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="check-circle" class="h-6 w-6"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($part['event_name']); ?></h4>
                                        <p class="text-xs text-muted-foreground mt-0.5 truncate">Alumno: <?php echo htmlspecialchars($part['student_name']); ?> • Evaluada el <?php echo date('d/m/Y H:i', strtotime($part['evaluated_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-1.5 bg-[#39FF14]/10 text-[#39FF14] border border-[#39FF14]/20 px-3 py-1.5 rounded-lg text-xs font-bold self-end sm:self-auto flex-shrink-0">
                                    <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                                    <span>Nota: <?php echo round($part['total_score'], 1); ?>/10</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- ALL EVENTS TAB -->
            <?php else: ?>
                <?php if (empty($events)): ?>
                    <div class="flex flex-col items-center justify-center py-16 space-y-3 text-muted-foreground">
                        <i data-lucide="globe" class="h-12 w-12 opacity-35 mb-2"></i>
                        <p class="text-sm">No hay eventos creados en el simulador.</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4 w-full self-start">
                        <?php foreach ($events as $ev): 
                            $rep_tier = get_reputation_tier($ev['required_reputation']);
                        ?>
                            <div class="bg-[#15181C]/50 border border-border/30 rounded-xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-primary/20 transition-all w-full max-w-full">
                                <div class="space-y-2 flex-1 min-w-0 w-full max-w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-sm text-foreground leading-tight"><?php echo htmlspecialchars($ev['name']); ?></h4>
                                        <span class="text-3xs font-semibold px-2 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded-full">
                                            <?php echo htmlspecialchars($ev['venue_type']); ?>
                                        </span>
                                        <span class="text-3xs font-semibold px-2 py-0.5 bg-muted border border-border text-muted-foreground rounded-full">
                                            <?php echo htmlspecialchars($ev['difficulty']); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground leading-relaxed truncate max-w-xl"><?php echo htmlspecialchars($ev['description']); ?></p>
                                    
                                    <div class="flex flex-wrap gap-2 sm:gap-4 text-[10px] text-muted-foreground pt-1">
                                        <span class="truncate max-w-full">Estilos: <strong class="text-foreground"><?php echo htmlspecialchars($ev['music_styles']); ?></strong></span>
                                        <span class="hidden sm:inline">•</span>
                                        <span class="truncate max-w-full">Rep. Requerida: <strong style="color: <?php echo $rep_tier['color']; ?>;"><?php echo $ev['required_reputation']; ?> (<?php echo $rep_tier['name']; ?>)</strong></span>
                                        <span class="hidden sm:inline">•</span>
                                        <span class="truncate max-w-full">Pago Base: <strong class="text-foreground">$<?php echo $ev['payment']; ?></strong></span>
                                    </div>
                                </div>
                                <div class="text-right text-xs text-muted-foreground leading-none self-end md:self-auto border-t md:border-t-0 border-border/30 pt-3 md:pt-0 w-full md:w-auto flex md:flex-col justify-between md:justify-end gap-1 flex-shrink-0">
                                    <span>Postulaciones:</span>
                                    <strong class="text-foreground text-sm font-bold mt-1"><?php echo $ev['submissions_count']; ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ================= MODALS OVERLAYS ================= -->

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

<!-- 1. NUEVO EVENTO MODAL -->
<?php if ($show_create_modal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4">
        <div class="w-full max-w-[620px] bg-[#15181C] border border-[#2D3139] rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 sm:px-8 py-4 sm:py-5 border-b border-[#2D3139]/40 flex-shrink-0">
                <h3 class="text-base font-extrabold text-foreground">Crear Evento para Simulador</h3>
                <a href="teacher_simulator.php" class="text-muted-foreground hover:text-foreground p-1 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </a>
            </div>

            <!-- Content scrollable -->
            <div class="px-4 sm:px-8 pb-4 sm:pb-8 pt-3 sm:pt-4 overflow-y-auto space-y-5 flex-1">
                <form action="../Backend/scripts/actions.php" method="POST" class="space-y-5">
                    
                    <div class="space-y-1.5">
                        <input type="hidden" name="action" value="create_event">
                        <label for="name" class="text-xs font-semibold text-foreground">Nombre del Evento / Locación</label>
                        <input id="name" name="name" required placeholder="Ej: Club Subterráneo - Set Apertura" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>

                    <div class="space-y-1.5">
                        <label for="description" class="text-xs font-semibold text-foreground">Descripción y Expectativa del Público</label>
                        <textarea id="description" name="description" rows="3" required placeholder="Describe lo que se espera..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="type" class="text-xs font-semibold text-foreground">Tipo de Locación</label>
                            <div class="relative custom-select-wrapper" id="type_wrapper">
                                <select id="type" name="type" class="hidden">
                                    <option value="club" selected>Club / Discoteca</option>
                                    <option value="festival">Festival Escenario Principal</option>
                                    <option value="radio">Radio Show Set</option>
                                    <option value="private">Evento Privado / Terraza</option>
                                    <option value="bar">Bar / Pub</option>
                                </select>
                                <div class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground cursor-pointer flex justify-between items-center transition-colors hover:border-primary/50" onclick="toggleCustomDropdown('type_list')">
                                    <span id="type_display" class="truncate pointer-events-none">Club / Discoteca</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 opacity-50 pointer-events-none"></i>
                                </div>
                                <div id="type_list" class="absolute top-full left-0 w-full mt-1.5 bg-[#1D2026] border border-border rounded-lg shadow-2xl z-50 hidden overflow-hidden py-1 max-h-60 overflow-y-auto">
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('type', 'club', 'Club / Discoteca')">Club / Discoteca</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('type', 'festival', 'Festival Escenario Principal')">Festival Escenario Principal</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('type', 'radio', 'Radio Show Set')">Radio Show Set</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('type', 'private', 'Evento Privado / Terraza')">Evento Privado / Terraza</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('type', 'bar', 'Bar / Pub')">Bar / Pub</div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label for="difficulty" class="text-xs font-semibold text-foreground">Dificultad</label>
                            <div class="relative custom-select-wrapper" id="difficulty_wrapper">
                                <select id="difficulty" name="difficulty" class="hidden">
                                    <option value="Fácil">Fácil</option>
                                    <option value="Medio" selected>Medio</option>
                                    <option value="Difícil">Difícil</option>
                                </select>
                                <div class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground cursor-pointer flex justify-between items-center transition-colors hover:border-primary/50" onclick="toggleCustomDropdown('difficulty_list')">
                                    <span id="difficulty_display" class="truncate pointer-events-none">Medio</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 opacity-50 pointer-events-none"></i>
                                </div>
                                <div id="difficulty_list" class="absolute top-full left-0 w-full mt-1.5 bg-[#1D2026] border border-border rounded-lg shadow-2xl z-50 hidden overflow-hidden py-1">
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('difficulty', 'Fácil', 'Fácil')">Fácil</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('difficulty', 'Medio', 'Medio')">Medio</div>
                                    <div class="px-3 py-2 text-sm text-foreground hover:bg-muted hover:text-primary font-medium cursor-pointer transition-colors" onclick="selectCustomOption('difficulty', 'Difícil', 'Difícil')">Difícil</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Reputación Requerida slider -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-end">
                                <label class="text-xs font-semibold text-foreground">Reputación Requerida (Puntos)</label>
                                <span id="rep-val" class="text-xs font-bold text-primary">0</span>
                            </div>
                            <input type="range" id="rep-slider" name="required_reputation" min="0" max="100" value="0" class="modal-slider mt-2" oninput="updateCreateEventSliders()">
                            <div class="flex justify-between text-[10px] text-muted-foreground px-1 mt-1 leading-none">
                                <span>0</span>
                                <span>50</span>
                                <span>100</span>
                            </div>
                        </div>
                        
                        <!-- Pago Base slider -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-end">
                                <label class="text-xs font-semibold text-foreground">Pago Base (Créditos)</label>
                                <span id="pay-val" class="text-xs font-bold text-primary">150</span>
                            </div>
                            <input type="range" id="pay-slider" name="payment" min="50" max="1000" step="10" value="150" class="modal-slider mt-2" oninput="updateCreateEventSliders()">
                            <div class="flex justify-between text-[10px] text-muted-foreground px-1 mt-1 leading-none">
                                <span>50</span>
                                <span>500</span>
                                <span>1000</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-0.5">
                            <label for="music_styles" class="text-xs font-semibold text-foreground">Géneros Musicales Permitidos</label>
                            <span class="text-[10px] text-muted-foreground">Separados por comas</span>
                        </div>
                        <input id="music_styles" name="music_styles" required placeholder="Ej: Tech House, House, Minimal" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>

                    <!-- Footer actions -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-4 border-t border-[#2D3139]/40 flex-shrink-0">
                        <a href="teacher_simulator.php" class="bg-transparent hover:bg-muted/10 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors flex items-center justify-center w-full sm:w-auto">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-[#00F2FF] text-[#0F1115] font-bold px-4 py-2 rounded-lg text-xs hover:opacity-90 transition-colors flex items-center justify-center gap-1.5 w-full sm:w-auto">
                            Publicar Evento
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
            <div class="flex items-center justify-between px-4 sm:px-8 py-4 sm:py-5 border-b border-[#2D3139]/40 flex-shrink-0">
                <h3 class="text-base font-extrabold text-foreground truncate">Evaluar Evento: <?php echo htmlspecialchars($selected_part['event_name']); ?></h3>
                <a href="teacher_simulator.php?tab=pending" class="text-muted-foreground hover:text-foreground p-1 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </a>
            </div>

            <!-- Content Area - 2 Column Split inside scrollable modal -->
            <div class="p-8 overflow-y-auto flex-1 select-none space-y-6">
                <div class="grid md:grid-cols-2 gap-8">
                    
                    <!-- Left: Submission details -->
                    <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                        <!-- Student details -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-3 flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($selected_part['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted flex-shrink-0">
                            <div class="min-w-0">
                                <h4 class="font-bold text-sm text-foreground truncate"><?php echo htmlspecialchars($selected_part['student_name']); ?></h4>
                                <p class="text-xs text-muted-foreground leading-none mt-0.5">Presentado el <?php echo date('d/m/Y H:i', strtotime($selected_part['submitted_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-3 text-xs">
                            <p class="text-muted-foreground leading-none text-xs">
                                Estilos: <strong class="text-foreground"><?php echo htmlspecialchars($selected_part['music_styles']); ?></strong>
                            </p>
                        </div>

                        <!-- Setlist Link -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-3 space-y-2">
                            <h5 class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                <i data-lucide="link" class="h-3.5 w-3.5 text-primary"></i>
                                Enlace del Set
                            </h5>
                            <div class="p-2 rounded-lg bg-muted/20 border border-border/30 text-center">
                                <a href="<?php echo htmlspecialchars($selected_part['set_url']); ?>" target="_blank" class="inline-flex items-center gap-2 text-primary hover:text-primary/80 transition-colors font-semibold text-xs max-w-full">
                                    <i data-lucide="external-link" class="h-3.5 w-3.5 flex-shrink-0"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($selected_part['set_url']); ?></span>
                                </a>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div class="bg-muted/10 border border-border/30 rounded-xl p-3 space-y-1.5">
                            <h5 class="text-xs font-bold text-foreground">Justificación</h5>
                            <p class="text-xs text-muted-foreground leading-relaxed bg-black/10 p-2.5 rounded border border-border/30 max-h-[120px] overflow-y-auto">
                                <?php echo nl2br(htmlspecialchars($selected_part['justification'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Right: Evaluation Form -->
                    <form action="../Backend/scripts/actions.php" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="evaluate_event">
                        <!-- Redirect back to simulator page after evaluated -->
                        <input type="hidden" name="redirect_target" value="teacher_simulator">
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
                            <textarea id="feedback" name="feedback" required rows="3" placeholder="Comenta la progresión de BPMs, transiciones armónicas sugeridas..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                        </div>

                        <!-- Actions Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2D3139]/40 flex-shrink-0">
                            <a href="teacher_simulator.php?tab=pending" class="bg-transparent hover:bg-muted/10 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors flex items-center justify-center w-full sm:w-auto">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-[#00F2FF] text-[#0F1115] font-bold px-4 py-2 rounded-lg text-xs hover:opacity-90 transition-colors flex items-center justify-center gap-1.5 shadow-lg shadow-primary/10">
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


    function toggleCustomDropdown(listId) {
        // Close others first
        document.querySelectorAll('[id$="_list"]').forEach(el => {
            if (el.id !== listId) el.classList.add('hidden');
        });
        const list = document.getElementById(listId);
        if (list) {
            list.classList.toggle('hidden');
        }
    }

    function selectCustomOption(selectId, value, text) {
        const select = document.getElementById(selectId);
        if (select) {
            select.value = value;
        }
        const display = document.getElementById(selectId + '_display');
        if (display) {
            display.innerText = text;
        }
        document.getElementById(selectId + '_list').classList.add('hidden');
    }

    // Close custom dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('[id$="_list"]').forEach(el => el.classList.add('hidden'));
        }
    });

    function updateCreateEventSliders() {
    const repSlider = document.getElementById('rep-slider');
    const paySlider = document.getElementById('pay-slider');
    
    if (repSlider) {
        updateSliderBackground(repSlider);
        document.getElementById('rep-val').innerText = repSlider.value;
    }
    
    if (paySlider) {
        updateSliderBackground(paySlider);
        document.getElementById('pay-val').innerText = paySlider.value;
    }
}

// Perform initial execution of sliders styling when loaded
document.addEventListener('DOMContentLoaded', () => {
    updateEventScores();
    updateCreateEventSliders();
});
</script>

<?php
render_footer();
?>
