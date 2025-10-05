<?php
// includes/maintenance_check.php
require_once __DIR__ . '/../config/database.php';

function isMaintenanceMode($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings 
                              WHERE setting_key IN ('maintenance_enabled', 'maintenance_start', 'maintenance_end')");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($settings) || $settings['maintenance_enabled'] != '1') {
            return false;
        }
        
        date_default_timezone_set('Asia/Manila');
        $current = new DateTime('now');
        $start = new DateTime($settings['maintenance_start']);
        $end = new DateTime($settings['maintenance_end']);
        
        return ($current >= $start && $current <= $end);
    } catch (Exception $e) {
        return false;
    }
}

function getMaintenanceSettings($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings 
                              WHERE setting_key LIKE 'maintenance_%'");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Set defaults
        return [
            'message' => $settings['maintenance_message'] ?? 'System is currently under maintenance. Please check back later.',
            'title' => $settings['maintenance_title'] ?? 'System Maintenance',
            'icon' => $settings['maintenance_icon'] ?? '🔧',
            'bg_color_1' => $settings['maintenance_bg_color_1'] ?? '#667eea',
            'bg_color_2' => $settings['maintenance_bg_color_2'] ?? '#764ba2',
            'box_bg' => $settings['maintenance_box_bg'] ?? '#ffffff',
            'title_color' => $settings['maintenance_title_color'] ?? '#333333',
            'text_color' => $settings['maintenance_text_color'] ?? '#666666',
            'timer_bg_1' => $settings['maintenance_timer_bg_1'] ?? '#667eea',
            'timer_bg_2' => $settings['maintenance_timer_bg_2'] ?? '#764ba2',
            'timer_text' => $settings['maintenance_timer_text'] ?? '⏰ Please check back soon',
            'contact_text' => $settings['maintenance_contact_text'] ?? 'For urgent matters, please contact support',
            'contact_email' => $settings['maintenance_contact_email'] ?? '',
            'contact_phone' => $settings['maintenance_contact_phone'] ?? '',
            'show_countdown' => $settings['maintenance_show_countdown'] ?? '0',
            'end_datetime' => $settings['maintenance_end'] ?? ''
        ];
    } catch (Exception $e) {
        return [];
    }
}

// Check if current user is admin
function isAdminUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Block access if maintenance mode is active and user is not admin
if (!isAdminUser() && isMaintenanceMode($pdo)) {
    $settings = getMaintenanceSettings($pdo);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($settings['title']); ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, <?php echo htmlspecialchars($settings['bg_color_1']); ?> 0%, <?php echo htmlspecialchars($settings['bg_color_2']); ?> 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .maintenance-container {
                background: <?php echo htmlspecialchars($settings['box_bg']); ?>;
                padding: 50px 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 600px;
                width: 100%;
                animation: slideIn 0.5s ease-out;
            }
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .maintenance-icon {
                font-size: 80px;
                margin-bottom: 20px;
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.1);
                }
            }
            h1 {
                color: <?php echo htmlspecialchars($settings['title_color']); ?>;
                font-size: 32px;
                margin-bottom: 15px;
            }
            p {
                color: <?php echo htmlspecialchars($settings['text_color']); ?>;
                font-size: 18px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .timer {
                background: linear-gradient(135deg, <?php echo htmlspecialchars($settings['timer_bg_1']); ?> 0%, <?php echo htmlspecialchars($settings['timer_bg_2']); ?> 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                margin-top: 20px;
            }
            .countdown {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin-top: 20px;
            }
            .countdown-item {
                background: linear-gradient(135deg, <?php echo htmlspecialchars($settings['timer_bg_1']); ?> 0%, <?php echo htmlspecialchars($settings['timer_bg_2']); ?> 100%);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                min-width: 80px;
            }
            .countdown-number {
                font-size: 32px;
                font-weight: bold;
                display: block;
            }
            .countdown-label {
                font-size: 12px;
                text-transform: uppercase;
                opacity: 0.9;
            }
            .contact-info {
                margin-top: 30px;
                padding-top: 30px;
                border-top: 2px solid #f0f0f0;
                color: <?php echo htmlspecialchars($settings['text_color']); ?>;
                font-size: 14px;
            }
            .contact-details {
                margin-top: 15px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .contact-item {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 15px;
            }
            .contact-item a {
                color: <?php echo htmlspecialchars($settings['timer_bg_1']); ?>;
                text-decoration: none;
                font-weight: 600;
            }
            .contact-item a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="maintenance-icon"><?php echo htmlspecialchars($settings['icon']); ?></div>
            <h1><?php echo htmlspecialchars($settings['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($settings['message'])); ?></p>
            
            <?php if ($settings['show_countdown'] == '1' && !empty($settings['end_datetime'])): ?>
            <div class="countdown" id="countdown">
                <div class="countdown-item">
                    <span class="countdown-number" id="days">00</span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="hours">00</span>
                    <span class="countdown-label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="minutes">00</span>
                    <span class="countdown-label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="seconds">00</span>
                    <span class="countdown-label">Seconds</span>
                </div>
            </div>
            <script>
                const endDate = new Date("<?php echo $settings['end_datetime']; ?>").getTime();
                
                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = endDate - now;
                    
                    if (distance < 0) {
                        document.getElementById('countdown').innerHTML = '<div class="timer">Maintenance completed! Refreshing...</div>';
                        setTimeout(() => location.reload(), 3000);
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    document.getElementById('days').textContent = String(days).padStart(2, '0');
                    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
                }
                
                updateCountdown();
                setInterval(updateCountdown, 1000);
            </script>
            <?php else: ?>
            <div class="timer">
                <?php echo htmlspecialchars($settings['timer_text']); ?>
            </div>
            <?php endif; ?>
            
            <div class="contact-info">
                <div><?php echo htmlspecialchars($settings['contact_text']); ?></div>
                <?php if (!empty($settings['contact_email']) || !empty($settings['contact_phone'])): ?>
                <div class="contact-details">
                    <?php if (!empty($settings['contact_email'])): ?>
                    <div class="contact-item">
                        <span>📧</span>
                        <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>">
                            <?php echo htmlspecialchars($settings['contact_email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['contact_phone'])): ?>
                    <div class="contact-item">
                        <span>📞</span>
                        <a href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                            <?php echo htmlspecialchars($settings['contact_phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
