<?php
require_once __DIR__ . '/../Backend/scripts/auth.php';
require_once __DIR__ . '/../Backend/scripts/db.php';

require_login();
$user = get_current_user_details();

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Extract avatar seed from current URL if it is a Dicebear avatar
$avatar_seed = '';
$is_custom_avatar = true;
if (preg_match('/seed=([^&]+)/', $user['avatar'], $matches)) {
    $avatar_seed = urldecode($matches[1]);
    $is_custom_avatar = false;
}

render_header("Mi Perfil y Configuración - NogiK");
render_sidebar();
?>

<!-- Main Content Area -->
<div class="flex-1 flex flex-col min-w-0 bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 backdrop-blur px-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Mi Perfil</h1>
            <p class="text-sm text-muted-foreground">Administra tu información personal y configuraciones de la cuenta</p>
        </div>
    </header>

    <!-- Content Area -->
    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        
        <?php if ($success === 'profile_updated'): ?>
            <div class="bg-success/10 border border-success/20 text-success text-sm rounded-lg p-3">
                ¡Perfil actualizado con éxito!
            </div>
        <?php elseif ($error === 'empty'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                Por favor, completa los campos obligatorios.
            </div>
        <?php elseif ($error === 'email_taken'): ?>
            <div class="bg-destructive/10 border border-destructive/20 text-destructive text-sm rounded-lg p-3">
                El correo electrónico ingresado ya está en uso por otra cuenta.
            </div>
        <?php endif; ?>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Personal info & settings (2 Cols) -->
            <div class="lg:col-span-2 space-y-6">
                <form action="../Backend/scripts/actions.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" id="cropped_avatar_data" name="cropped_avatar_data" value="">
                    
                    <!-- General Settings Card -->
                    <div class="bg-card border border-border/50 rounded-xl p-6 space-y-6">
                        <h3 class="font-bold text-base text-foreground flex items-center gap-2 pb-2 border-b border-border/30">
                            <i data-lucide="user" class="h-5 w-5 text-primary"></i>
                            Información de la Cuenta
                        </h3>
                        
                        <!-- Avatar live preview and seed selection -->
                        <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-muted/20 border border-border/40 rounded-xl">
                            <div class="relative group cursor-pointer" onclick="triggerAvatarUpload()">
                                <img id="avatar-preview-img" src="<?php echo htmlspecialchars($user['avatar'] ?? 'Frontend/public/placeholder-user.jpg'); ?>" class="w-24 h-24 rounded-full border border-border bg-[#0F1115] shadow-lg transition-transform group-hover:scale-105 object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-full transition-opacity">
                                    <i data-lucide="camera" class="h-5 w-5 text-white"></i>
                                </div>
                            </div>
                            <!-- Hidden File Input -->
                            <input type="file" id="avatar_file" name="avatar_file" accept="image/*" class="hidden" onchange="previewUploadedAvatar(this)">
                            
                            <div class="flex-1 space-y-2 w-full">
                                <label for="avatar_seed" class="text-xs font-semibold text-foreground flex items-center justify-between">
                                    <span>Semilla de tu Avatar (Dicebear)</span>
                                    <?php if ($is_custom_avatar): ?>
                                        <span class="text-3xs bg-primary/20 text-primary border border-primary/30 px-1.5 py-0.5 rounded leading-none">Foto Personalizada Activa</span>
                                    <?php endif; ?>
                                </label>
                                <div class="flex gap-2">
                                    <input id="avatar_seed" name="avatar_seed" value="<?php echo htmlspecialchars($avatar_seed); ?>" placeholder="Ej: Carlos, Laura, TechnoDJ" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none" oninput="updateAvatarPreview()">
                                    <button type="button" onclick="randomizeAvatar()" class="bg-muted hover:bg-muted/80 text-foreground font-semibold px-3 py-2 rounded-lg text-xs transition-colors flex items-center gap-1 flex-shrink-0">
                                        <i data-lucide="shuffle" class="h-3.5 w-3.5"></i>
                                        Aleatorio
                                    </button>
                                </div>
                                <p class="text-3xs text-muted-foreground">Escribe cualquier palabra para generar dinámicamente un avatar de caricatura único y divertido, o haz click en la foto de la izquierda para subir una foto personalizada.</p>
                            </div>
                        </div>

                        <!-- Name and Email input -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="name" class="text-xs font-semibold text-foreground">Nombre Completo</label>
                                <input id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                            </div>
                            <div class="space-y-1.5">
                                <label for="email" class="text-xs font-semibold text-foreground">Correo Electrónico</label>
                                <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-input border border-border rounded-lg px-3 py-2 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                            </div>
                        </div>

                        <!-- Change Password block -->
                        <div class="space-y-4 pt-2">
                            <h4 class="text-xs font-bold text-foreground uppercase tracking-wider border-b border-border/30 pb-2">Seguridad (Cambiar Contraseña)</h4>
                            <div class="space-y-1.5">
                                <label for="new_password" class="text-xs font-semibold text-foreground">Nueva Contraseña (Dejar vacío si no deseas cambiarla)</label>
                                <div class="relative">
                                    <input id="new_password" name="new_password" type="password" placeholder="••••••••" class="w-full bg-input border border-border rounded-lg pl-3 pr-10 py-2 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-none">
                                    <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3">
                        <a href="<?php echo $user['role'] === 'student' ? 'student.php' : 'teacher.php'; ?>" class="bg-card hover:bg-muted/40 border border-border text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                            Volver al Dashboard
                        </a>
                        <button type="submit" class="bg-[#00F2FF] text-[#0F1115] font-bold px-4 py-2 rounded-lg text-xs hover:opacity-90 transition-colors shadow-lg shadow-primary/10">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Preferences (1 Col) -->
            <div class="space-y-6">
                <!-- Session Preferences Card -->
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-4">
                    <h3 class="font-bold text-base text-foreground flex items-center gap-2 pb-2 border-b border-border/30">
                        <i data-lucide="sliders" class="h-5 w-5 text-secondary"></i>
                        Preferencias de la Interfaz
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Theme Toggle (Functional) -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p id="theme-toggle-label" class="text-xs font-semibold text-foreground leading-tight">Cambiar a Modo Claro</p>
                                <p id="theme-toggle-desc" class="text-3xs text-muted-foreground mt-0.5">Activa un tema visual claro y limpio para el día.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="pref-theme" class="sr-only peer" onchange="toggleThemePref(this)">
                                <div class="w-9 h-5 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-muted-foreground after:border-muted-foreground after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:bg-primary peer-checked:after:border-primary peer-checked:bg-primary/20"></div>
                            </label>
                        </div>

                        <!-- Email Notifications toggle (Local storage bound) -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-foreground leading-tight">Notificaciones de Evaluaciones</p>
                                <p class="text-3xs text-muted-foreground mt-0.5">Recibe correos cuando un profesor califique tus sets.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="pref-notifications" class="sr-only peer" onchange="saveNotificationPref(this)">
                                <div class="w-9 h-5 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-muted-foreground after:border-muted-foreground after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:bg-primary peer-checked:after:border-primary peer-checked:bg-primary/20"></div>
                            </label>
                        </div>

                        <!-- Sound effects toggle -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-foreground leading-tight">Efectos de Sonido</p>
                                <p class="text-3xs text-muted-foreground mt-0.5">Sonidos retro al subir de nivel o ganar reputación.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="pref-sounds" class="sr-only peer" onchange="saveSoundsPref(this)">
                                <div class="w-9 h-5 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-muted-foreground after:border-muted-foreground after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:bg-primary peer-checked:after:border-primary peer-checked:bg-primary/20"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Account details Card -->
                <div class="bg-card border border-border/50 rounded-xl p-6 space-y-3">
                    <h4 class="font-bold text-xs text-foreground uppercase tracking-wider border-b border-border/30 pb-2">Estadísticas del Usuario</h4>
                    <div class="text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Rol:</span>
                            <span class="font-semibold text-foreground capitalize"><?php echo htmlspecialchars($user['role'] === 'student' ? 'Estudiante' : 'Profesor'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Miembro desde:</span>
                            <span class="font-semibold text-foreground"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <?php if ($user['role'] === 'student'): ?>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nivel actual:</span>
                                <span class="font-semibold text-primary">Nivel <?php echo $user['level']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Reputación:</span>
                                <span class="font-semibold text-[#00F2FF]"><?php echo $user['reputation']; ?> Puntos</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId, button) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    
    // Toggle icon
    button.innerHTML = isPassword 
        ? `<i data-lucide="eye-off" class="h-4 w-4"></i>`
        : `<i data-lucide="eye" class="h-4 w-4"></i>`;
    lucide.createIcons();
}

function updateAvatarPreview() {
    const seed = document.getElementById('avatar_seed').value.trim();
    const previewImg = document.getElementById('avatar-preview-img');
    if (!previewImg) return;
    
    // Construct new Dicebear url
    const seedParam = encodeURIComponent(seed || 'NogiK');
    previewImg.src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${seedParam}`;
}

function randomizeAvatar() {
    const randomSeeds = [
        'Aria', 'Max', 'BPM', 'Beat', 'Fader', 'Mixer', 'Vinyl', 'Crossfader', 'Laser', 
        'Neon', 'Techno', 'Synth', 'Milo', 'Luna', 'Klaus', 'Ava', 'Zoe', 'Felix'
    ];
    const randomIndex = Math.floor(Math.random() * randomSeeds.length);
    const selectedSeed = randomSeeds[randomIndex];
    
    const seedInput = document.getElementById('avatar_seed');
    if (seedInput) {
        seedInput.value = selectedSeed;
        updateAvatarPreview();
    }
}

// Play chiptune fanfare using Web Audio API (retro sounds)
function playRetroSound() {
    if (localStorage.getItem('nogik_pref_sounds') === '0') return;
    
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();
    
    const playNote = (freq, time, duration) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, time);
        
        gain.gain.setValueAtTime(0.08, time);
        gain.gain.exponentialRampToValueAtTime(0.0001, time + duration);
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        osc.start(time);
        osc.stop(time + duration);
    };
    
    const now = ctx.currentTime;
    // Retro level up sound: C5 -> E5 -> G5 -> C6
    playNote(523.25, now, 0.1);
    playNote(659.25, now + 0.08, 0.1);
    playNote(783.99, now + 0.16, 0.1);
    playNote(1046.50, now + 0.24, 0.2);
}

// Prefs persistence logic using localStorage
function updateThemeText(isLight) {
    const label = document.getElementById('theme-toggle-label');
    const desc = document.getElementById('theme-toggle-desc');
    if (!label || !desc) return;
    
    if (isLight) {
        label.textContent = "Cambiar a Modo Oscuro";
        desc.textContent = "Activa el tema visual cyberpunk oscuro y con neones.";
    } else {
        label.textContent = "Cambiar a Modo Claro";
        desc.textContent = "Activa un tema visual claro y limpio para el día.";
    }
}

function toggleThemePref(checkbox) {
    const isLight = checkbox.checked;
    localStorage.setItem('nogik_theme', isLight ? 'light' : 'dark');
    
    if (isLight) {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    } else {
        document.documentElement.classList.remove('light');
        document.documentElement.classList.add('dark');
    }
    
    updateThemeText(isLight);
    playRetroSound();
}

function saveNotificationPref(checkbox) {
    localStorage.setItem('nogik_pref_notifications', checkbox.checked ? '1' : '0');
    if (checkbox.checked) {
        playRetroSound();
    }
}

function saveSoundsPref(checkbox) {
    localStorage.setItem('nogik_pref_sounds', checkbox.checked ? '1' : '0');
    if (checkbox.checked) {
        playRetroSound();
    }
}

function triggerAvatarUpload() {
    const fileInput = document.getElementById('avatar_file');
    if (fileInput) fileInput.click();
}

function previewUploadedAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            initCropImage(e.target.result);
            document.getElementById('crop-modal').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

let zoom = 1.0;
let posX = 0;
let posY = 0;
let rotation = 0;
let isDragging = false;
let startX = 0;
let startY = 0;
let cropImage = null;

function initCropImage(src) {
    cropImage = new Image();
    cropImage.src = src;
    cropImage.onload = function() {
        const imgEl = document.getElementById('crop-image');
        if (!imgEl) return;
        
        imgEl.src = src;
        
        // Reset transforms
        zoom = 1.0;
        posX = 0;
        posY = 0;
        rotation = 0;
        
        // Reset slider
        document.getElementById('zoom-slider').value = 100;
        
        // Container size
        const containerW = 360;
        const containerH = 240;
        
        const imgRatio = cropImage.width / cropImage.height;
        if (imgRatio > 1.5) {
            imgEl.style.height = containerH + 'px';
            imgEl.style.width = (containerH * imgRatio) + 'px';
        } else {
            imgEl.style.width = containerW + 'px';
            imgEl.style.height = (containerW / imgRatio) + 'px';
        }
        
        // Center position
        posX = (containerW - parseInt(imgEl.style.width)) / 2;
        posY = (containerH - parseInt(imgEl.style.height)) / 2;
        
        updateImageTransform();
    };
}

function updateImageTransform() {
    const imgEl = document.getElementById('crop-image');
    if (imgEl) {
        imgEl.style.transform = `translate(${posX}px, ${posY}px) scale(${zoom}) rotate(${rotation}deg)`;
    }
}

function handleZoom(val) {
    zoom = val / 100;
    updateImageTransform();
}

function handleRotate() {
    rotation = (rotation + 90) % 360;
    updateImageTransform();
}

function startDragging(e) {
    isDragging = true;
    startX = e.clientX - posX;
    startY = e.clientY - posY;
}

function dragImage(e) {
    if (isDragging) {
        posX = e.clientX - startX;
        posY = e.clientY - startY;
        updateImageTransform();
    }
}

function startDraggingTouch(e) {
    if (e.touches && e.touches[0]) {
        isDragging = true;
        const touch = e.touches[0];
        startX = touch.clientX - posX;
        startY = touch.clientY - posY;
    }
}

function dragImageTouch(e) {
    if (isDragging && e.touches && e.touches[0]) {
        e.preventDefault();
        const touch = e.touches[0];
        posX = touch.clientX - startX;
        posY = touch.clientY - startY;
        updateImageTransform();
    }
}

function stopDragging() {
    isDragging = false;
}

function closeCropModal() {
    document.getElementById('crop-modal').classList.add('hidden');
    document.getElementById('avatar_file').value = '';
}

function saveCroppedImage() {
    const imgEl = document.getElementById('crop-image');
    if (!imgEl) return;
    
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const ctx = canvas.getContext('2d');
    
    ctx.fillStyle = '#0F1115';
    ctx.fillRect(0, 0, 256, 256);
    
    const containerW = 360;
    const containerH = 240;
    const cropSquareSize = 180;
    const cropX = (containerW - cropSquareSize) / 2;
    const cropY = (containerH - cropSquareSize) / 2;
    const scaleCanvas = 256 / cropSquareSize;
    
    ctx.save();
    ctx.scale(scaleCanvas, scaleCanvas);
    ctx.translate(-cropX, -cropY);
    
    const imgW = imgEl.offsetWidth;
    const imgH = imgEl.offsetHeight;
    
    ctx.translate(posX + imgW / 2, posY + imgH / 2);
    ctx.rotate((rotation * Math.PI) / 180);
    ctx.scale(zoom, zoom);
    
    ctx.drawImage(cropImage, -imgW / 2, -imgH / 2, imgW, imgH);
    ctx.restore();
    
    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
    
    document.getElementById('avatar-preview-img').src = croppedDataUrl;
    document.getElementById('cropped_avatar_data').value = croppedDataUrl;
    
    const seedInput = document.getElementById('avatar_seed');
    if (seedInput) {
        seedInput.value = '';
    }
    
    closeCropModal();
}

document.addEventListener('DOMContentLoaded', () => {
    // Prefill theme preference
    const themeCheck = document.getElementById('pref-theme');
    if (themeCheck) {
        const isLight = localStorage.getItem('nogik_theme') === 'light';
        themeCheck.checked = isLight;
        updateThemeText(isLight);
    }

    // Prefill notifications preference
    const notifCheck = document.getElementById('pref-notifications');
    if (notifCheck) {
        notifCheck.checked = localStorage.getItem('nogik_pref_notifications') !== '0';
    }
    
    // Prefill sounds preference
    const soundsCheck = document.getElementById('pref-sounds');
    if (soundsCheck) {
        soundsCheck.checked = localStorage.getItem('nogik_pref_sounds') !== '0';
    }
});
</script>

<!-- Image Crop Modal -->
<div id="crop-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4 hidden">
    <div class="bg-card border border-border rounded-2xl w-full max-w-[400px] p-6 flex flex-col gap-5 shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-border/40">
            <h3 class="text-base font-extrabold text-foreground">Editar imagen</h3>
            <button type="button" onclick="closeCropModal()" class="text-muted-foreground hover:text-foreground p-1 transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <!-- Crop workspace -->
        <div class="relative w-[360px] h-[240px] bg-[#090A0C] border border-border/40 rounded-xl overflow-hidden cursor-move select-none mx-auto"
             id="crop-workspace"
             onmousedown="startDragging(event)"
             onmousemove="dragImage(event)"
             onmouseup="stopDragging()"
             onmouseleave="stopDragging()"
             ontouchstart="startDraggingTouch(event)"
             ontouchmove="dragImageTouch(event)"
             ontouchend="stopDragging()">
            
            <img id="crop-image" class="absolute origin-center max-w-none transition-none" style="transform-origin: center;">
            
            <!-- Circular cutout mask -->
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-[180px] h-[180px] rounded-full border-2 border-white shadow-[0_0_0_9999px_rgba(15,17,21,0.75)]"></div>
            </div>
        </div>

        <!-- Controls (Slider and Rotate) -->
        <div class="flex items-center justify-between gap-4 px-2">
            <div class="flex items-center gap-3 flex-1">
                <i data-lucide="image" class="h-3.5 w-3.5 text-muted-foreground flex-shrink-0"></i>
                <input type="range" id="zoom-slider" min="100" max="300" value="100" class="w-full h-1 bg-muted rounded-lg appearance-none cursor-pointer accent-primary" oninput="handleZoom(this.value)">
                <i data-lucide="image" class="h-5 w-5 text-foreground flex-shrink-0"></i>
            </div>
            
            <div class="flex gap-2">
                <button type="button" onclick="handleRotate()" class="p-2 bg-muted hover:bg-muted/80 rounded-lg text-foreground transition-colors flex items-center justify-center" title="Rotar 90°">
                    <i data-lucide="rotate-cw" class="h-4 w-4"></i>
                </button>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex justify-end gap-3 pt-3 border-t border-border/40">
            <button type="button" onclick="closeCropModal()" class="bg-muted hover:bg-muted/80 text-foreground font-semibold px-4 py-2 rounded-lg text-xs transition-colors">
                Cancelar
            </button>
            <button type="button" onclick="saveCroppedImage()" class="bg-[#00F2FF] text-[#0F1115] font-bold px-4 py-2 rounded-lg text-xs hover:opacity-90 transition-colors shadow-lg shadow-primary/10">
                Guardar
            </button>
        </div>
    </div>
</div>

<?php
render_footer();
?>
