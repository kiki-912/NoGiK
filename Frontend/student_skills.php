<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

// Fetch student's skills
$stmt_skills = $pdo->prepare("SELECT ss.*, s.name, s.description, s.category, s.icon FROM student_skills ss JOIN skills s ON ss.skill_id = s.id WHERE ss.student_id = ?");
$stmt_skills->execute([$student['id']]);
$skills = $stmt_skills->fetchAll();

// Category tags colors
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

$skillIcons = [
    'headphones' => 'headphones',
    'zap' => 'zap',
    'trending-up' => 'trending-up',
    'music' => 'music',
    'radio' => 'radio'
];

render_header("Mis Habilidades - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Mis Habilidades</h1>
            <p class="text-sm text-muted-foreground">Tu desarrollo técnico y artístico en números</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <!-- Intro Card -->
        <div class="rounded-xl border border-border/50 bg-card p-6">
            <h3 class="font-bold text-lg text-foreground mb-2 flex items-center gap-2">
                <i data-lucide="zap" class="text-primary h-5 w-5"></i>
                Tu Árbol de Competencias
            </h3>
            <p class="text-sm text-muted-foreground">
                Para convertirte en un DJ completo, trabajas en 5 áreas clave. Completa tareas, clases y evaluaciones para ganar XP en cada competencia y desbloquear eventos avanzados en el Simulador.
            </p>
        </div>

        <!-- Skills Grid -->
        <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($skills as $skill): 
                $progress_pct = ($skill['xp'] / $skill['xp_to_next_level']) * 100;
                $cat_class = $categoryColors[$skill['category']] ?? 'bg-muted text-muted-foreground';
                $cat_label = $categoryLabels[$skill['category']] ?? $skill['category'];
                $icon_name = $skillIcons[$skill['icon']] ?? 'zap';
            ?>
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4 hover:border-primary/20 transition-all">
                    <!-- Title & Category badge -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-muted rounded-lg flex items-center justify-center text-primary border border-border">
                                <i data-lucide="<?php echo $icon_name; ?>" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-base text-foreground"><?php echo htmlspecialchars($skill['name']); ?></h4>
                                <span class="inline-block mt-0.5 text-3xs font-semibold px-2 py-0.5 rounded border <?php echo $cat_class; ?>">
                                    <?php echo $cat_label; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-primary">Nv. <?php echo $skill['level']; ?></span>
                            <span class="text-2xs text-muted-foreground block">máx. 5</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-sm text-muted-foreground">
                        <?php echo htmlspecialchars($skill['description']); ?>
                    </p>

                    <!-- Progress Bar -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-2xs text-muted-foreground">
                            <span>Experiencia</span>
                            <span><?php echo $skill['xp']; ?> / <?php echo $skill['xp_to_next_level']; ?> XP</span>
                        </div>
                        <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" style="width: <?php echo $progress_pct; ?>%"></div>
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex items-center justify-between pt-2 border-t border-border/30 text-xs">
                        <span class="text-muted-foreground">Estado:</span>
                        <?php if ($skill['status'] === 'completed'): ?>
                            <span class="text-success font-semibold flex items-center gap-1">
                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                Completado
                            </span>
                        <?php elseif ($skill['status'] === 'in-progress'): ?>
                            <span class="text-primary font-semibold flex items-center gap-1">
                                <i data-lucide="loader" class="h-4 w-4 animate-spin"></i>
                                En progreso
                            </span>
                        <?php else: ?>
                            <span class="text-muted-foreground font-semibold flex items-center gap-1">
                                <i data-lucide="circle" class="h-4 w-4"></i>
                                No iniciado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php
render_footer();
?>
