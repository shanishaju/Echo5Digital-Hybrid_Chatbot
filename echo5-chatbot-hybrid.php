<?php
/*
Plugin Name: Echo5 AI Chatbot (Hybrid)
Description: A WordPress chatbot plugin using a secure Node.js backend (OpenAI GPT-3.5 Turbo).
Version: 1.0.0
Author: Echo5 Digital
*/

if (!defined('ABSPATH')) exit;

define('ECHO5_CHATBOT_HYBRID_VERSION', '1.0.0');
define('ECHO5_CHATBOT_HYBRID_URL', plugin_dir_url(__FILE__));

add_action('wp_enqueue_scripts', 'enqueue_echo5_chatbot_hybrid_scripts');

function enqueue_echo5_chatbot_hybrid_scripts() {
    $backend_url = get_option('echo5_chatbot_hybrid_backend_url', 'https://react-chatbot-99g6.vercel.app/chat');
    $openai_api_key = get_option('echo5_chatbot_hybrid_openai_key', '');
    $system_prompt = get_option('echo5_chatbot_hybrid_system_prompt', 'You are a helpful expert on our products.');
    $faq = get_option('echo5_chatbot_hybrid_faq', '');
    wp_enqueue_style('echo5-chatbot-hybrid-style', ECHO5_CHATBOT_HYBRID_URL . 'css/echo5-chat-style.css', [], ECHO5_CHATBOT_HYBRID_VERSION);
    wp_enqueue_script('echo5-chatbot-hybrid-js', ECHO5_CHATBOT_HYBRID_URL . 'js/echo5-chat-hybrid.js', ['jquery'], ECHO5_CHATBOT_HYBRID_VERSION, true);
    wp_localize_script('echo5-chatbot-hybrid-js', 'echo5_chatbot_hybrid_data', [
        'backend_url' => $backend_url,
        'openai_api_key' => $openai_api_key,
        'system_prompt' => $system_prompt,
        'faq' => $faq,
        'plugin_url' => ECHO5_CHATBOT_HYBRID_URL
    ]);
}

add_action('wp_footer', 'echo5_chatbot_hybrid_box');
function echo5_chatbot_hybrid_box() {
    ?>
    <div id="echo5-chat-container" class="minimized">
        <div id="echo5-chat-header">Chat with us!</div>
        <div id="echo5-chat-messages"></div>
        <div id="echo5-chat-input-area">
            <input id="echo5-chat-message-input" type="text" placeholder="Type your message..." />
            <button id="echo5-send-message-button">Send</button>
        </div>
    </div>
    <template id="echo5-typing-indicator-template">
        <div class="echo5-typing-indicator">Bot is typing...</div>
    </template>
    <?php
}

add_action('admin_menu', function() {
    add_menu_page(
        'Echo5 Chatbot',
        'Echo5 Chatbot',
        'manage_options',
        'echo5-chatbot-hybrid',
        'echo5_chatbot_hybrid_settings_page',
        'dashicons-format-chat',
        60
    );
    add_submenu_page(
        'echo5-chatbot-hybrid',
        'API Settings',
        'API Settings',
        'manage_options',
        'echo5-chatbot-hybrid-api',
        'echo5_chatbot_hybrid_api_settings_page'
    );
    add_submenu_page(
        'echo5-chatbot-hybrid',
        'AI Training',
        'AI Training',
        'manage_options',
        'echo5-chatbot-hybrid-training',
        'echo5_chatbot_hybrid_training_settings_page'
    );
    add_submenu_page(
        'echo5-chatbot-hybrid',
        'Customize',
        'Customize',
        'manage_options',
        'echo5-chatbot-hybrid-customize',
        'echo5_chatbot_hybrid_customize_settings_page'
    );
});

function echo5_chatbot_hybrid_settings_page() {
    echo '<div class="wrap"><h1>Echo5 Chatbot</h1><p>Welcome to the Echo5 Chatbot admin panel. Use the submenu to configure API and AI Training settings.</p></div>';
}

function echo5_chatbot_hybrid_api_settings_page() {
    ?>
    <div class="wrap">
        <h1>Echo5 Chatbot API Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('echo5_chatbot_hybrid_settings'); ?>
            <?php do_settings_sections('echo5_chatbot_hybrid_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Backend URL</th>
                    <td>
                        <input type="password" id="echo5-chatbot-hybrid-backend-url" name="echo5_chatbot_hybrid_backend_url" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_backend_url', 'https://react-chatbot-99g6.vercel.app/chat')); ?>" size="50" />
                        <button type="button" class="button" id="echo5-test-backend-url">Test Key</button>
                        <span id="echo5-backend-url-test-result" style="margin-left:10px;"></span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">OpenAI API Key</th>
                    <td>
                        <input type="password" id="echo5-chatbot-hybrid-openai-key" name="echo5_chatbot_hybrid_openai_key" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_openai_key', '')); ?>" size="50" />
                        <button type="button" class="button" id="echo5-test-openai-key">Test Key</button>
                        <span id="echo5-openai-key-test-result" style="margin-left:10px;"></span>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var testBtn = document.getElementById('echo5-test-openai-key');
        var toggleBtn = document.getElementById('echo5-toggle-openai-key');
        var keyInput = document.getElementById('echo5-chatbot-hybrid-openai-key');
        var testBackendBtn = document.getElementById('echo5-test-backend-url');
        var backendInput = document.getElementById('echo5-chatbot-hybrid-backend-url');
        if (testBtn) {
            testBtn.addEventListener('click', function() {
                var key = keyInput.value.trim();
                var resultSpan = document.getElementById('echo5-openai-key-test-result');
                resultSpan.textContent = 'Testing...';
                fetch('https://api.openai.com/v1/models', {
                    method: 'GET',
                    headers: { 'Authorization': 'Bearer ' + key }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.data) {
                        resultSpan.textContent = '✅ Valid';
                        resultSpan.style.color = 'green';
                    } else {
                        resultSpan.textContent = '❌ Invalid';
                        resultSpan.style.color = 'red';
                    }
                })
                .catch(() => {
                    resultSpan.textContent = '❌ Invalid or Network Error';
                    resultSpan.style.color = 'red';
                });
            });
        }
        if (testBackendBtn && backendInput) {
            testBackendBtn.addEventListener('click', function() {
                var url = backendInput.value.trim();
                var resultSpan = document.getElementById('echo5-backend-url-test-result');
                resultSpan.textContent = 'Testing...';
                fetch(url, { method: 'OPTIONS' })
                    .then(function(response) {
                        if (response.ok) {
                            resultSpan.textContent = '✅ Server is Connected';
                            resultSpan.style.color = 'green';
                        } else {
                            resultSpan.textContent = '❌ Server is Unreachable (' + response.status + ')';
                            resultSpan.style.color = 'red';
                        }
                    })
                    .catch(function() {
                        resultSpan.textContent = '❌ Unreachable or Network Error';
                        resultSpan.style.color = 'red';
                    });
            });
        }
    });
    </script>
    <?php
}

add_action('admin_init', function() {
    register_setting('echo5_chatbot_hybrid_settings', 'echo5_chatbot_hybrid_system_prompt');
    register_setting('echo5_chatbot_hybrid_settings', 'echo5_chatbot_hybrid_faq');
    // Appearance/Customize settings
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_chat_name');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_header_bg');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_header_text');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_bubble_user');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_bubble_bot');
});

function echo5_chatbot_hybrid_training_settings_page() {
    ?>
    <div class="wrap">
        <h1>Echo5 Chatbot AI Training</h1>
        <form method="post" action="options.php">
            <?php settings_fields('echo5_chatbot_hybrid_settings'); ?>
            <?php do_settings_sections('echo5_chatbot_hybrid_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">System Prompt<br><small>Tell the AI how to behave (e.g. "You are a helpful expert on our products.")</small></th>
                    <td><textarea name="echo5_chatbot_hybrid_system_prompt" rows="3" cols="70"><?php echo esc_textarea(get_option('echo5_chatbot_hybrid_system_prompt', 'You are a helpful expert on our products.')); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom FAQ Knowledge<br><small>Paste your FAQ or product info here. This will be injected into the AI context.</small></th>
                    <td><textarea name="echo5_chatbot_hybrid_faq" rows="8" cols="70"><?php echo esc_textarea(get_option('echo5_chatbot_hybrid_faq', '')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function echo5_chatbot_hybrid_customize_settings_page() {
    ?>
    <div class="wrap">
        <h1>Echo5 Chatbot Customize</h1>
        <form method="post" action="options.php">
            <?php settings_fields('echo5_chatbot_hybrid_customize'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Chat Name</th>
                    <td><input type="text" name="echo5_chatbot_hybrid_chat_name" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_chat_name', 'Chat with us!')); ?>" size="40" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Header Background Color</th>
                    <td><input type="color" name="echo5_chatbot_hybrid_header_bg" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_header_bg', '#2d8cff')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Header Text Color</th>
                    <td><input type="color" name="echo5_chatbot_hybrid_header_text" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_header_text', '#ffffff')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">User Bubble Color</th>
                    <td><input type="color" name="echo5_chatbot_hybrid_bubble_user" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_bubble_user', '#e5e5ea')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Bot Bubble Color</th>
                    <td><input type="color" name="echo5_chatbot_hybrid_bubble_bot" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_bubble_bot', '#f1f0f0')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Secure REST API endpoint for OpenAI API key (admin only)
add_action('rest_api_init', function () {
    register_rest_route('echo5-chatbot/v1', '/openai-key', [
        'methods' => 'GET',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function () {
            $key = get_option('echo5_chatbot_hybrid_openai_key', '');
            if (!$key) {
                return new WP_Error('no_key', 'No API key set', ['status' => 404]);
            }
            return ['openai_key' => $key];
        }
    ]);
});
