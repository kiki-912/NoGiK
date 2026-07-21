<?php
// NoGiK - Authentication & Layout Utilities
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Check if DB is OK, if not redirect to database setup wizard (unless we are processing the setup action)
if (defined('DB_STATUS') && DB_STATUS !== 'OK') {
    $is_setup_action = (isset($_GET['action']) && $_GET['action'] === 'setup_db') || (isset($_POST['action']) && $_POST['action'] === 'setup_db');
    if (!$is_setup_action) {
        render_db_setup_wizard();
        exit();
    }
}

function render_db_setup_wizard() {
    $status = DB_STATUS;
    $error_msg = defined('DB_ERROR_MESSAGE') ? DB_ERROR_MESSAGE : '';
    ?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Base de Datos - NoGiK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              background: '#0F1115',
              foreground: '#E0E0E0',
              card: '#1A1D23',
              primary: '#00F2FF',
              'primary-foreground': '#0F1115',
              secondary: '#7000FF',
              border: '#2D3139',
              muted: '#9CA3AF'
            }
          }
        }
      }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-background text-foreground min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-card border border-border rounded-xl p-8 shadow-xl text-center space-y-6">
        <div class="inline-flex p-3 bg-primary/10 text-primary rounded-full">
            <i data-lucide="database" class="h-10 w-10"></i>
        </div>
        
        <div class="space-y-2">
            <h2 class="text-2xl font-extrabold text-foreground">Asistente de Base de Datos</h2>
            <p class="text-xs text-muted">Configuración inicial de NoGiK en tu servidor local</p>
        </div>

        <div class="p-4 rounded-lg bg-black/20 border border-border/50 text-sm text-left space-y-3">
            <?php if ($status === 'CONNECTION_FAILED'): ?>
                <div class="text-destructive font-bold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="h-5 w-5 flex-shrink-0"></i>
                    <span>Conexión Fallida</span>
                </div>
                <p class="text-xs text-muted">
                    No pudimos establecer conexión con tu servidor MySQL local en <strong>localhost</strong>.
                </p>
                <div class="bg-destructive/10 text-destructive text-xs p-2.5 rounded border border-destructive/20 font-mono select-all">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
                <p class="text-xs text-muted mt-2">
                    Asegúrate de que Laragon, XAMPP o tu servidor local estén encendidos y corriendo el servicio de MySQL (puerto 3306) con el usuario <strong>root</strong> y contraseña vacía.
                </p>
            <?php else: ?>
                <div class="text-primary font-bold flex items-center gap-2">
                    <i data-lucide="info" class="h-5 w-5 flex-shrink-0"></i>
                    <span>Base de datos no inicializada</span>
                </div>
                <p class="text-xs text-muted">
                    El servidor de MySQL está activo, pero la base de datos <strong>nogik</strong> o sus tablas no se encuentran creadas en el sistema.
                </p>
                <div class="bg-primary/10 text-primary text-xs p-2.5 rounded border border-primary/20 font-mono">
                    SQL file: Backend/database/nogik.sql
                </div>
                
                <form action="Backend/scripts/actions.php" method="POST" class="pt-2">
                    <input type="hidden" name="action" value="setup_db">
                    <button type="submit" class="w-full bg-primary text-primary-foreground font-bold py-2.5 px-4 rounded-lg hover:opacity-90 transition-all text-xs flex items-center justify-center gap-2">
                        <i data-lucide="play" class="h-4 w-4"></i>
                        Crear e Importar Base de Datos Automáticamente
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="pt-2">
            <button onclick="window.location.reload()" class="text-xs text-muted hover:text-foreground flex items-center justify-center gap-1.5 mx-auto transition-colors">
                <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                Reintentar Conexión
            </button>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
    <?php
}

// Get reputation tier information
function get_reputation_tier($reputation) {
    $tiers = [
        ['id' => 'bedroom', 'name' => 'Bedroom DJ', 'min' => 0, 'max' => 19, 'color' => '#9CA3AF', 'desc' => 'Pinchando en tu habitación.'],
        ['id' => 'warmup', 'name' => 'Warm-up DJ', 'min' => 20, 'max' => 39, 'color' => '#00F2FF', 'desc' => 'Abriendo la noche en locales.'],
        ['id' => 'resident', 'name' => 'Resident DJ', 'min' => 40, 'max' => 59, 'color' => '#7000FF', 'desc' => 'Residente de un club local.'],
        ['id' => 'headliner', 'name' => 'Headliner', 'min' => 60, 'max' => 79, 'color' => '#FF6B35', 'desc' => 'El acto principal de la noche.'],
        ['id' => 'festival', 'name' => 'Festival DJ', 'min' => 80, 'max' => 9999, 'color' => '#39FF14', 'desc' => 'Pinchando en los escenarios principales.']
    ];
    
    foreach ($tiers as $tier) {
        if ($reputation >= $tier['min'] && $reputation <= $tier['max']) {
            return $tier;
        }
    }
    return $tiers[0];
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user details from database
function get_current_user_details() {
    global $pdo;
    if (!is_logged_in()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) return null;
    
    if ($user['role'] === 'student') {
        $stmt_s = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt_s->execute([$user['id']]);
        $student = $stmt_s->fetch();
        if ($student) {
            $user = array_merge($user, $student);
        }
    } else {
        $stmt_t = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
        $stmt_t->execute([$user['id']]);
        $teacher = $stmt_t->fetch();
        if ($teacher) {
            $user = array_merge($user, $teacher);
        }
    }
    
    return $user;
}

// Redirect helpers
function require_login() {
    if (!is_logged_in()) {
        $path = (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'index.php' : '../index.php';
        header("Location: $path");
        exit();
    }
}

function require_role($role) {
    require_login();
    $user = get_current_user_details();
    if ($user['role'] !== $role) {
        $prefix = (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'Frontend/' : '';
        header("Location: " . $prefix . ($user['role'] === 'student' ? 'student.php' : 'teacher.php'));
        exit();
    }
}

// Render HTML header
function render_header($title = "NoGiK - Academia DJ") {
    ob_start();
    $user = get_current_user_details();
    ?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <?php $prefix = (basename($_SERVER['PHP_SELF']) === 'index.php') ? '' : '../'; ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo $prefix; ?>favicon.svg?v=2">
    
    <!-- Instant Theme Setter to prevent flashing -->
    <script>
      (function() {
        const theme = localStorage.getItem('nogik_theme') || 'dark';
        if (theme === 'light') {
          document.documentElement.classList.remove('dark');
          document.documentElement.classList.add('light');
        } else {
          document.documentElement.classList.add('dark');
          document.documentElement.classList.remove('light');
        }
      })();
    </script>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
    
    <!-- Custom global styles -->
    <link rel="stylesheet" href="Frontend/Styles/styles.css">
    
    <!-- Tailwind CSS via CDN with customized configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
              mono: ['Space Mono', 'monospace'],
            },
            colors: {
              background: 'var(--color-background)',
              foreground: 'var(--color-foreground)',
              card: 'var(--color-card)',
              'card-foreground': 'var(--color-card-foreground)',
              popover: 'var(--color-popover)',
              'popover-foreground': 'var(--color-popover-foreground)',
              primary: 'var(--color-primary)',
              'primary-foreground': 'var(--color-primary-foreground)',
              secondary: 'var(--color-secondary)',
              'secondary-foreground': 'var(--color-secondary-foreground)',
              muted: 'var(--color-muted)',
              'muted-foreground': 'var(--color-muted-foreground)',
              destructive: '#FF4757',
              'destructive-foreground': '#E0E0E0',
              success: '#39FF14',
              'success-foreground': '#0F1115',
              border: 'var(--color-border)',
              input: 'var(--color-input)',
              ring: 'var(--color-ring)',
            }
          }
        }
      }
    </script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Flatpickr (Custom Datepicker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col md:flex-row overflow-x-hidden">
    <?php
}

// Render Sidebar Navigation
function render_sidebar() {
    $user = get_current_user_details();
    if (!$user) return;
    
    $is_student = ($user['role'] === 'student');
    $current_page = basename($_SERVER['PHP_SELF']);
    
    global $pdo;
    $pending_count = 0;
    if (!$is_student && isset($pdo)) {
        try {
            $stmt_pending = $pdo->query("SELECT COUNT(*) FROM class_attendees WHERE status = 'pending'");
            $pending_count = $stmt_pending->fetchColumn();
        } catch (Exception $e) {}
    }
    
    $student_nav = [
        ['title' => 'Dashboard', 'url' => 'student.php', 'icon' => 'layout-dashboard'],
        ['title' => 'Mis Habilidades', 'url' => 'student_skills.php', 'icon' => 'zap'],
        ['title' => 'Calendario', 'url' => 'student_calendar.php', 'icon' => 'calendar'],
        ['title' => 'Mis Sets', 'url' => 'student_sets.php', 'icon' => 'music'],
        ['title' => 'Comunidad', 'url' => 'student_community.php', 'icon' => 'users'],
        ['title' => 'Simulador', 'url' => 'student_simulator.php', 'icon' => 'radio'],
    ];
    
    $teacher_nav = [
        ['title' => 'Dashboard', 'url' => 'teacher.php', 'icon' => 'layout-dashboard'],
        ['title' => 'Alumnos', 'url' => 'teacher_students.php', 'icon' => 'graduation-cap'],
        ['title' => 'Clases', 'url' => 'teacher_classes.php', 'icon' => 'calendar'],
        ['title' => 'Evaluaciones', 'url' => 'teacher_evaluations.php', 'icon' => 'clipboard-check'],
        ['title' => 'Simulador', 'url' => 'teacher_simulator.php', 'icon' => 'radio'],
        ['title' => 'Notificaciones', 'url' => 'teacher_notifications.php', 'icon' => 'bell'],
    ];
    
    $nav_items = $is_student ? $student_nav : $teacher_nav;
    ?>
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm hidden md:hidden"></div>
    <!-- Sidebar -->
    <aside id="sidebar-menu" class="fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full md:translate-x-0 md:relative md:inset-auto md:w-64 bg-background border-b md:border-b-0 md:border-r border-border flex flex-col justify-between flex-shrink-0 transition-transform duration-300 ease-in-out">
        <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
            <!-- Header Brand -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-border">
                <div class="relative flex-shrink-0">
                    <i data-lucide="disc-3" class="h-8 w-8 text-primary animate-spin" style="animation-duration: 4s;"></i>
                    <div class="absolute inset-0 bg-primary/20 blur-md rounded-full"></div>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-lg leading-none">
                        <span class="text-primary">NoGi</span><span class="text-foreground">K</span>
                    </span>
                    <span class="text-xs text-muted-foreground mt-1">
                        <?php echo $is_student ? 'Estudiante' : 'Profesor'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <p class="text-xs font-semibold text-muted-foreground px-3 mb-2 uppercase tracking-wider">Navegación</p>
                <?php foreach ($nav_items as $item): 
                    $item_base = explode('?', $item['url'])[0];
                    $is_active = ($current_page === $item_base);
                    $active_classes = $is_active 
                        ? 'bg-primary/10 text-primary border-l-2 border-primary' 
                        : 'text-muted-foreground hover:bg-muted/30 hover:text-foreground';
                ?>
                    <a href="<?php echo $item['url']; ?>" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $active_classes; ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="<?php echo $item['icon']; ?>" class="h-4 w-4"></i>
                            <span><?php echo $item['title']; ?></span>
                        </div>
                        <?php if ($item['url'] === 'teacher_notifications.php' && $pending_count > 0): ?>
                            <span class="bg-destructive text-destructive-foreground text-[10px] font-bold px-2 py-0.5 rounded-full leading-none animate-pulse">
                                <?php echo $pending_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <!-- Student Progress Info in Sidebar -->
            <?php if ($is_student): 
                $tier = get_reputation_tier($user['reputation']);
                $xp_pct = ($user['xp'] / $user['xp_to_next_level']) * 100;
            ?>
                <div class="p-4 border-t border-border mt-2 space-y-4">
                    <p class="text-xs font-semibold text-muted-foreground px-2 uppercase tracking-wider">Tu Progreso</p>
                    
                    <div class="px-2 space-y-3">
                        <!-- Level -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Nivel</span>
                            <span class="text-sm font-bold text-primary"><?php echo $user['level']; ?></span>
                        </div>
                        
                        <!-- XP Bar -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-2xs text-muted-foreground">
                                <span>XP</span>
                                <span><?php echo $user['xp']; ?> / <?php echo $user['xp_to_next_level']; ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all" style="width: <?php echo $xp_pct; ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Reputation -->
                        <div class="flex items-center gap-2.5 p-2 rounded-lg bg-muted/40 border border-border/50">
                            <div class="w-2.5 h-2.5 rounded-full" style="background-color: <?php echo $tier['color']; ?>"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-foreground truncate leading-none mb-0.5">
                                    <?php echo $tier['name']; ?>
                                </p>
                                <p class="text-3xs text-muted-foreground leading-none">
                                    Rep: <?php echo $user['reputation']; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Teacher Actions -->
                <div class="p-4 border-t border-border mt-2">
                    <p class="text-xs font-semibold text-muted-foreground px-2 uppercase tracking-wider mb-3">Acciones Rápidas</p>
                    <div class="space-y-1 px-1">
                        <a href="teacher_classes.php?create=1" class="flex items-center gap-2 text-xs text-muted-foreground hover:text-primary py-1.5">
                            <i data-lucide="plus-circle" class="h-3.5 w-3.5 text-primary"></i>
                            <span>Programar Clase</span>
                        </a>
                        <a href="teacher_evaluations.php" class="flex items-center gap-2 text-xs text-muted-foreground hover:text-secondary py-1.5">
                            <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-secondary"></i>
                            <span>Evaluar Pendientes</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-border bg-card/40 flex items-center justify-between">
            <a href="profile.php" class="flex items-center gap-2 min-w-0 group hover:opacity-85 transition-opacity">
                <img src="<?php echo htmlspecialchars($user['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($user['name']); ?>" 
                     class="h-8 w-8 rounded-full border border-border group-hover:border-primary/50 flex-shrink-0 bg-muted transition-colors">
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-semibold text-foreground group-hover:text-primary truncate leading-tight transition-colors">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                    <span class="text-3xs text-muted-foreground truncate">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </span>
                </div>
            </a>
            
            <!-- Logout Button -->
            <a href="logout.php" title="Cerrar sesión" class="p-1.5 rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors">
                <i data-lucide="log-out" class="h-4 w-4"></i>
            </a>
        </div>
    </aside>
    <?php
}

// Render Footer
function render_footer() {
    ?>
    <!-- Initialize Lucide Icons -->
    <script>
      lucide.createIcons();
      (function() {
        const menu = document.getElementById('sidebar-menu');
        const backdrop = document.getElementById('sidebar-backdrop');
        const toggle = document.getElementById('mobile-menu-toggle');
        const body = document.body;

        if (toggle && menu && backdrop) {
          function openMenu() {
            menu.classList.remove('-translate-x-full');
            menu.classList.add('translate-x-0');
            backdrop.classList.remove('hidden');
            body.classList.add('overflow-hidden');
          }
          function closeMenu() {
            menu.classList.add('-translate-x-full');
            menu.classList.remove('translate-x-0');
            backdrop.classList.add('hidden');
            body.classList.remove('overflow-hidden');
          }
          toggle.addEventListener('click', openMenu);
          backdrop.addEventListener('click', closeMenu);
          document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !backdrop.classList.contains('hidden')) {
              closeMenu();
            }
          });
        }
      })();
    </script>
</body>
</html>
    <?php
    $output = ob_get_clean();
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        $output = str_replace([
            'src="Frontend/public/',
            'src=\'Frontend/public/\'',
            'href="Frontend/Styles/',
            'href=\'Frontend/Styles/\''
        ], [
            'src="public/',
            'src=\'public/\'',
            'href="Styles/',
            'href=\'Styles/\''
        ], $output);
    }
    echo $output;
}
?>
