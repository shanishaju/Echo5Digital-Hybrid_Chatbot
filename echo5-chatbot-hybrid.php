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
    $backend_url = get_option('echo5_chatbot_hybrid_backend_url', '');
    $openai_api_key = get_option('echo5_chatbot_hybrid_openai_key', '');
    $system_prompt = get_option('echo5_chatbot_hybrid_system_prompt', 'Hi! I’m Echo5 Digital’s expert virtual assistant. How can I help you today?');
    $faq = get_option('echo5_chatbot_hybrid_faq', '');
    $typing_color = get_option('echo5_chatbot_hybrid_typing_color', '#2d8cff');
    $typing_speed = get_option('echo5_chatbot_hybrid_typing_speed', '1');
    $bot_name = get_option('echo5_chatbot_hybrid_bot_name', 'Bot');
    $node_static_url = 'https://static-files-chi.vercel.app/'; // <-- CHANGE THIS to your Node server static URL
    wp_enqueue_style('echo5-chatbot-hybrid-style', $node_static_url . 'echo5-chat-style.css', [], ECHO5_CHATBOT_HYBRID_VERSION);
    wp_enqueue_script('echo5-chatbot-hybrid-js', $node_static_url . 'echo5-chat-hybrid.js', ['jquery'], ECHO5_CHATBOT_HYBRID_VERSION, true);
    wp_localize_script('echo5-chatbot-hybrid-js', 'echo5_chatbot_hybrid_data', [
        'backend_url' => $backend_url,
        'openai_api_key' => $openai_api_key,
        'system_prompt' => $system_prompt,
        'faq' => $faq,
        'plugin_url' => ECHO5_CHATBOT_HYBRID_URL,
        'typing_color' => $typing_color,
        'typing_speed' => $typing_speed,
        'bot_name' => $bot_name
    ]);
}

add_action('wp_footer', 'echo5_chatbot_hybrid_box');
function echo5_chatbot_hybrid_box() {
    ?>
    <div id="echo5-chat-container" class="minimized">
        <div id="echo5-chat-header">Chat with us!
            <button id="echo5-minimize-btn" style="float:right; background:none; border:none; font-size:18px; cursor:pointer; color:#fff; margin-left:10px;" title="Minimize">&minus;</button>
        </div>
        <div id="echo5-chat-messages"></div>
        <div id="echo5-chat-input-area">
            <input id="echo5-chat-message-input" type="text" placeholder="Type your message..." />
            <button id="echo5-send-message-button">Send</button>
        </div>
    </div>
    <template id="echo5-typing-indicator-template">
        <div class="echo5-typing-indicator">
            <span class="echo5-typing-dots">
                <span class="echo5-typing-dot"></span>
                <span class="echo5-typing-dot"></span>
                <span class="echo5-typing-dot"></span>
            </span>
        </div>
    </template>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var chatContainer = document.getElementById('echo5-chat-container');
            if (chatContainer && chatContainer.classList.contains('minimized')) {
                chatContainer.classList.remove('minimized');
                // Greet when auto-opened
                greetEcho5Chat();
            }
        }, 5000); // 5 seconds

        // Greet when manually opened (if minimized class is removed by user interaction)
        var chatHeader = document.getElementById('echo5-chat-header');
        if (chatHeader) {
            chatHeader.addEventListener('click', function(e) {
                // Prevent minimize button from triggering maximize
                if (e.target && e.target.id === 'echo5-minimize-btn') return;
                var chatContainer = document.getElementById('echo5-chat-container');
                if (chatContainer && chatContainer.classList.contains('minimized')) {
                    chatContainer.classList.remove('minimized');
                    greetEcho5Chat();
                }
            });
        }

        // Minimize button logic
        var minimizeBtn = document.getElementById('echo5-minimize-btn');
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var chatContainer = document.getElementById('echo5-chat-container');
                if (chatContainer && !chatContainer.classList.contains('minimized')) {
                    chatContainer.classList.add('minimized');
                }
            });
        }

        function greetEcho5Chat() {
            var messages = document.getElementById('echo5-chat-messages');
            if (!messages) return;
            // Prevent duplicate greetings
            if (messages.querySelector('.echo5-greeting')) return;
            var greetDiv = document.createElement('div');
            greetDiv.className = 'echo5-chat-bubble echo5-chat-bubble-bot echo5-greeting';
            greetDiv.textContent = window.echo5_chatbot_hybrid_data && window.echo5_chatbot_hybrid_data.system_prompt ? window.echo5_chatbot_hybrid_data.system_prompt : 'Hi! I’m Echo5 Digital’s expert virtual assistant. How can I help you today?';
            messages.appendChild(greetDiv);
            messages.scrollTop = messages.scrollHeight;
        }
    });
    </script>
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
            <?php settings_fields('echo5_chatbot_hybrid_api'); ?>
            <?php do_settings_sections('echo5_chatbot_hybrid_api'); ?>
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
        var toggleBackendBtn = document.getElementById('echo5-toggle-backend-url');
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
    // API settings in their own group
    register_setting('echo5_chatbot_hybrid_api', 'echo5_chatbot_hybrid_openai_key');
    register_setting('echo5_chatbot_hybrid_api', 'echo5_chatbot_hybrid_backend_url');
    // Training/FAQ settings
    register_setting('echo5_chatbot_hybrid_settings', 'echo5_chatbot_hybrid_system_prompt');
    register_setting('echo5_chatbot_hybrid_settings', 'echo5_chatbot_hybrid_faq');
    // Appearance/Customize settings
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_chat_name');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_header_bg');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_header_text');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_bubble_user');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_bubble_bot');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_typing_color');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_typing_speed');
    register_setting('echo5_chatbot_hybrid_customize', 'echo5_chatbot_hybrid_bot_name');
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
                    <td><textarea name="echo5_chatbot_hybrid_system_prompt" rows="3" cols="70"><?php echo esc_textarea(get_option('echo5_chatbot_hybrid_system_prompt', 'Hi! I’m Echo5 Digital’s expert virtual assistant. How can I help you today?')); ?></textarea></td>
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
                <tr valign="top">
                    <th scope="row">Typing Indicator Color</th>
                    <td><input type="color" name="echo5_chatbot_hybrid_typing_color" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_typing_color', '#2d8cff')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Typing Animation Speed (seconds)</th>
                    <td><input type="number" name="echo5_chatbot_hybrid_typing_speed" min="0.2" step="0.1" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_typing_speed', '1')); ?>" /> <small>e.g. 1 = normal, 0.5 = fast, 2 = slow</small></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Bot Display Name<br><small>The name shown for the AI in chat bubbles (e.g. "EchoBot", "Support AI").</small></th>
                    <td><input type="text" name="echo5_chatbot_hybrid_bot_name" value="<?php echo esc_attr(get_option('echo5_chatbot_hybrid_bot_name', 'Bot')); ?>" size="40" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('wp_footer', 'echo5_chatbot_custom_minimize_style');
function echo5_chatbot_custom_minimize_style() {
    $header_bg = get_option('echo5_chatbot_hybrid_header_bg', '#2d8cff');
    echo '<style>
        #echo5-chat-container.minimized #echo5-minimize-btn {
            display: none;
        }
        #echo5-chat-header {
            background: ' . esc_attr($header_bg) . ' !important;
        }
    </style>';
}
