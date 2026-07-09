<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_role('student');
$student = get_current_user_details();

// Fetch all sets from community (with student and user details)
$stmt = $pdo->prepare("
    SELECT s.*, se.overall_score, u.name as student_name, u.avatar as student_avatar, st.level, st.reputation 
    FROM dj_sets s 
    LEFT JOIN set_evaluations se ON s.id = se.set_id
    JOIN users u ON s.student_id = u.id
    JOIN students st ON s.student_id = st.user_id
    ORDER BY s.uploaded_at DESC
");
$stmt->execute();
$all_sets = $stmt->fetchAll();

// Fetch counts
$stmt_stud_cnt = $pdo->prepare("SELECT COUNT(*) FROM students");
$stmt_stud_cnt->execute();
$total_students_count = $stmt_stud_cnt->fetchColumn();

$stmt_comm_cnt = $pdo->prepare("SELECT COUNT(*) FROM comments");
$stmt_comm_cnt->execute();
$total_comments_count = $stmt_comm_cnt->fetchColumn();

$success = $_GET['success'] ?? '';
?>

<?php
render_header("Comunidad - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Comunidad</h1>
            <p class="text-sm text-muted-foreground">Escucha y comenta los sets de otros alumnos de la academia</p>
        </div>
    </header>

    <!-- Content -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'commented'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Comentario añadido correctamente!
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="bg-card border border-border/50 rounded-xl p-4 flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-full text-primary">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-foreground"><?php echo $total_students_count; ?></h4>
                    <p class="text-xs text-muted-foreground">Alumnos activos</p>
                </div>
            </div>
            
            <div class="bg-card border border-border/50 rounded-xl p-4 flex items-center gap-4">
                <div class="p-3 bg-secondary/10 rounded-full text-secondary">
                    <i data-lucide="music" class="h-5 w-5"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-foreground"><?php echo count($all_sets); ?></h4>
                    <p class="text-xs text-muted-foreground">Sets compartidos</p>
                </div>
            </div>
            
            <div class="bg-card border border-border/50 rounded-xl p-4 flex items-center gap-4">
                <div class="p-3 bg-success/10 rounded-full text-success">
                    <i data-lucide="message-square" class="h-5 w-5"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-foreground"><?php echo $total_comments_count; ?></h4>
                    <p class="text-xs text-muted-foreground">Comentarios totales</p>
                </div>
            </div>
        </div>

        <!-- Feed List -->
        <?php if (empty($all_sets)): ?>
            <div class="bg-card border border-border/50 rounded-xl p-12 text-center text-muted-foreground">
                <i data-lucide="music" class="h-12 w-12 mx-auto opacity-30 mb-4"></i>
                <p class="text-lg">No hay sets compartidos en la comunidad aún</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($all_sets as $set): 
                    $owner_tier = get_reputation_tier($set['reputation']);
                    $is_own = ($set['student_id'] === $_SESSION['user_id']);
                    
                    // Fetch comments for this set
                    $stmt_c = $pdo->prepare("SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.role as user_role FROM comments c JOIN users u ON c.user_id = u.id WHERE c.set_id = ? ORDER BY c.created_at ASC");
                    $stmt_c->execute([$set['id']]);
                    $comments = $stmt_c->fetchAll();
                ?>
                    <div class="bg-card border border-border/50 rounded-xl p-6">
                        <div class="flex flex-col md:flex-row gap-5">
                            <!-- Play Cover -->
                            <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="w-24 h-24 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center flex-shrink-0 hover:from-primary/30 hover:to-secondary/30 transition-all text-primary">
                                <i data-lucide="play" class="h-10 w-10"></i>
                            </a>

                            <!-- Card Body -->
                            <div class="flex-1 min-w-0 space-y-2">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($set['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-10 h-10 rounded-full border bg-muted">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-foreground text-sm">
                                                    <?php echo htmlspecialchars($set['student_name']); ?>
                                                </span>
                                                <?php if ($is_own): ?>
                                                    <span class="text-3xs font-semibold px-1.5 py-0.5 bg-muted text-muted-foreground rounded border border-border uppercase">Tu set</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-2 text-2xs text-muted-foreground">
                                                <span class="w-2 h-2 rounded-full" style="background-color: <?php echo $owner_tier['color']; ?>"></span>
                                                <span><?php echo $owner_tier['name']; ?></span>
                                                <span>•</span>
                                                <span>Nivel <?php echo $set['level']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-2xs text-muted-foreground">
                                        <?php echo date('d M', strtotime($set['uploaded_at'])); ?>
                                    </span>
                                </div>

                                <h3 class="font-bold text-base text-foreground mt-1"><?php echo htmlspecialchars($set['title']); ?></h3>
                                <p class="text-sm text-muted-foreground leading-relaxed line-clamp-2"><?php echo htmlspecialchars($set['description']); ?></p>

                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <span class="px-1.5 py-0.5 bg-muted border border-border text-muted-foreground rounded text-2xs"><?php echo htmlspecialchars($set['genre']); ?></span>
                                    <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                        <?php echo $set['duration']; ?> min
                                    </span>
                                    <?php if ($set['overall_score']): ?>
                                        <span class="flex items-center gap-0.5 text-xs text-[#39FF14] font-semibold bg-[#39FF14]/10 px-1.5 py-0.5 rounded">
                                            <i data-lucide="star" class="h-3.5 w-3.5 fill-current"></i>
                                            <?php echo round($set['overall_score'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3 pt-3 border-t border-border/30 mt-2">
                                    <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="inline-flex items-center gap-1.5 border border-border hover:bg-muted/40 font-semibold px-3 py-1.5 rounded-lg text-xs text-foreground transition-colors">
                                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        Escuchar
                                    </a>
                                    <button onclick="toggleComments('<?php echo $set['id']; ?>')" class="inline-flex items-center gap-1.5 hover:bg-muted/40 font-semibold px-3 py-1.5 rounded-lg text-xs text-muted-foreground hover:text-foreground transition-colors">
                                        <i data-lucide="message-square" class="h-3.5 w-3.5"></i>
                                        <span><?php echo count($comments); ?> comentarios</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Comments Section (Toggled by JS) -->
                        <div id="comments-section-<?php echo $set['id']; ?>" class="hidden mt-6 pt-6 border-t border-border/50 space-y-4">
                            <h4 class="font-bold text-sm text-foreground flex items-center gap-2">
                                <i data-lucide="message-square" class="h-4 w-4 text-primary"></i>
                                Comentarios (<?php echo count($comments); ?>)
                            </h4>

                            <!-- Comments list -->
                            <?php if (empty($comments)): ?>
                                <p class="text-xs text-muted-foreground">Sé el primero en comentar este set</p>
                            <?php else: ?>
                                <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                                    <?php foreach ($comments as $comment): 
                                        $is_teach = ($comment['user_role'] === 'teacher');
                                    ?>
                                        <div class="flex gap-3 text-xs bg-muted/10 p-3 rounded border border-border/30">
                                            <img src="<?php echo htmlspecialchars($comment['user_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-6 h-6 rounded-full bg-muted">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-foreground"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                                        <?php if ($is_teach): ?>
                                                            <span class="bg-primary/20 text-primary px-1.5 py-0.2 rounded text-3xs font-bold border border-primary/30 uppercase">Profesor</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-3xs text-muted-foreground"><?php echo date('d M, H:i', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                                <p class="text-muted-foreground leading-normal"><?php echo htmlspecialchars($comment['content']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Add comment input (Redirect action) -->
                            <form action="../Backend/scripts/actions.php" method="POST" class="flex gap-2 pt-2">
                                <input type="hidden" name="action" value="submit_comment">
                                <input type="hidden" name="set_id" value="<?php echo $set['id']; ?>">
                                
                                <img src="<?php echo htmlspecialchars($student['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0">
                                <div class="flex-1 flex gap-2">
                                    <input required name="content" placeholder="Escribe un comentario..." class="flex-1 bg-input border border-border rounded-lg px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    <button type="submit" class="bg-primary text-primary-foreground font-semibold px-3 py-1.5 rounded-lg text-xs hover:bg-primary/95 transition-colors">
                                        <i data-lucide="send" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    function toggleComments(setId) {
        const sect = document.getElementById('comments-section-' + setId);
        if (sect) {
            sect.classList.toggle('hidden');
        }
    }
</script>

<?php
render_footer();
?>
