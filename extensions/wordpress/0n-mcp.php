<?php
/**
 * Plugin Name: 0n for WordPress
 * Plugin URI: https://0ncore.com/downloads
 * Description: Connect your WordPress site to 0n Core. One token, full control from anywhere.
 * Version: 1.0.0
 * Author: RocketOpp LLC
 * Author URI: https://rocketopp.com
 * License: MIT
 * Text Domain: 0n-mcp
 */

if (!defined('ABSPATH')) exit;

define('ON_MCP_VERSION', '1.0.0');
define('ON_MCP_API_URL', 'https://www.0ncore.com');
define('ON_MCP_NAMESPACE', '0n/v1');

/* ============================================================
 * ACTIVATION / DEACTIVATION
 * ============================================================ */

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
    if (!get_option('0n_mcp_site_id')) {
        update_option('0n_mcp_site_id', wp_generate_uuid4());
    }
});

register_deactivation_hook(__FILE__, function () {
    $token = get_option('0n_mcp_token', '');
    $site_id = get_option('0n_mcp_site_id', '');
    if ($token && $site_id) {
        wp_remote_post(ON_MCP_API_URL . '/api/services/unregister', [
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'body' => json_encode(['site_id' => $site_id]),
            'timeout' => 8,
        ]);
    }
    flush_rewrite_rules();
});

/* ============================================================
 * TOKEN VERIFICATION + SITE REGISTRATION
 * ============================================================ */

function on_mcp_verify_token($token) {
    $response = wp_remote_post(ON_MCP_API_URL . '/api/auth/verify-token', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['token' => $token]),
        'timeout' => 12,
    ]);
    if (is_wp_error($response)) return ['ok' => false, 'error' => $response->get_error_message()];
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200 || empty($body)) return ['ok' => false, 'error' => 'Invalid token'];
    return ['ok' => true, 'profile' => $body];
}

function on_mcp_register_site($token) {
    $site_id = get_option('0n_mcp_site_id');
    $payload = [
        'site_id'      => $site_id,
        'service_type' => 'wordpress',
        'site_url'     => get_site_url(),
        'site_name'    => get_bloginfo('name'),
        'mcp_endpoint' => rest_url(ON_MCP_NAMESPACE . '/mcp'),
        'wp_version'   => get_bloginfo('version'),
        'plugin_version' => ON_MCP_VERSION,
        'tools'        => array_keys(on_mcp_tool_registry()),
    ];
    $response = wp_remote_post(ON_MCP_API_URL . '/api/services/register', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ],
        'body' => json_encode($payload),
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) return ['ok' => false, 'error' => $response->get_error_message()];
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code >= 200 && $code < 300) {
        update_option('0n_mcp_registered_at', time());
        return ['ok' => true, 'data' => $body];
    }
    return ['ok' => false, 'error' => $body['error'] ?? 'Registration failed', 'code' => $code];
}

/* ============================================================
 * REST AUTH
 * ============================================================ */

function on_mcp_authenticate($request) {
    $stored = get_option('0n_mcp_token', '');
    if (empty($stored)) {
        return new WP_Error('not_connected', 'Site not connected to 0n Core', ['status' => 401]);
    }
    $auth = $request->get_header('authorization');
    if (!$auth || !preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
        return new WP_Error('missing_token', 'Missing bearer token', ['status' => 401]);
    }
    if (!hash_equals($stored, $m[1])) {
        return new WP_Error('invalid_token', 'Token does not match site', ['status' => 403]);
    }
    return true;
}

/* ============================================================
 * MCP TOOL REGISTRY
 * ============================================================ */

function on_mcp_tool_registry() {
    return [
        'wp_create_post' => [
            'description' => 'Create a post or page',
            'input' => ['title' => 'string', 'content' => 'string', 'status' => 'publish|draft|pending', 'type' => 'post|page'],
            'handler' => 'on_mcp_t_create_post',
        ],
        'wp_update_post' => [
            'description' => 'Update an existing post',
            'input' => ['id' => 'int', 'title?' => 'string', 'content?' => 'string', 'status?' => 'string'],
            'handler' => 'on_mcp_t_update_post',
        ],
        'wp_delete_post' => [
            'description' => 'Delete or trash a post',
            'input' => ['id' => 'int', 'force?' => 'bool'],
            'handler' => 'on_mcp_t_delete_post',
        ],
        'wp_list_posts' => [
            'description' => 'List posts with filters',
            'input' => ['type?' => 'string', 'status?' => 'string', 'per_page?' => 'int', 'search?' => 'string'],
            'handler' => 'on_mcp_t_list_posts',
        ],
        'wp_get_post' => [
            'description' => 'Get a single post by ID',
            'input' => ['id' => 'int'],
            'handler' => 'on_mcp_t_get_post',
        ],
        'wp_create_user' => [
            'description' => 'Create a user',
            'input' => ['username' => 'string', 'email' => 'string', 'password?' => 'string', 'role?' => 'string'],
            'handler' => 'on_mcp_t_create_user',
        ],
        'wp_list_users' => [
            'description' => 'List users',
            'input' => ['role?' => 'string', 'per_page?' => 'int'],
            'handler' => 'on_mcp_t_list_users',
        ],
        'wp_update_option' => [
            'description' => 'Update a WP option',
            'input' => ['name' => 'string', 'value' => 'mixed'],
            'handler' => 'on_mcp_t_update_option',
        ],
        'wp_get_option' => [
            'description' => 'Get a WP option',
            'input' => ['name' => 'string'],
            'handler' => 'on_mcp_t_get_option',
        ],
        'wp_install_plugin' => [
            'description' => 'Install and activate a plugin from the WP repository',
            'input' => ['slug' => 'string', 'activate?' => 'bool'],
            'handler' => 'on_mcp_t_install_plugin',
        ],
        'wp_site_info' => [
            'description' => 'Get site title, URL, version, theme, plugins',
            'input' => [],
            'handler' => 'on_mcp_t_site_info',
        ],
        'wp_upload_media' => [
            'description' => 'Upload media from a URL',
            'input' => ['url' => 'string', 'title?' => 'string', 'attach_to?' => 'int'],
            'handler' => 'on_mcp_t_upload_media',
        ],
    ];
}

/* ============================================================
 * REST API ROUTES
 * ============================================================ */

add_action('rest_api_init', function () {
    register_rest_route(ON_MCP_NAMESPACE, '/mcp', [
        'methods'  => 'POST',
        'callback' => 'on_mcp_handle_request',
        'permission_callback' => 'on_mcp_authenticate',
    ]);

    register_rest_route(ON_MCP_NAMESPACE, '/mcp/tools', [
        'methods'  => 'GET',
        'callback' => function () {
            $tools = [];
            foreach (on_mcp_tool_registry() as $name => $def) {
                $tools[] = ['name' => $name, 'description' => $def['description'], 'input' => $def['input']];
            }
            return ['tools' => $tools, 'count' => count($tools)];
        },
        'permission_callback' => 'on_mcp_authenticate',
    ]);

    register_rest_route(ON_MCP_NAMESPACE, '/mcp/health', [
        'methods'  => 'GET',
        'callback' => function () {
            return [
                'ok' => true,
                'site_id' => get_option('0n_mcp_site_id'),
                'site_url' => get_site_url(),
                'plugin_version' => ON_MCP_VERSION,
                'wp_version' => get_bloginfo('version'),
                'tool_count' => count(on_mcp_tool_registry()),
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});

function on_mcp_handle_request($request) {
    $params = $request->get_json_params();
    $tool = $params['tool'] ?? null;
    $input = $params['input'] ?? [];

    if (!$tool) return new WP_Error('missing_tool', 'Missing "tool" in body', ['status' => 400]);

    $registry = on_mcp_tool_registry();
    if (!isset($registry[$tool])) {
        return new WP_Error('unknown_tool', "Unknown tool: $tool", ['status' => 404]);
    }

    $handler = $registry[$tool]['handler'];
    if (!is_callable($handler)) {
        return new WP_Error('handler_missing', "Handler not callable for $tool", ['status' => 500]);
    }

    try {
        $result = call_user_func($handler, $input);
        return ['ok' => true, 'tool' => $tool, 'result' => $result];
    } catch (Exception $e) {
        return new WP_Error('tool_error', $e->getMessage(), ['status' => 500]);
    }
}

/* ============================================================
 * MCP TOOL HANDLERS
 * ============================================================ */

function on_mcp_t_create_post($input) {
    $id = wp_insert_post([
        'post_title'   => $input['title'] ?? 'Untitled',
        'post_content' => $input['content'] ?? '',
        'post_status'  => $input['status'] ?? 'draft',
        'post_type'    => $input['type'] ?? 'post',
    ], true);
    if (is_wp_error($id)) throw new Exception($id->get_error_message());
    return ['id' => $id, 'permalink' => get_permalink($id), 'edit_url' => get_edit_post_link($id, 'raw')];
}

function on_mcp_t_update_post($input) {
    if (empty($input['id'])) throw new Exception('id required');
    $data = ['ID' => intval($input['id'])];
    foreach (['title' => 'post_title', 'content' => 'post_content', 'status' => 'post_status'] as $k => $v) {
        if (isset($input[$k])) $data[$v] = $input[$k];
    }
    $id = wp_update_post($data, true);
    if (is_wp_error($id)) throw new Exception($id->get_error_message());
    return ['id' => $id, 'permalink' => get_permalink($id)];
}

function on_mcp_t_delete_post($input) {
    if (empty($input['id'])) throw new Exception('id required');
    $force = !empty($input['force']);
    $result = wp_delete_post(intval($input['id']), $force);
    return ['deleted' => (bool)$result, 'force' => $force];
}

function on_mcp_t_list_posts($input) {
    $query = new WP_Query([
        'post_type'      => $input['type'] ?? 'post',
        'post_status'    => $input['status'] ?? 'any',
        'posts_per_page' => intval($input['per_page'] ?? 20),
        's'              => $input['search'] ?? '',
    ]);
    $posts = [];
    foreach ($query->posts as $p) {
        $posts[] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'status' => $p->post_status,
            'type' => $p->post_type,
            'date' => $p->post_date,
            'permalink' => get_permalink($p->ID),
        ];
    }
    return ['posts' => $posts, 'total' => $query->found_posts];
}

function on_mcp_t_get_post($input) {
    if (empty($input['id'])) throw new Exception('id required');
    $p = get_post(intval($input['id']));
    if (!$p) throw new Exception('Post not found');
    return [
        'id' => $p->ID,
        'title' => $p->post_title,
        'content' => $p->post_content,
        'excerpt' => $p->post_excerpt,
        'status' => $p->post_status,
        'type' => $p->post_type,
        'author' => $p->post_author,
        'date' => $p->post_date,
        'modified' => $p->post_modified,
        'permalink' => get_permalink($p->ID),
    ];
}

function on_mcp_t_create_user($input) {
    if (empty($input['username']) || empty($input['email'])) throw new Exception('username and email required');
    $password = $input['password'] ?? wp_generate_password(16);
    $id = wp_create_user($input['username'], $password, $input['email']);
    if (is_wp_error($id)) throw new Exception($id->get_error_message());
    if (!empty($input['role'])) {
        $u = new WP_User($id);
        $u->set_role(sanitize_text_field($input['role']));
    }
    return ['id' => $id, 'username' => $input['username'], 'email' => $input['email']];
}

function on_mcp_t_list_users($input) {
    $args = ['number' => intval($input['per_page'] ?? 20)];
    if (!empty($input['role'])) $args['role'] = sanitize_text_field($input['role']);
    $users = get_users($args);
    return ['users' => array_map(function ($u) {
        return [
            'id' => $u->ID,
            'username' => $u->user_login,
            'email' => $u->user_email,
            'name' => $u->display_name,
            'roles' => $u->roles,
        ];
    }, $users)];
}

function on_mcp_t_update_option($input) {
    if (empty($input['name'])) throw new Exception('name required');
    $ok = update_option(sanitize_text_field($input['name']), $input['value']);
    return ['updated' => (bool)$ok];
}

function on_mcp_t_get_option($input) {
    if (empty($input['name'])) throw new Exception('name required');
    return ['name' => $input['name'], 'value' => get_option(sanitize_text_field($input['name']))];
}

function on_mcp_t_install_plugin($input) {
    if (empty($input['slug'])) throw new Exception('slug required');
    if (!function_exists('plugins_api')) require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    if (!class_exists('Plugin_Upgrader')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }
    $api = plugins_api('plugin_information', ['slug' => sanitize_key($input['slug'])]);
    if (is_wp_error($api)) throw new Exception($api->get_error_message());
    $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
    $installed = $upgrader->install($api->download_link);
    if (is_wp_error($installed)) throw new Exception($installed->get_error_message());
    $plugin_file = $upgrader->plugin_info();
    $activated = null;
    if (!empty($input['activate']) && $plugin_file) {
        $activated = activate_plugin($plugin_file);
        if (is_wp_error($activated)) throw new Exception($activated->get_error_message());
    }
    return ['installed' => true, 'plugin' => $plugin_file, 'activated' => $activated === null];
}

function on_mcp_t_site_info($input) {
    if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $theme = wp_get_theme();
    $active_plugins = (array) get_option('active_plugins', []);
    $all_plugins = get_plugins();
    $plugins = [];
    foreach ($all_plugins as $file => $data) {
        $plugins[] = [
            'file' => $file,
            'name' => $data['Name'],
            'version' => $data['Version'],
            'active' => in_array($file, $active_plugins, true),
        ];
    }
    return [
        'name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'url' => get_site_url(),
        'wp_version' => get_bloginfo('version'),
        'language' => get_bloginfo('language'),
        'theme' => ['name' => $theme->get('Name'), 'version' => $theme->get('Version'), 'stylesheet' => get_stylesheet()],
        'plugins' => $plugins,
        'plugin_count' => count($plugins),
        'active_plugin_count' => count($active_plugins),
    ];
}

function on_mcp_t_upload_media($input) {
    if (empty($input['url'])) throw new Exception('url required');
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = download_url(esc_url_raw($input['url']));
    if (is_wp_error($tmp)) throw new Exception($tmp->get_error_message());
    $file_array = [
        'name' => basename(parse_url($input['url'], PHP_URL_PATH)) ?: 'upload-' . time(),
        'tmp_name' => $tmp,
    ];
    $attach_to = intval($input['attach_to'] ?? 0);
    $id = media_handle_sideload($file_array, $attach_to, $input['title'] ?? null);
    if (is_wp_error($id)) {
        @unlink($tmp);
        throw new Exception($id->get_error_message());
    }
    return ['id' => $id, 'url' => wp_get_attachment_url($id)];
}

/* ============================================================
 * ADMIN UI
 * ============================================================ */

add_action('admin_menu', function () {
    add_options_page('0n MCP', '0n MCP', 'manage_options', '0n-mcp', 'on_mcp_settings_page');
});

function on_mcp_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['on_mcp_disconnect']) && check_admin_referer('on_mcp_disconnect')) {
        $token = get_option('0n_mcp_token', '');
        $site_id = get_option('0n_mcp_site_id', '');
        if ($token && $site_id) {
            wp_remote_post(ON_MCP_API_URL . '/api/services/unregister', [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
                'body' => json_encode(['site_id' => $site_id]),
                'timeout' => 8,
            ]);
        }
        delete_option('0n_mcp_token');
        delete_option('0n_mcp_profile');
        delete_option('0n_mcp_registered_at');
        echo '<div class="notice notice-success"><p>Disconnected from 0n Core.</p></div>';
    } elseif (isset($_POST['on_mcp_token']) && check_admin_referer('on_mcp_connect')) {
        $token = sanitize_text_field($_POST['on_mcp_token']);
        if (!preg_match('/^0n_/', $token)) {
            echo '<div class="notice notice-error"><p>Token must start with <code>0n_</code></p></div>';
        } else {
            $verify = on_mcp_verify_token($token);
            if (!$verify['ok']) {
                echo '<div class="notice notice-error"><p>Token verification failed: ' . esc_html($verify['error']) . '</p></div>';
            } else {
                update_option('0n_mcp_token', $token);
                update_option('0n_mcp_profile', json_encode($verify['profile']));
                $reg = on_mcp_register_site($token);
                if ($reg['ok']) {
                    echo '<div class="notice notice-success"><p>Connected and registered. Your site is now controllable from any 0n surface.</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>Token saved but registration failed: ' . esc_html($reg['error'] ?? 'unknown') . '</p></div>';
                }
            }
        }
    }

    $token = get_option('0n_mcp_token', '');
    $profile = json_decode(get_option('0n_mcp_profile', '{}'), true);
    $site_id = get_option('0n_mcp_site_id', '');
    $registered_at = get_option('0n_mcp_registered_at', 0);
    $connected = !empty($token) && !empty($profile['email'] ?? null);
    $tool_count = count(on_mcp_tool_registry());
    $mcp_endpoint = rest_url(ON_MCP_NAMESPACE . '/mcp');
    ?>
    <div class="wrap" style="max-width:760px;">
        <style>
            .on-wrap{background:#0d1117;padding:24px;border-radius:12px;color:#e6edf3;margin-top:16px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
            .on-card{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:20px;margin:14px 0;}
            .on-label{font-size:11px;color:#8b949e;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-weight:600;}
            .on-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #21262d;font-size:13px;}
            .on-row:last-child{border-bottom:none;}
            .on-row .k{color:#8b949e;}
            .on-row .v{color:#e6edf3;font-family:ui-monospace,monospace;font-size:12px;}
            .on-input{width:100%;font-family:ui-monospace,monospace;background:#0d1117;border:1px solid #30363d;color:#e6edf3;padding:11px 14px;border-radius:6px;font-size:13px;box-sizing:border-box;}
            .on-input:focus{outline:none;border-color:#6EE05A;}
            .on-btn{background:#6EE05A;color:#0d1117;border:none;padding:10px 22px;border-radius:6px;font-weight:700;cursor:pointer;font-size:13px;}
            .on-btn:hover{background:#5DD449;}
            .on-btn-ghost{background:transparent;color:#e6edf3;border:1px solid #30363d;padding:10px 18px;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;}
            .on-btn-danger{background:transparent;color:#f85149;border:1px solid #f85149;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;font-size:12px;}
            .on-status{display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}
            .on-status .dot{width:8px;height:8px;border-radius:50%;}
            .on-on{background:rgba(110,224,90,.12);color:#6EE05A;}
            .on-on .dot{background:#6EE05A;box-shadow:0 0 6px rgba(110,224,90,.6);}
            .on-off{background:rgba(248,81,73,.12);color:#f85149;}
            .on-off .dot{background:#f85149;}
            .on-h1{font-size:22px;font-weight:700;margin:0 0 4px;color:#e6edf3;}
            .on-sub{color:#8b949e;font-size:13px;margin:0 0 18px;}
            .on-link{color:#6EE05A;text-decoration:none;}
            .on-link:hover{text-decoration:underline;}
            .on-tool-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px;margin-top:8px;}
            .on-tool-chip{background:#0d1117;border:1px solid #30363d;color:#8b949e;font-family:ui-monospace,monospace;font-size:11px;padding:5px 9px;border-radius:4px;text-align:center;}
        </style>

        <div class="on-wrap">
            <h1 class="on-h1">0n for WordPress</h1>
            <p class="on-sub">v<?php echo ON_MCP_VERSION; ?> &nbsp;·&nbsp; <?php echo $tool_count; ?> MCP tools &nbsp;·&nbsp; <span class="on-status <?php echo $connected ? 'on-on' : 'on-off'; ?>"><span class="dot"></span><?php echo $connected ? 'Connected' : 'Not Connected'; ?></span></p>

            <?php if ($connected): ?>
            <div class="on-card">
                <div class="on-label">Connected Account</div>
                <div style="font-size:16px;font-weight:600;margin-bottom:2px;"><?php echo esc_html($profile['full_name'] ?? $profile['email']); ?></div>
                <div style="color:#8b949e;font-size:12px;"><?php echo esc_html($profile['email'] ?? ''); ?> &nbsp;·&nbsp; <?php echo esc_html($profile['plan'] ?? 'free'); ?> plan</div>
            </div>

            <div class="on-card">
                <div class="on-label">Site Registration</div>
                <div class="on-row"><span class="k">Site ID</span><span class="v"><?php echo esc_html($site_id); ?></span></div>
                <div class="on-row"><span class="k">MCP Endpoint</span><span class="v"><?php echo esc_html($mcp_endpoint); ?></span></div>
                <div class="on-row"><span class="k">Registered</span><span class="v"><?php echo $registered_at ? esc_html(date('Y-m-d H:i', $registered_at)) : 'pending'; ?></span></div>
                <div class="on-row"><span class="k">Tools Exposed</span><span class="v"><?php echo $tool_count; ?></span></div>
            </div>

            <div class="on-card">
                <div class="on-label">Available MCP Tools</div>
                <div class="on-tool-grid">
                    <?php foreach (array_keys(on_mcp_tool_registry()) as $t): ?>
                        <div class="on-tool-chip"><?php echo esc_html($t); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="post" style="margin-top:16px;">
                <?php wp_nonce_field('on_mcp_disconnect'); ?>
                <button type="submit" name="on_mcp_disconnect" value="1" class="on-btn-danger" onclick="return confirm('Disconnect this site from 0n Core?');">Disconnect Site</button>
            </form>
            <?php else: ?>
            <div class="on-card">
                <div class="on-label">Connect to 0n Core</div>
                <p style="color:#8b949e;font-size:13px;margin:0 0 14px;">Paste your 0n token to register this site. Once connected, you can control it from Claude, Chrome, Slack, or any 0n surface.</p>
                <form method="post">
                    <?php wp_nonce_field('on_mcp_connect'); ?>
                    <input type="text" name="on_mcp_token" class="on-input" placeholder="0n_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="margin-bottom:12px;" autocomplete="off" />
                    <button type="submit" class="on-btn">Connect</button>
                    <a href="https://0ncore.com/settings" target="_blank" class="on-btn-ghost" style="text-decoration:none;display:inline-block;margin-left:8px;">Get Token</a>
                </form>
            </div>

            <div class="on-card">
                <div class="on-label">What You'll Be Able To Do</div>
                <ul style="margin:8px 0 0 0;padding-left:18px;color:#8b949e;font-size:13px;line-height:1.7;">
                    <li>"Publish a blog post titled X" — from Claude</li>
                    <li>"List my draft posts" — from Slack</li>
                    <li>"Update site tagline to X" — from your phone</li>
                    <li>"Install Yoast and activate it" — from anywhere</li>
                    <li><?php echo $tool_count; ?> tools, all token-authenticated</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/* ============================================================
 * DASHBOARD WIDGET
 * ============================================================ */

add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('on_mcp_widget', '0n MCP — Connection Status', 'on_mcp_dashboard_widget');
});

function on_mcp_dashboard_widget() {
    $token = get_option('0n_mcp_token', '');
    $profile = json_decode(get_option('0n_mcp_profile', '{}'), true);
    $connected = !empty($token) && !empty($profile['email'] ?? null);
    $tool_count = count(on_mcp_tool_registry());
    ?>
    <style>
        .on-w{font-family:-apple-system,BlinkMacSystemFont,sans-serif;}
        .on-w-row{display:flex;align-items:center;gap:10px;}
        .on-w-dot{width:10px;height:10px;border-radius:50%;}
        .on-w-on{background:#6EE05A;box-shadow:0 0 8px rgba(110,224,90,.6);}
        .on-w-off{background:#f85149;}
        .on-w-meta{color:#666;font-size:12px;margin:10px 0 12px;}
        .on-w-btn{display:inline-block;background:#6EE05A;color:#0d1117 !important;padding:7px 16px;border-radius:5px;font-weight:700;font-size:12px;text-decoration:none;}
        .on-w-btn:hover{background:#5DD449;color:#0d1117;}
    </style>
    <div class="on-w">
        <div class="on-w-row">
            <span class="on-w-dot <?php echo $connected ? 'on-w-on' : 'on-w-off'; ?>"></span>
            <strong><?php echo $connected ? 'Connected' : 'Not Connected'; ?></strong>
            <?php if ($connected): ?><span style="color:#666;font-size:12px;">— <?php echo esc_html($profile['full_name'] ?? $profile['email']); ?></span><?php endif; ?>
        </div>
        <?php if ($connected): ?>
            <div class="on-w-meta"><?php echo $tool_count; ?> MCP tools exposed &nbsp;·&nbsp; <?php echo esc_html($profile['plan'] ?? 'free'); ?> plan</div>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=0n-mcp')); ?>" class="on-w-btn">Manage Connection</a>
        <?php else: ?>
            <div class="on-w-meta">Connect this site to 0n Core to control it from Claude, Slack, or anywhere.</div>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=0n-mcp')); ?>" class="on-w-btn">Connect Site</a>
        <?php endif; ?>
    </div>
    <?php
}

/* ============================================================
 * PLUGIN ROW LINKS
 * ============================================================ */

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="' . admin_url('options-general.php?page=0n-mcp') . '">Settings</a>');
    return $links;
});
