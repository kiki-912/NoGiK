<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('teacher');
$teacher = get_current_user_details();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch pending class join requests for this teacher's classes
$stmt_classes = $pdo->prepare("
    SELECT ca.class_id, ca.student_id, c.title as class_title, u.name as student_name, u.avatar as student_avatar, u.email as student_email, c.class_date
    FROM class_attendees ca
    JOIN classes c ON ca.class_id = c.id
    JOIN users u ON ca.student_id = u.id
    WHERE ca.status = 'pending' AND c.teacher_id = ?
    ORDER BY c.class_date ASC
");
$stmt_classes->execute([$teacher['id']]);
$pending_classes = $stmt_classes->fetchAll();

// Fetch pending event participations (any student submissions to simulated events)
$stmt_events = $pdo->prepare("
    SELECT ep.id as participation_id, ep.event_id, ep.submitted_at, se.name as event_name, u.name as student_name, u.avatar as student_avatar
    FROM event_participations ep
    JOIN simulated_events se ON ep.event_id = se.id
    JOIN users u ON ep.student_id = u.id
    WHERE ep.status = 'pending'
    ORDER BY ep.submitted_at DESC
");
$stmt_events->execute();
$pending_events = $stmt_events->fetchAll();

render_header("Notificaciones - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground flex items-center gap-2">
                <i data-lucide="bell" class="h-6 w-6 text-primary"></i>
                Notificaciones y Solicitudes
            </h1>
            <p class="text-sm text-muted-foreground">Gestiona las solicitudes de tus alumnos y las participaciones pendientes</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'approved'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Solicitud aprobada correctamente! El alumno ya está inscrito.
            </div>
        <?php elseif ($success === 'rejected'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                La solicitud de inscripción ha sido rechazada.
            </div>
        <?php elseif ($error === 'invalid'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Datos inválidos. No se pudo procesar la solicitud.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left: Class Join Requests -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border/30 pb-2">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="calendar" class="h-5 w-5 text-primary"></i>
                        Inscripciones a Clases (<?php echo count($pending_classes); ?>)
                    </h3>
                </div>

                <?php if (empty($pending_classes)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-8 text-center text-muted-foreground">
                        <i data-lucide="check-circle-2" class="h-10 w-10 mx-auto opacity-30 mb-2 text-success"></i>
                        <p class="text-sm font-medium">No tienes solicitudes pendientes de tus clases</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($pending_classes as $pc): 
                            $class_date = new DateTime($pc['class_date']);
                        ?>
                            <div class="bg-card border border-border/50 rounded-xl p-4 space-y-4 hover:border-primary/20 transition-all flex flex-col justify-between">
                                <div class="flex items-start gap-3">
                                    <img src="<?php echo htmlspecialchars($pc['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted flex-shrink-0">
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <p class="text-xs text-foreground">
                                            <span class="font-bold text-foreground"><?php echo htmlspecialchars($pc['student_name']); ?></span> 
                                            (<span class="text-muted-foreground"><?php echo htmlspecialchars($pc['student_email']); ?></span>)
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Solicita unirse a la clase: 
                                            <strong class="text-foreground"><?php echo htmlspecialchars($pc['class_title']); ?></strong>
                                        </p>
                                        <p class="text-3xs text-muted-foreground flex items-center gap-1">
                                            <i data-lucide="clock" class="h-3 w-3"></i>
                                            Fecha de la clase: <?php echo $class_date->format('d/m/Y H:i'); ?> hs
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border/30">
                                    <!-- Reject Form -->
                                    <form action="../Backend/scripts/actions.php" method="POST" class="inline">
                                        <input type="hidden" name="action" value="reject_class_join">
                                        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($pc['class_id']); ?>">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($pc['student_id']); ?>">
                                        <button type="submit" class="bg-destructive/10 text-destructive border border-destructive/20 font-semibold px-3 py-1.5 rounded-lg text-xs hover:bg-destructive/20 transition-colors">
                                            Rechazar
                                        </button>
                                    </form>
                                    <!-- Approve Form -->
                                    <form action="../Backend/scripts/actions.php" method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve_class_join">
                                        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($pc['class_id']); ?>">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($pc['student_id']); ?>">
                                        <button type="submit" class="bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded-lg text-xs hover:bg-primary/90 transition-colors">
                                            Autorizar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Event Participations -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border/30 pb-2">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="radio" class="h-5 w-5 text-secondary"></i>
                        Participación en Eventos (<?php echo count($pending_events); ?>)
                    </h3>
                </div>

                <?php if (empty($pending_events)): ?>
                    <div class="bg-card border border-border/50 rounded-xl p-8 text-center text-muted-foreground">
                        <i data-lucide="check-circle-2" class="h-10 w-10 mx-auto opacity-30 mb-2 text-success"></i>
                        <p class="text-sm font-medium">No hay participaciones de eventos pendientes</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($pending_events as $pe): 
                            $submitted_date = new DateTime($pe['submitted_at']);
                        ?>
                            <div class="bg-card border border-border/50 rounded-xl p-4 space-y-4 hover:border-secondary/20 transition-all flex flex-col justify-between">
                                <div class="flex items-start gap-3">
                                    <img src="<?php echo htmlspecialchars($pe['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted flex-shrink-0">
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <p class="text-xs text-foreground">
                                            El alumno <span class="font-bold"><?php echo htmlspecialchars($pe['student_name']); ?></span> 
                                            envió una propuesta para el evento: 
                                            <strong class="text-foreground"><?php echo htmlspecialchars($pe['event_name']); ?></strong>
                                        </p>
                                        <p class="text-3xs text-muted-foreground flex items-center gap-1">
                                            <i data-lucide="calendar" class="h-3 w-3"></i>
                                            Enviado el <?php echo $submitted_date->format('d/m/Y H:i'); ?> hs
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-2 border-t border-border/30">
                                    <a href="teacher_evaluations.php?participation_id=<?php echo $pe['participation_id']; ?>" class="inline-flex items-center gap-1 bg-secondary text-secondary-foreground font-semibold px-3 py-1.5 rounded-lg text-xs hover:bg-secondary/90 transition-colors">
                                        <span>Evaluar y Aprobar</span>
                                        <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
render_footer();
?>
