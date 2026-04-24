<?php
/**
 * Plugin Name: 0n Core
 * Plugin URI: https://0ncore.com
 * Description: AI Command Center for WordPress. One 0n token — 91 services, 1,171 tools. Generate content, scan for HIPAA compliance, sync with your CRM.
 * Version: 3.0.0
 * Author: RocketOpp LLC
 * Author URI: https://rocketopp.com
 * License: MIT
 */

if (!defined('ABSPATH')) exit;

define('ON_CORE_VERSION', '3.0.0');
define('ON_CORE_SUPABASE_URL', 'https://pwujhhmlrtxjmjzyttwn.supabase.co');
define('ON_CORE_SUPABASE_ANON', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3dWpoaG1scnR4am1qenl0dHduIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA0MjI0NTcsImV4cCI6MjA4NTk5ODQ1N30.VA_AqMDtjfoQUIOsYR6CdZ5O4Akyggg6PgLw1UOnr3g');
define('ON_CORE_API_URL', 'https://www.0ncore.com');

// --- Token Verification ---
function on_core_verify_token($token) {
    $url = ON_CORE_SUPABASE_URL . '/rest/v1/profiles?access_token=eq.' . urlencode($token) . '&select=*';
    $response = wp_remote_get($url, [
        'headers' => [
            'apikey' => ON_CORE_SUPABASE_ANON,
            'Authorization' => 'Bearer ' . ON_CORE_SUPABASE_ANON,
        ],
        'timeout' => 10,
    ]);
    if (is_wp_error($response)) return null;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return (!empty($body) && is_array($body)) ? $body[0] : null;
}

// --- Core API Call ---
function on_core_api($path, $method = 'GET', $body = null) {
    $args = ['method' => $method, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 15];
    if ($body) $args['body'] = json_encode($body);
    $response = wp_remote_request(ON_CORE_API_URL . $path, $args);
    if (is_wp_error($response)) return ['error' => $response->get_error_message()];
    return json_decode(wp_remote_retrieve_body($response), true);
}

// --- Admin Menu ---
add_action('admin_menu', function () {
    add_options_page('0n Core Settings', '0n Core', 'manage_options', '0n-core', 'on_core_settings_page');
    add_menu_page('0n Core', '0n Core', 'manage_options', '0n-core-dashboard', 'on_core_dashboard_page', 'dashicons-rest-api', 30);
});

// --- Settings Page ---
function on_core_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['on_core_token'])) {
        $token = sanitize_text_field($_POST['on_core_token']);
        $profile = on_core_verify_token($token);
        if ($profile) {
            update_option('0n_core_token', $token);
            update_option('0n_core_profile', json_encode($profile));
            echo '<div class="notice notice-success"><p>Connected as <strong>' . esc_html($profile['full_name'] ?? $profile['email']) . '</strong> — ' . esc_html($profile['plan'] ?? 'free') . ' plan.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Invalid token. Get yours at <a href="https://0ncore.com/settings" target="_blank">0ncore.com/settings</a></p></div>';
        }
    }

    if (isset($_POST['on_core_action'])) {
        $action = sanitize_text_field($_POST['on_core_action']);
        $result = null;
        if ($action === 'blog') $result = on_core_api('/api/cron/blog');
        elseif ($action === 'use-case') $result = on_core_api('/api/cron/use-cases');
        elseif ($action === 'hipaa') $result = on_core_api('/api/hipaa/scan', 'POST', ['site' => get_site_url()]);
        if ($result) echo '<div class="notice notice-info"><pre style="white-space:pre-wrap;margin:0;">' . esc_html(json_encode($result, JSON_PRETTY_PRINT)) . '</pre></div>';
    }

    $token = get_option('0n_core_token', '');
    $profile = json_decode(get_option('0n_core_profile', '{}'), true);
    $connected = !empty($token) && !empty($profile['email']);
    ?>
    <div class="wrap" style="max-width:720px;">
        <style>
            .on-card{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:24px;margin:16px 0;color:#e6edf3;}
            .on-label{font-size:12px;color:#8b949e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;}
            .on-token-input{width:100%;font-family:monospace;background:#0d1117;border:1px solid #30363d;color:#e6edf3;padding:10px 14px;border-radius:6px;font-size:14px;}
            .on-btn{background:#6EE05A;color:#0d1117;border:none;padding:9px 20px;border-radius:6px;font-weight:700;cursor:pointer;font-size:13px;margin-right:8px;}
            .on-btn-ghost{background:transparent;color:#6EE05A;border:1px solid #30363d;padding:9px 18px;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;margin-right:8px;}
            .on-badge{display:inline-block;background:rgba(110,224,90,.12);color:#6EE05A;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;}
        </style>

        <h1 style="color:#e6edf3;font-size:22px;margin-bottom:4px;">0n Core <span class="on-badge">v<?php echo ON_CORE_VERSION; ?></span></h1>
        <p style="color:#8b949e;margin-top:4px;">One token. 91 services. 1,171 tools.</p>

        <?php if ($connected): ?>
        <div class="on-card">
            <div class="on-label">Connected Profile</div>
            <div style="font-size:17px;font-weight:600;margin-bottom:4px;"><?php echo esc_html($profile['full_name'] ?? $profile['email']); ?></div>
            <div style="color:#8b949e;font-size:13px;margin-bottom:12px;"><?php echo esc_html($profile['plan'] ?? 'free'); ?> plan &nbsp;·&nbsp; <?php echo esc_html($profile['role'] ?? 'member'); ?></div>
            <a href="https://www.0ncore.com/console" target="_blank" class="on-btn" style="text-decoration:none;display:inline-block;">Open Console</a>
        </div>
        <?php endif; ?>

        <div class="on-card">
            <div class="on-label">0n Token</div>
            <form method="post">
                <input type="text" name="on_core_token" class="on-token-input" value="<?php echo esc_attr($token); ?>" placeholder="0n_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="margin-bottom:12px;" />
                <button type="submit" class="on-btn"><?php echo $connected ? 'Update Token' : 'Connect'; ?></button>
                <?php if (!$connected): ?><a href="https://0ncore.com/settings" target="_blank" class="on-btn-ghost" style="text-decoration:none;">Get Token</a><?php endif; ?>
            </form>
        </div>

        <?php if ($connected): ?>
        <div class="on-card">
            <div class="on-label">Quick Actions</div>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                <button type="submit" name="on_core_action" value="blog" class="on-btn">Generate Blog</button>
                <button type="submit" name="on_core_action" value="use-case" class="on-btn-ghost">Generate Use Case</button>
                <button type="submit" name="on_core_action" value="hipaa" class="on-btn-ghost">HIPAA Scan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// --- Dashboard Widget ---
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('on_core_widget', '0n Core Status', function () {
        $token = get_option('0n_core_token', '');
        $profile = json_decode(get_option('0n_core_profile', '{}'), true);
        $connected = !empty($token) && !empty($profile['email']);
        echo '<style>.on-w-status{display:flex;align-items:center;gap:8px;}.on-w-dot{width:8px;height:8px;border-radius:50%;}</style>';
        echo '<div class="on-w-status">';
        echo '<div class="on-w-dot" style="background:' . ($connected ? '#6EE05A' : '#f85149') . ';box-shadow:0 0 6px ' . ($connected ? 'rgba(110,224,90,.5)' : 'rgba(248,81,73,.5)') . ';"></div>';
        echo '<strong>' . ($connected ? 'Connected — ' . esc_html($profile['full_name'] ?? $profile['email']) : 'Not Connected') . '</strong>';
        echo '</div>';
        if ($connected) {
            echo '<p style="color:#555;font-size:12px;margin:8px 0 0;">' . esc_html($profile['plan'] ?? 'free') . ' plan &nbsp;·&nbsp; 1,171 tools &nbsp;·&nbsp; 91 services</p>';
            echo '<a href="' . admin_url('admin.php?page=0n-core-dashboard') . '" style="display:inline-block;margin-top:10px;background:#6EE05A;color:#0d1117;padding:6px 14px;border-radius:5px;font-weight:700;font-size:12px;text-decoration:none;">Open 0n Dashboard</a>';
        } else {
            echo '<p style="font-size:12px;margin:8px 0 0;"><a href="' . admin_url('options-general.php?page=0n-core') . '">Connect your 0n token</a></p>';
        }
    });
});

// --- Dashboard Page ---
function on_core_dashboard_page() {
    $profile = json_decode(get_option('0n_core_profile', '{}'), true);
    $connected = !empty($profile['email']);
    echo '<div class="wrap"><h1>0n Core Dashboard</h1>';
    if ($connected) {
        echo '<p>Connected as <strong>' . esc_html($profile['full_name'] ?? $profile['email']) . '</strong>. Full dashboard at <a href="https://www.0ncore.com/console" target="_blank">0ncore.com/console</a>.</p>';
        echo '<iframe src="https://www.0ncore.com/console" style="width:100%;height:80vh;border:1px solid #30363d;border-radius:8px;" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>';
    } else {
        echo '<p>No token connected. <a href="' . admin_url('options-general.php?page=0n-core') . '">Add your 0n token</a> to get started.</p>';
    }
    echo '</div>';
}
