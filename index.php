<?php
require_once __DIR__ . '/Backend/scripts/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $user = get_current_user_details();
    header("Location: " . ($user['role'] === 'student' ? 'Frontend/student.php' : 'Frontend/teacher.php'));
    exit();
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NogiK - Academia DJ</title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
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
              'card-foreground': '#E0E0E0',
              popover: '#1A1D23',
              'popover-foreground': '#E0E0E0',
              primary: '#00F2FF',
              'primary-foreground': '#0F1115',
              secondary: '#7000FF',
              'secondary-foreground': '#E0E0E0',
              muted: '#252830',
              'muted-foreground': '#9CA3AF',
              accent: '#7000FF',
              'accent-foreground': '#E0E0E0',
              destructive: '#FF4757',
              'destructive-foreground': '#E0E0E0',
              success: '#39FF14',
              'success-foreground': '#0F1115',
              border: '#2D3139',
              input: '#252830',
              ring: '#00F2FF',
            }
          }
        }
      }
    </script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-background text-foreground min-h-screen">
    <main class="min-h-screen bg-background">
        <!-- Hero Section -->
        <div class="relative overflow-hidden min-h-screen flex items-center">
            <!-- Background gradient effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-secondary/5"></div>
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-3xl"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 w-full">
                <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                    <!-- Left side - Branding -->
                    <div class="flex-1 text-center lg:text-left">
                        <div class="flex items-center justify-center lg:justify-start gap-3 mb-6">
                            <div class="relative">
                                <i data-lucide="disc-3" class="h-14 w-14 text-primary animate-spin" style="animation-duration: 6s;"></i>
                                <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full"></div>
                            </div>
                            <h1 class="text-5xl font-bold tracking-tight">
                                <span class="text-primary">Nogi</span><span class="text-foreground">K</span>
                            </h1>
                        </div>
                        
                        <p class="text-xl text-muted-foreground mb-8 max-w-xl">
                            La plataforma definitiva para formación y simulación de carrera DJ. 
                            Aprende, practica y construye tu camino hacia el escenario.
                        </p>

                        <!-- Features Grid -->
                        <div class="grid grid-cols-2 gap-4 max-w-md mx-auto lg:mx-0">
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <i data-lucide="headphones" class="h-5 w-5 text-primary"></i>
                                <span class="text-sm">Clases Interactivas</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <i data-lucide="zap" class="h-5 w-5 text-primary"></i>
                                <span class="text-sm">Habilidades Reales</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <i data-lucide="trending-up" class="h-5 w-5 text-primary"></i>
                                <span class="text-sm">Sistema de Reputación</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <i data-lucide="radio" class="h-5 w-5 text-primary"></i>
                                <span class="text-sm">Simulador de Carrera</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right side - Login Form -->
                    <div class="w-full max-w-md">
                        <div class="rounded-xl border border-border/50 bg-card/80 p-8 shadow-xl backdrop-blur-sm">
                            <div class="text-center mb-6">
                                <h2 class="text-2xl font-bold text-foreground">Iniciar Sesión</h2>
                                <p class="text-sm text-muted-foreground mt-1">Accede a tu cuenta de NogiK</p>
                            </div>
                            
                            <?php if (isset($_GET['success']) && $_GET['success'] === 'setup_complete'): ?>
                                <div class="bg-success/15 border border-success/35 text-success text-sm rounded-lg p-3 mb-4 flex items-center gap-2">
                                    <i data-lucide="check-circle" class="h-4.5 w-4.5 flex-shrink-0"></i>
                                    <span>¡Base de datos importada correctamente! Ya puedes usar las cuentas demo.</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($error === 'invalid'): ?>
                                <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3 mb-4">
                                    Credenciales inválidas. Intenta con las cuentas demo.
                                </div>
                            <?php elseif ($error === 'empty'): ?>
                                <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3 mb-4">
                                    Por favor ingresa todos los campos.
                                </div>
                            <?php endif; ?>

                            <form action="Backend/scripts/actions.php" method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="login">
                                
                                <div class="space-y-1.5">
                                    <label for="email" class="text-sm font-medium text-foreground">Email</label>
                                    <input 
                                        id="email" 
                                        name="email"
                                        type="email" 
                                        placeholder="tu@email.com" 
                                        required 
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none"
                                    >
                                </div>
                                
                                <div class="space-y-1.5">
                                    <label for="password" class="text-sm font-medium text-foreground">Contraseña</label>
                                    <input 
                                        id="password" 
                                        name="password"
                                        type="password" 
                                        placeholder="••••••••" 
                                        required 
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none"
                                    >
                                </div>
                                
                                <button 
                                    type="submit" 
                                    class="w-full bg-primary text-primary-foreground font-semibold py-2.5 px-4 rounded-lg hover:bg-primary/95 transition-colors focus:outline-none"
                                >
                                    Iniciar Sesión
                                </button>
                            </form>

                            <!-- Demo Buttons -->
                            <div class="mt-8 space-y-3 pt-6 border-t border-border/50">
                                <p class="text-center text-xs text-muted-foreground uppercase tracking-wider font-semibold">Cuentas Demo</p>
                                <div class="flex gap-2">
                                    <button 
                                        type="button" 
                                        onclick="fillDemo('demo@nogik.com', 'demo123')"
                                        class="flex-1 text-center py-2 px-3 border border-primary/50 text-primary text-sm font-medium rounded-lg hover:bg-primary/10 transition-colors"
                                    >
                                        Alumno Demo
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="fillDemo('carlos@nogik.com', 'teacher123')"
                                        class="flex-1 text-center py-2 px-3 border border-secondary/50 text-secondary text-sm font-medium rounded-lg hover:bg-secondary/10 transition-colors"
                                    >
                                        Profesor Demo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section class="py-20 px-4 bg-card/30 border-t border-border">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-center mb-12 text-foreground">
                    Todo lo que necesitas para ser DJ profesional
                </h2>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature Card 1 -->
                    <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/30 transition-colors">
                        <div class="inline-flex p-3 rounded-lg mb-4 text-primary bg-primary/10 border border-primary/20">
                            <i data-lucide="headphones" class="h-8 w-8"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-foreground">Aprende con Expertos</h3>
                        <p class="text-muted-foreground text-sm">Clases estructuradas con profesionales de la industria. Materiales, videos y seguimiento personalizado.</p>
                    </div>
                    
                    <!-- Feature Card 2 -->
                    <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/30 transition-colors">
                        <div class="inline-flex p-3 rounded-lg mb-4 text-secondary bg-secondary/10 border border-secondary/20">
                            <i data-lucide="trending-up" class="h-8 w-8"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-foreground">Progresa Visualmente</h3>
                        <p class="text-muted-foreground text-sm">Sistema de habilidades con 5 competencias clave. Observa tu evolución en tiempo real con gráficos detallados.</p>
                    </div>
                    
                    <!-- Feature Card 3 -->
                    <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/30 transition-colors">
                        <div class="inline-flex p-3 rounded-lg mb-4 text-success bg-success/10 border border-success/20">
                            <i data-lucide="users" class="h-8 w-8"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-foreground">Simula tu Carrera</h3>
                        <p class="text-muted-foreground text-sm">Eventos simulados que replican situaciones reales. Desde bares hasta festivales, gana reputación y desbloquea oportunidades.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 px-4 border-t border-border bg-card/10">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="disc-3" class="h-6 w-6 text-primary"></i>
                    <span class="font-semibold text-foreground">NogiK</span>
                </div>
                <p class="text-sm text-muted-foreground">
                    Plataforma de formación para DJs
                </p>
            </div>
        </footer>
    </main>

    <script>
        lucide.createIcons();
        
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
