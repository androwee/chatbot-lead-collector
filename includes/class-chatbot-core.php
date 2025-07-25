<?php

class Chatbot_Core {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ $this, 'render_chatbot_html' ] );
        add_action( 'wp_ajax_save_chatbot_lead', [ $this, 'save_lead_handler' ] );
        add_action( 'wp_ajax_nopriv_save_chatbot_lead', [ $this, 'save_lead_handler' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'handle_csv_export' ] );
    }

    public static function on_activation() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_leads';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name ( id mediumint(9) NOT NULL AUTO_INCREMENT, name tinytext NOT NULL, email varchar(100) NOT NULL, phone varchar(20) NOT NULL, submission_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, PRIMARY KEY  (id) ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function handle_csv_export() {
        if ( ! isset($_GET['action']) || $_GET['action'] !== 'download_csv' ) {
            return;
        }
        if ( ! isset($_GET['_wpnonce']) || ! wp_verify_nonce($_GET['_wpnonce'], 'lcc_download_leads_nonce') ) {
            wp_die('Aksi tidak diizinkan.');
        }
        if ( ! current_user_can('manage_options') ) {
            wp_die('Anda tidak memiliki izin untuk melakukan aksi ini.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_leads';
        $leads = $wpdb->get_results( "SELECT id, name, email, phone, submission_date FROM $table_name ORDER BY id DESC", ARRAY_A );

        $filename = 'chatbot-leads-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['ID', 'Nama', 'Email', 'Telepon', 'Tanggal']);

        if ( ! empty($leads) ) {
            foreach ($leads as $lead) {
                fputcsv($output, $lead);
            }
        }
        
        fclose($output);
        exit();
    }

    private function get_conversation_flow() {
        $convo_texts = get_option('lcc_conversation_texts', [
            'start_message' => 'Halo! Ada yang bisa kami bantu? Silakan pilih salah satu opsi di bawah ini.',
            'option1_text'  => 'Tanya Properti',
            'option2_text'  => 'Jadwalkan Konsultasi',
        ]);

        return [
            'start' => [ 'bot_message' => $convo_texts['start_message'], 'options' => [ [ 'text' => $convo_texts['option1_text'], 'next_step' => 'ask_name' ], [ 'text' => $convo_texts['option2_text'], 'next_step' => 'ask_name' ], ] ],
            'ask_name' => [ 'bot_message' => 'Tentu. Boleh kami tahu nama lengkap Anda?', 'show_input' => 'name' ],
            'ask_email' => [ 'bot_message' => 'Terima kasih, {name}! Selanjutnya, boleh kami tahu alamat email Anda?', 'show_input' => 'email' ],
            'ask_phone' => [ 'bot_message' => 'Luar biasa! Terakhir, berapa nomor WhatsApp Anda yang bisa kami hubungi?', 'show_input' => 'phone' ]
        ];
    }
    
    public function enqueue_assets() {
        $tailwind_settings = get_option('lcc_tailwind_settings', ['enabled' => true, 'url' => 'https://cdn.tailwindcss.com']);
        if (isset($tailwind_settings['enabled']) && $tailwind_settings['enabled']) {
            wp_enqueue_script('tailwindcss', $tailwind_settings['url'], [], null, false);
        }
        
        wp_enqueue_style('lcc-style', LCC_PLUGIN_URL . 'assets/css/chatbot-style.css', [], '4.3.0' );
        wp_enqueue_script('lcc-script', LCC_PLUGIN_URL . 'assets/js/chatbot-script.js', [ 'jquery' ], '4.3.0', true );
        
        $final_content = get_option('lcc_final_content', [ 'message' => 'Terima kasih, {name}! Data Anda sudah kami terima.', 'link_text' => 'Download Brosur', 'link_url' => '', ]);
        wp_localize_script( 'lcc-script', 'chatbot_params', [
            'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'chatbot_nonce' ),
            'conversation' => $this->get_conversation_flow(), 'final_content' => $final_content
        ]);
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'chatbot-leads') === false && strpos($hook, 'chatbot-settings') === false) return;
        wp_enqueue_media();
        wp_enqueue_script('lcc-admin-script', LCC_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], '4.3.0', true);
    }

    public function render_chatbot_html() { include_once LCC_PLUGIN_PATH . 'views/chatbot-view.php'; }

    public function save_lead_handler() {
        check_ajax_referer( 'chatbot_nonce', 'nonce' );
        $name = sanitize_text_field( $_POST['name'] ); $email = sanitize_email( $_POST['email'] ); $phone = sanitize_text_field( $_POST['phone'] );
        if ( empty($name) || !is_email($email) || empty($phone) ) wp_send_json_error(['message' => 'Data tidak lengkap.']);
        global $wpdb; $table_name = $wpdb->prefix . 'chatbot_leads';
        $result = $wpdb->insert( $table_name, [ 'name' => $name, 'email' => $email, 'phone' => $phone, 'submission_date' => current_time( 'mysql' ) ] );
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Gagal menyimpan ke database.']);
        }
    }

    public function add_admin_menu() { add_menu_page( 'Chatbot Leads', 'Chatbot', 'manage_options', 'chatbot-leads', [ $this, 'render_leads_page' ], 'dashicons-format-chat', 25 ); add_submenu_page( 'chatbot-leads', 'Pengaturan', 'Pengaturan', 'manage_options', 'chatbot-settings', [ $this, 'render_settings_page' ] ); }

    public function register_settings() {
        register_setting('lcc_settings_group', 'lcc_bot_avatar');
        register_setting('lcc_settings_group', 'lcc_bubble_logo');
        register_setting('lcc_settings_group', 'lcc_bot_name');
        register_setting('lcc_settings_group', 'lcc_chatbot_color');
        register_setting('lcc_settings_group', 'lcc_conversation_texts');
        register_setting('lcc_settings_group', 'lcc_final_content');
        register_setting('lcc_settings_group', 'lcc_tailwind_settings');

        add_settings_section('lcc_visual_section', 'Pengaturan Visual', null, 'chatbot-settings');
        add_settings_field('lcc_bot_name_field', 'Nama Bot di Header', [ $this, 'render_bot_name_field' ], 'chatbot-settings', 'lcc_visual_section');
        add_settings_field('lcc_chatbot_color_field', 'Warna Utama Chatbot', [ $this, 'render_chatbot_color_field' ], 'chatbot-settings', 'lcc_visual_section');
        add_settings_field('lcc_bot_avatar_field', 'Avatar Bot di Header', [ $this, 'render_avatar_field' ], 'chatbot-settings', 'lcc_visual_section');
        add_settings_field('lcc_bubble_logo_field', 'Logo Bubble Chat', [ $this, 'render_bubble_logo_field' ], 'chatbot-settings', 'lcc_visual_section');

        add_settings_section('lcc_conversation_section', 'Percakapan Awal', null, 'chatbot-settings');
        add_settings_field('lcc_start_message_field', 'Pesan Pembuka', [ $this, 'render_start_message_field' ], 'chatbot-settings', 'lcc_conversation_section');
        add_settings_field('lcc_option1_field', 'Tombol Opsi 1', [ $this, 'render_option1_field' ], 'chatbot-settings', 'lcc_conversation_section');
        add_settings_field('lcc_option2_field', 'Tombol Opsi 2', [ $this, 'render_option2_field' ], 'chatbot-settings', 'lcc_conversation_section');

        add_settings_section('lcc_final_content_section', 'Konten Final', null, 'chatbot-settings');
        add_settings_field('lcc_final_message_field', 'Pesan Terima Kasih', [ $this, 'render_final_message_field' ], 'chatbot-settings', 'lcc_final_content_section');
        add_settings_field('lcc_final_link_text_field', 'Teks Tombol/Link', [ $this, 'render_final_link_text_field' ], 'chatbot-settings', 'lcc_final_content_section');
        add_settings_field('lcc_final_link_url_field', 'URL File/Halaman', [ $this, 'render_final_link_url_field' ], 'chatbot-settings', 'lcc_final_content_section');

        add_settings_section('lcc_advanced_section', 'Pengaturan Lanjutan', null, 'chatbot-settings');
        add_settings_field('lcc_tailwind_field', 'Tailwind CSS', [ $this, 'render_tailwind_field' ], 'chatbot-settings', 'lcc_advanced_section');
    }
    
    public function render_bot_name_field() {
        $value = get_option('lcc_bot_name', 'Butuh Bantuan?');
        echo '<input type="text" name="lcc_bot_name" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Nama ini akan muncul di header jendela chat.</p>';
    }

    public function render_chatbot_color_field() {
        $value = get_option('lcc_chatbot_color', '#2563EB'); // Default: Tailwind blue-600
        echo '<input type="color" name="lcc_chatbot_color" value="' . esc_attr($value) . '">';
        echo '<p class="description">Pilih warna utama untuk bubble chat, header, dan tombol.</p>';
    }

    public function render_avatar_field() { echo $this->render_uploader_field('lcc_bot_avatar'); }
    public function render_bubble_logo_field() { echo $this->render_uploader_field('lcc_bubble_logo'); }
    private function render_uploader_field($option_name) {
        $image_url = get_option($option_name);
        return '<img id="'.$option_name.'_preview" src="' . esc_url($image_url) . '" style="width:60px; height:60px; vertical-align:middle; object-fit:cover; border-radius:50%; border:1px solid #ddd; ' . (empty($image_url) ? 'display:none;' : '') . '"> <input type="hidden" id="'.$option_name.'_url" name="'.$option_name.'" value="' . esc_attr($image_url) . '"> <button type="button" class="button lcc-upload-button" data-target-id="'.$option_name.'">Pilih/Unggah Gambar</button> <button type="button" class="button button-secondary lcc-remove-button" data-target-id="'.$option_name.'">Hapus</button>';
    }

    public function render_start_message_field() {
        $options = get_option('lcc_conversation_texts');
        $value = isset($options['start_message']) ? $options['start_message'] : 'Halo! Ada yang bisa kami bantu?';
        echo '<textarea name="lcc_conversation_texts[start_message]" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
    }
    public function render_option1_field() {
        $options = get_option('lcc_conversation_texts');
        $value = isset($options['option1_text']) ? $options['option1_text'] : 'Tanya Properti';
        echo '<input type="text" name="lcc_conversation_texts[option1_text]" value="' . esc_attr($value) . '" class="regular-text">';
    }
    public function render_option2_field() {
        $options = get_option('lcc_conversation_texts');
        $value = isset($options['option2_text']) ? $options['option2_text'] : 'Jadwalkan Konsultasi';
        echo '<input type="text" name="lcc_conversation_texts[option2_text]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_tailwind_field() {
        $options = get_option('lcc_tailwind_settings', ['enabled' => true, 'url' => 'https://cdn.tailwindcss.com']);
        $enabled = isset($options['enabled']) ? $options['enabled'] : false;
        $url = isset($options['url']) ? $options['url'] : 'https://cdn.tailwindcss.com';
        echo '<label><input type="checkbox" name="lcc_tailwind_settings[enabled]" value="1" ' . checked( $enabled, true, false ) . '> Aktifkan Tailwind CSS</label>';
        echo '<p class="description">Gunakan Tailwind CSS dari CDN. Nonaktifkan jika tema Anda sudah menggunakan Tailwind.</p>';
        echo '<br><label>URL CDN:<br><input type="url" name="lcc_tailwind_settings[url]" value="' . esc_attr($url) . '" class="large-text"></label>';
    }

    public function render_final_message_field() { $options = get_option('lcc_final_content'); $value = isset($options['message']) ? $options['message'] : ''; echo '<textarea name="lcc_final_content[message]" rows="4" class="large-text">' . esc_textarea($value) . '</textarea><p class="description">Gunakan {name} untuk menyapa pengunjung dengan namanya.</p>'; }
    public function render_final_link_text_field() { $options = get_option('lcc_final_content'); $value = isset($options['link_text']) ? $options['link_text'] : ''; echo '<input type="text" name="lcc_final_content[link_text]" value="' . esc_attr($value) . '" class="regular-text" placeholder="Contoh: Download Brosur">'; }
    public function render_final_link_url_field() { $options = get_option('lcc_final_content'); $value = isset($options['link_url']) ? $options['link_url'] : ''; echo '<input type="url" name="lcc_final_content[link_url]" value="' . esc_attr($value) . '" class="large-text" placeholder="https://..."><p class="description">Masukkan URL lengkap ke file atau halaman tujuan.</p>'; }
    
    public function render_leads_page() {
        global $wpdb; $table_name = $wpdb->prefix . 'chatbot_leads';
        $leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY submission_date DESC" );
        
        echo '<div class="wrap">';
        echo '<h1>Data Prospek dari Chatbot</h1>';
        
        $download_url = add_query_arg([
            'action' => 'download_csv',
            '_wpnonce' => wp_create_nonce('lcc_download_leads_nonce')
        ]);
        echo '<a href="' . esc_url($download_url) . '" class="button button-primary" style="margin: 0 0 15px;">Download Semua Prospek (CSV)</a>';

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Tanggal</th></tr></thead><tbody>';
        if ($leads) { 
            foreach ($leads as $lead) {
                echo '<tr><td>' . esc_html($lead->name) . '</td><td>' . esc_html($lead->email) . '</td><td>' . esc_html($lead->phone) . '</td><td>' . esc_html($lead->submission_date) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="4">Belum ada data yang masuk.</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    
    public function render_settings_page() {
        echo '<div class="wrap"><h1>Pengaturan Chatbot</h1><form method="post" action="options.php">';
        settings_fields('lcc_settings_group');
        do_settings_sections('chatbot-settings');
        submit_button();
        echo '</form></div>';
    }
}