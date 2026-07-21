<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

$id = $_GET['id'] ?? '';
$upload = isset($_GET['upload']) || isset($_GET['action']) && $_GET['action'] === 'upload';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Genre list for form
$genres = ['Tech House', 'Techno', 'House', 'Deep House', 'Minimal', 'Disco', 'Melodic Techno', 'Progressive House', 'Trap', 'Hip Hop', 'Drum & Bass'];

// DETAIL VIEW
if (!empty($id)) {
    // Fetch set
    $stmt_set = $pdo->prepare("SELECT s.*, se.technique, se.coherence, se.creativity, se.adaptation, se.overall_score, se.feedback, se.evaluated_at, se.xp_awarded, se.reputation_change, u.name as teacher_name FROM dj_sets s LEFT JOIN set_evaluations se ON s.id = se.set_id LEFT JOIN users u ON se.teacher_id = u.id WHERE s.id = ? AND s.student_id = ?");
    $stmt_set->execute([$id, $student['id']]);
    $set = $stmt_set->fetch();
    
    if (!$set) {
        header("Location: student_sets.php");
        exit();
    }
    
    // Fetch comments
    $stmt_comm = $pdo->prepare("SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.role as user_role FROM comments c JOIN users u ON c.user_id = u.id WHERE c.set_id = ? ORDER BY c.created_at ASC");
    $stmt_comm->execute([$id]);
    $comments = $stmt_comm->fetchAll();
    
    render_header("Detalle de Set - NoGiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground "><?php echo htmlspecialchars($set['title']); ?></h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Detalles y feedback de evaluación</p>
            </div>
        </div>
            <a href="student_sets.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            
        </header>

        <!-- Content -->
        <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
            
            <?php if ($success === 'commented'): ?>
                <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                    ¡Comentario añadido correctamente!
                </div>
            <?php endif; ?>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left Details (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Set Overview -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <div class="flex gap-5">
                            <div class="w-20 h-20 bg-gradient-to-br from-primary/20 to-secondary/20 rounded-xl flex items-center justify-center flex-shrink-0 text-primary">
                                <i data-lucide="play" class="h-10 w-10"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="px-2 py-0.5 bg-primary/10 border border-primary/20 text-primary text-xs font-semibold rounded">
                                    <?php echo htmlspecialchars($set['genre']); ?>
                                </span>
                                <h3 class="text-xl font-bold text-foreground mt-2 truncate"><?php echo htmlspecialchars($set['title']); ?></h3>
                                <p class="text-xs text-muted-foreground mt-1">Subido el <?php echo date('d/m/Y H:i', strtotime($set['uploaded_at'])); ?></p>
                            </div>
                        </div>
                        
                        <p class="text-sm text-muted-foreground bg-muted/20 p-4 rounded-lg border border-border/30">
                            <?php echo nl2br(htmlspecialchars($set['description'])); ?>
                        </p>
                        
                        <div class="flex flex-wrap gap-4 text-xs text-muted-foreground pt-2">
                            <span class="flex items-center gap-1">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                                <?php echo $set['duration']; ?> minutos
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="message-square" class="h-4 w-4"></i>
                                <?php echo count($comments); ?> comentarios
                            </span>
                        </div>
                        
                        <div class="pt-2">
                            <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="inline-flex items-center gap-2 bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-sm hover:bg-primary/95 transition-colors">
                                <i data-lucide="external-link" class="h-4 w-4"></i>
                                Escuchar Set Completo
                            </a>
                        </div>
                    </div>

                    <!-- Comments Thread -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                        <h4 class="font-bold text-base text-foreground flex items-center gap-2">
                            <i data-lucide="message-square" class="h-5 w-5 text-primary"></i>
                            Comentarios (<?php echo count($comments); ?>)
                        </h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($comments as $comment): 
                                $is_teacher_comm = ($comment['user_role'] === 'teacher');
                                $bg_class = $is_teacher_comm ? 'bg-primary/5 border border-primary/20' : 'bg-muted/10 border border-border/30';
                            ?>
                                <div class="p-4 rounded-lg <?php echo $bg_class; ?> space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <img src="<?php echo htmlspecialchars($comment['user_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-6 h-6 rounded-full bg-muted">
                                            <span class="text-xs font-semibold text-foreground">
                                                <?php echo htmlspecialchars($comment['user_name']); ?>
                                            </span>
                                            <?php if ($is_teacher_comm): ?>
                                                <span class="text-3xs font-semibold px-1.5 py-0.5 bg-primary/20 text-primary rounded border border-primary/30 uppercase">Profesor</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-3xs text-muted-foreground"><?php echo date('d M, H:i', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Comment form -->
                            <form action="../Backend/scripts/actions.php" method="POST" class="pt-4 border-t border-border/30 space-y-3">
                                <input type="hidden" name="action" value="submit_comment">
                                <input type="hidden" name="set_id" value="<?php echo $set['id']; ?>">
                                
                                <textarea name="content" required placeholder="Escribe un comentario..." rows="3" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                                
                                <button type="submit" class="bg-muted text-foreground hover:bg-muted/80 font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                                    Comentar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Evaluation (1 Col) -->
                <div class="space-y-6">
                    <?php if ($set['overall_score']): ?>
                        <!-- Score Display -->
                        <div class="bg-card border border-[#39FF14]/30 rounded-xl p-6 text-center space-y-4">
                            <div class="inline-flex p-3 bg-[#39FF14]/10 text-[#39FF14] rounded-full">
                                <i data-lucide="star" class="h-8 w-8 fill-current"></i>
                            </div>
                            <div>
                                <p class="text-2xs text-muted-foreground uppercase font-bold tracking-wider">Nota de Evaluación</p>
                                <h3 class="text-4xl font-extrabold text-[#39FF14] mt-1"><?php echo round($set['overall_score'], 2); ?></h3>
                                <p class="text-xs text-muted-foreground mt-1">Evaluado por <?php echo htmlspecialchars($set['teacher_name']); ?></p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 pt-2 text-xs">
                                <div class="bg-muted/40 p-2.5 rounded-lg border border-border/30">
                                    <p class="text-muted-foreground">Técnica</p>
                                    <p class="text-base font-bold text-foreground mt-0.5"><?php echo $set['technique']; ?></p>
                                </div>
                                <div class="bg-muted/40 p-2.5 rounded-lg border border-border/30">
                                    <p class="text-muted-foreground">Coherencia</p>
                                    <p class="text-base font-bold text-foreground mt-0.5"><?php echo $set['coherence']; ?></p>
                                </div>
                                <div class="bg-muted/40 p-2.5 rounded-lg border border-border/30">
                                    <p class="text-muted-foreground">Creatividad</p>
                                    <p class="text-base font-bold text-foreground mt-0.5"><?php echo $set['creativity']; ?></p>
                                </div>
                                <div class="bg-muted/40 p-2.5 rounded-lg border border-border/30">
                                    <p class="text-muted-foreground">Adaptación</p>
                                    <p class="text-base font-bold text-foreground mt-0.5"><?php echo $set['adaptation']; ?></p>
                                </div>
                            </div>
                            
                            <div class="flex gap-2 pt-2">
                                <span class="flex-1 text-center py-1.5 bg-primary/10 border border-primary/20 text-primary text-xs font-semibold rounded-lg">
                                    +<?php echo $set['xp_awarded']; ?> XP
                                </span>
                                <span class="flex-1 text-center py-1.5 bg-[#39FF14]/10 border border-[#39FF14]/20 text-[#39FF14] text-xs font-semibold rounded-lg">
                                    +<?php echo $set['reputation_change']; ?> Rep
                                </span>
                            </div>
                        </div>

                        <!-- Feedback Card -->
                        <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                            <h4 class="font-bold text-sm text-foreground flex items-center gap-2">
                                <i data-lucide="clipboard-list" class="h-4.5 w-4.5 text-primary"></i>
                                Feedback del Profesor
                            </h4>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap leading-relaxed">
                                <?php echo htmlspecialchars($set['feedback']); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Pending card -->
                        <div class="bg-card border border-border/50 rounded-xl p-6 text-center space-y-4 py-12">
                            <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center text-muted-foreground mx-auto">
                                <i data-lucide="clock" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-base text-foreground">Evaluación Pendiente</h4>
                                <p class="text-xs text-muted-foreground mt-1 max-w-[200px] mx-auto">
                                    Tu set ha sido enviado. Los profesores serán notificados para evaluar tu mezcla pronto.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php
    render_footer();
    exit();
}

// UPLOAD FORM VIEW
if ($upload) {
    render_header("Subir Set - NoGiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">Subir Set</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Comparte tu mezcla para recibir feedback de los profesores</p>
            </div>
        </div>
            <a href="student_sets.php" class="p-1.5 hover:bg-muted/30 rounded-lg text-muted-foreground hover:text-foreground">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
            </a>
            
        </header>

        <!-- Content -->
        <div class="p-4 sm:p-6 max-w-2xl mx-auto space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
            <form action="../Backend/scripts/actions.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="upload_set">
                
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="music" class="h-5 w-5 text-primary"></i>
                        Información del Set
                    </h3>
                    
                    <div class="space-y-1.5">
                        <label for="title" class="text-sm font-medium text-foreground">Título del Set</label>
                        <input id="title" name="title" required placeholder="Ej: Tech House Journey Vol. 1" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                    </div>

                    <div class="space-y-1.5">
                        <label for="description" class="text-sm font-medium text-foreground">Descripción</label>
                        <textarea id="description" name="description" rows="4" placeholder="Describe tu set, el contexto, las técnicas de mezcla utilizadas..." class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground resize-none focus:ring-1 focus:ring-primary focus:outline-none"></textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-foreground">Género</label>
                            <div class="relative custom-select">
                                <input type="hidden" id="genre" name="genre" required>
                                <button type="button" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none flex justify-between items-center transition-colors hover:border-primary/50 select-button">
                                    <span class="select-text text-muted-foreground truncate">Selecciona un género</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 opacity-50 flex-shrink-0"></i>
                                </button>
                                <!-- Dropdown menu -->
                                <div class="absolute z-50 w-full mt-1 bg-popover border border-border rounded-lg shadow-xl overflow-hidden opacity-0 invisible transform -translate-y-2 transition-all duration-200 select-menu">
                                    <div class="max-h-60 overflow-y-auto py-1">
                                        <?php foreach ($genres as $g): ?>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors select-option" data-value="<?php echo $g; ?>">
                                                <?php echo $g; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label for="duration" class="text-sm font-medium text-foreground">Duración (minutos)</label>
                            <input id="duration" name="duration" type="number" min="5" max="300" value="60" required class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2">
                        <i data-lucide="link" class="h-5 w-5 text-primary"></i>
                        Enlace de SoundCloud / Mixcloud / Drive
                    </h3>
                    
                    <div class="space-y-1.5">
                        <label for="url" class="text-sm font-medium text-foreground">URL del Set</label>
                        <input id="url" name="url" type="url" required placeholder="https://soundcloud.com/tu-usuario/tu-set" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                        <p class="text-3xs text-muted-foreground">Sube tu set a cualquier plataforma pública y pega el enlace aquí.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="student_sets.php" class="bg-card hover:bg-muted/40 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-primary text-primary-foreground font-semibold px-4 py-2 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                        Subir Set
                    </button>
                </div>
            </form>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Custom Select Logic
        const customSelects = document.querySelectorAll('.custom-select');
        
        customSelects.forEach(selectContainer => {
            const button = selectContainer.querySelector('.select-button');
            const menu = selectContainer.querySelector('.select-menu');
            const textSpan = selectContainer.querySelector('.select-text');
            const hiddenInput = selectContainer.querySelector('input[type="hidden"]');
            const options = selectContainer.querySelectorAll('.select-option');
            
            // Toggle menu
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other open menus
                document.querySelectorAll('.custom-select .select-menu').forEach(otherMenu => {
                    if (otherMenu !== menu) {
                        closeMenu(otherMenu);
                    }
                });
                
                const isOpen = !menu.classList.contains('invisible');
                if (isOpen) {
                    closeMenu(menu);
                } else {
                    openMenu(menu);
                    button.classList.add('border-primary/50', 'ring-1', 'ring-primary');
                }
            });
            
            // Select option
            options.forEach(option => {
                option.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const value = option.getAttribute('data-value');
                    const text = option.innerText.trim();
                    
                    hiddenInput.value = value;
                    textSpan.innerText = text;
                    textSpan.classList.remove('text-muted-foreground');
                    textSpan.classList.add('text-foreground');
                    
                    closeMenu(menu);
                });
            });
        });
        
        // Close menus when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select .select-menu').forEach(menu => {
                closeMenu(menu);
            });
        });
        
        function openMenu(menu) {
            menu.classList.remove('invisible', 'opacity-0', '-translate-y-2');
            menu.classList.add('opacity-100', 'translate-y-0');
        }
        
        function closeMenu(menu) {
            menu.classList.remove('opacity-100', 'translate-y-0');
            menu.classList.add('opacity-0', '-translate-y-2');
            
            // Delay invisible to allow animation to finish
            setTimeout(() => {
                if (menu.classList.contains('opacity-0')) {
                    menu.classList.add('invisible');
                }
            }, 200);
            
            const button = menu.closest('.custom-select').querySelector('.select-button');
            button.classList.remove('border-primary/50', 'ring-1', 'ring-primary');
        }
    });
</script>
    <?php
    render_footer();
    exit();
}

// DEFAULT LIST VIEW
// Fetch sets
$stmt = $pdo->prepare("SELECT s.*, se.overall_score FROM dj_sets s LEFT JOIN set_evaluations se ON s.id = se.set_id WHERE s.student_id = ? ORDER BY s.uploaded_at DESC");
$stmt->execute([$student['id']]);
$sets = $stmt->fetchAll();

$evaluated = array_filter($sets, function($s) { return !is_null($s['overall_score']); });
$pending = array_filter($sets, function($s) { return is_null($s['overall_score']); });

$active_tab = $_GET['tab'] ?? 'all';
$filtered_sets = $sets;
if ($active_tab === 'evaluated') $filtered_sets = $evaluated;
if ($active_tab === 'pending') $filtered_sets = $pending;

render_header("Mis Sets - NoGiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">Mis Sets</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate"><?php echo count($sets); ?> sets subidos, <?php echo count($evaluated); ?> evaluados</p>
            </div>
        </div>
        
        <a href="student_sets.php?upload=1" class="inline-flex items-center gap-2 bg-primary text-primary-foreground font-semibold px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs sm:text-sm hover:bg-primary/90 transition-colors shrink-0">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Subir Set
        </a>
    </header>

    <!-- Content -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <?php if ($success === 'uploaded'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Set subido correctamente! Listo para ser evaluado por los profesores.
            </div>
        <?php elseif ($error === 'empty'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Por favor, completa todos los campos del set.
            </div>
        <?php endif; ?>

        <!-- Tabs filter -->
        <div class="space-y-6">
            <div class="flex border-b border-border gap-2">
                <a href="student_sets.php?tab=all" class="px-4 py-2 text-sm font-semibold border-b-2 <?php echo $active_tab === 'all' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Todos (<?php echo count($sets); ?>)
                </a>
                <a href="student_sets.php?tab=evaluated" class="px-4 py-2 text-sm font-semibold border-b-2 <?php echo $active_tab === 'evaluated' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Evaluados (<?php echo count($evaluated); ?>)
                </a>
                <a href="student_sets.php?tab=pending" class="px-4 py-2 text-sm font-semibold border-b-2 <?php echo $active_tab === 'pending' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Pendientes (<?php echo count($pending); ?>)
                </a>
            </div>

            <!-- Sets Grid -->
            <?php if (empty($filtered_sets)): ?>
                <div class="bg-card border border-border/50 rounded-xl p-12 text-center text-muted-foreground">
                    <i data-lucide="music-4" class="h-12 w-12 mx-auto opacity-30 mb-3"></i>
                    <p class="text-base">No se encontraron sets en esta sección</p>
                </div>
            <?php else: ?>
                <div class="grid gap-4">
                    <?php foreach ($filtered_sets as $set): ?>
                        <div class="bg-card border border-border/50 rounded-xl p-6 flex flex-col sm:flex-row sm:items-start justify-between gap-6 hover:border-primary/20 transition-all">
                            <div class="flex gap-4 min-w-0">
                                <div class="w-16 h-16 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-xl flex items-center justify-center text-primary flex-shrink-0">
                                    <i data-lucide="play" class="h-7 w-7"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-base text-foreground truncate"><?php echo htmlspecialchars($set['title']); ?></h4>
                                    <p class="text-sm text-muted-foreground mt-1 line-clamp-1"><?php echo htmlspecialchars($set['description']); ?></p>
                                    
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground pt-2">
                                        <span class="px-1.5 py-0.5 bg-muted rounded text-2xs"><?php echo htmlspecialchars($set['genre']); ?></span>
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                            <?php echo $set['duration']; ?> min
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                            <?php echo date('d/m/Y', strtotime($set['uploaded_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-end gap-3 flex-shrink-0">
                                <?php if ($set['overall_score']): ?>
                                    <div class="flex items-center gap-1 text-[#39FF14] text-sm font-bold bg-[#39FF14]/10 px-2 py-1 rounded">
                                        <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                                        <span><?php echo round($set['overall_score'], 1); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-muted-foreground px-2 py-0.5 bg-muted rounded-full">Pendiente</span>
                                <?php endif; ?>
                                
                                <div class="flex gap-2">
                                    <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="p-1.5 hover:bg-muted/30 border border-border rounded text-muted-foreground hover:text-primary transition-all">
                                        <i data-lucide="external-link" class="h-4 w-4"></i>
                                    </a>
                                    <a href="student_sets.php?id=<?php echo $set['id']; ?>" class="bg-muted text-foreground hover:bg-muted/80 text-xs px-3 py-1.5 rounded transition-all">
                                        Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>




<?php
render_footer();
?>
