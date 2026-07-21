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
render_header("Comunidad - NoGiK");
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
                <h1 class="whitespace-normal text-lg sm:text-2xl font-bold text-foreground ">Comunidad</h1>
                <p class="text-xs sm:text-sm text-muted-foreground hidden sm:block truncate">Escucha y comenta los sets de otros alumnos de la academia</p>
            </div>
        </div>
        
    </header>

    <!-- Content -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto overflow-x-hidden max-h-[calc(100vh-80px)] w-full">
        
        <?php if ($success === 'commented'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Comentario añadido correctamente!
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 min-[480px]:grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-card border border-border/50 rounded-xl p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div class="p-2 sm:p-3 bg-primary/10 rounded-full text-primary flex-shrink-0">
                    <i data-lucide="users" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-lg sm:text-xl font-bold text-foreground leading-tight"><?php echo $total_students_count; ?></h4>
                    <p class="text-xs text-muted-foreground truncate">Alumnos activos</p>
                </div>
            </div>
            
            <div class="bg-card border border-border/50 rounded-xl p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div class="p-2 sm:p-3 bg-secondary/10 rounded-full text-secondary flex-shrink-0">
                    <i data-lucide="music" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-lg sm:text-xl font-bold text-foreground leading-tight"><?php echo count($all_sets); ?></h4>
                    <p class="text-xs text-muted-foreground truncate">Sets compartidos</p>
                </div>
            </div>
            
            <div class="bg-card border border-border/50 rounded-xl p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
                <div class="p-2 sm:p-3 bg-success/10 rounded-full text-success flex-shrink-0">
                    <i data-lucide="message-square" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-lg sm:text-xl font-bold text-foreground leading-tight"><?php echo $total_comments_count; ?></h4>
                    <p class="text-xs text-muted-foreground truncate">Comentarios totales</p>
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
                    <div class="bg-card border border-border/50 rounded-xl p-4">
                        <!-- Top Header: User info & Date -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2.5">
                                <img src="<?php echo htmlspecialchars($set['student_avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-8 h-8 rounded-full border bg-muted flex-shrink-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-foreground text-sm truncate">
                                            <?php echo htmlspecialchars($set['student_name']); ?>
                                        </span>
                                        <?php if ($is_own): ?>
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 bg-muted text-muted-foreground rounded border border-border uppercase">Tu set</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-3xs text-muted-foreground truncate">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: <?php echo $owner_tier['color']; ?>"></span>
                                        <span class="truncate"><?php echo $owner_tier['name']; ?></span>
                                        <span>•</span>
                                        <span class="whitespace-nowrap">Nivel <?php echo $set['level']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-3xs text-muted-foreground flex-shrink-0 self-start mt-1">
                                <?php echo date('d M', strtotime($set['uploaded_at'])); ?>
                            </span>
                        </div>

                        <!-- Mid Section: Play button & Title/Desc -->
                        <div class="flex flex-row gap-3">
                            <!-- Play Cover -->
                            <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="w-12 h-12 sm:w-16 sm:h-16 rounded-xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center flex-shrink-0 hover:from-primary/30 hover:to-secondary/30 transition-all text-primary mt-1">
                                <i data-lucide="play" class="h-6 w-6 sm:h-8 sm:w-8 ml-0.5"></i>
                            </a>
                            
                            <!-- Card Body -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-sm sm:text-base text-foreground leading-tight"><?php echo htmlspecialchars($set['title']); ?></h3>
                                <p class="text-xs text-muted-foreground mt-1 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($set['description']); ?></p>
                                
                                <!-- Tags -->
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="px-1.5 py-0.5 bg-muted border border-border text-muted-foreground rounded text-3xs font-medium"><?php echo htmlspecialchars($set['genre']); ?></span>
                                    <span class="flex items-center gap-1 text-3xs text-muted-foreground font-medium">
                                        <i data-lucide="clock" class="h-3 w-3"></i>
                                        <?php echo $set['duration']; ?> min
                                    </span>
                                    <?php if ($set['overall_score']): ?>
                                        <span class="flex items-center gap-0.5 text-3xs text-[#39FF14] font-bold bg-[#39FF14]/10 px-1.5 py-0.5 rounded">
                                            <i data-lucide="star" class="h-3 w-3 fill-current"></i>
                                            <?php echo round($set['overall_score'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center gap-3 pt-3 mt-3 border-t border-border/40">
                            <a href="<?php echo htmlspecialchars($set['url']); ?>" target="_blank" class="inline-flex items-center gap-1.5 border border-border hover:bg-muted/40 font-semibold px-3 py-1.5 rounded-lg text-xs text-foreground transition-colors">
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                Escuchar
                            </a>
                            <button onclick="toggleComments('<?php echo $set['id']; ?>')" class="inline-flex items-center gap-1.5 hover:bg-muted/40 font-semibold px-3 py-1.5 rounded-lg text-xs text-muted-foreground hover:text-foreground transition-colors ml-auto">
                                <i data-lucide="message-square" class="h-3.5 w-3.5"></i>
                                <span><?php echo count($comments); ?> comentarios</span>
                            </button>
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
