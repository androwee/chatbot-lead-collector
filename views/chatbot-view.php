<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

$avatar_url = get_option('lcc_bot_avatar');
$bubble_logo_url = get_option('lcc_bubble_logo');
$bot_name = get_option('lcc_bot_name', 'Butuh Bantuan?');
$main_color = get_option('lcc_chatbot_color', '#2563EB');
?>

<style>
    :root {
        --lcc-main-color: <?php echo esc_attr($main_color); ?>;
    }
    #lcc-chat-toggle,
    .lcc-chat-header,
    #lcc-lead-form .lcc-submit-button,
    .lcc-chat-body .lcc-user-message {
        background-color: var(--lcc-main-color);
    }
    .lcc-option-button {
        color: var(--lcc-main-color);
        border: 1px solid var(--lcc-main-color);
    }
    .lcc-option-button:hover {
        background-color: var(--lcc-main-color);
        color: #ffffff;
    }
    #lcc-input-field:focus {
        border-color: var(--lcc-main-color) !important;
        box-shadow: 0 0 0 2px var(--lcc-main-color) !important;
    }
</style>

<div id="lcc-chat-container" class="fixed bottom-5 right-5 z-[99999]">
    <!-- Tombol Chat Bubble Baru -->
    <div id="lcc-chat-toggle" class="lcc-chat-bubble w-auto h-auto px-5 py-3 rounded-full flex items-center justify-center cursor-pointer shadow-lg transform hover:scale-105 transition-transform">
        <span class="text-white mr-3 font-semibold"><?php echo esc_html($bot_name); ?></span>
        <?php if ($bubble_logo_url): ?>
            <img src="<?php echo esc_url($bubble_logo_url); ?>" alt="Chat" class="w-8 h-8 rounded-full object-cover">
        <?php else: ?>
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <?php endif; ?>
    </div>

    <div id="lcc-chat-window" style="display:none;" class="w-[350px] h-[500px] bg-white rounded-xl shadow-2xl flex flex-col">
        <div class="lcc-chat-header text-white p-4 rounded-t-xl flex justify-between items-center flex-shrink-0">
            <div class="flex items-center">
                <?php if ($avatar_url): ?>
                    <img style="width: 40px; height: 40px;" src="<?php echo esc_url($avatar_url); ?>" alt="Bot Avatar" class="w-10 h-10 rounded-full mr-3 border-2 border-white object-cover">
                <?php endif; ?>
                <div>
                    <strong class="font-bold"><?php echo esc_html($bot_name); ?></strong>
                    <p class="text-xs opacity-80">Online</p>
                </div>
            </div>
            <div id="lcc-close-chat" class="cursor-pointer p-1 rounded-full hover:bg-white/20 transition-colors">
                <svg style="width:20px; height:20px;" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </div>
        </div>
        <div class="lcc-chat-body p-4 flex-grow overflow-y-auto space-y-4"></div>
        <div class="p-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
            <div id="lcc-options-container" class="flex flex-col space-y-2"></div>
            <form id="lcc-lead-form" class="flex space-x-2 items-center">
                <input type="text" id="lcc-input-field" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none" placeholder="Ketik jawaban...">
                <button type="submit" class="lcc-submit-button w-10 h-10 flex-shrink-0 text-white rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 0011 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                </button>
            </form>
        </div>
    </div>
</div>