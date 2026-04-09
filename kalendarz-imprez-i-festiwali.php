<?php
/*
Plugin Name: Kalendarz imprez i festiwali
*/
if (!defined('ABSPATH')) exit;

define('KIF_VER', '2.0');
define('KIF_DIR', plugin_dir_path(__FILE__));
define('KIF_URL', plugin_dir_url(__FILE__));

require_once KIF_DIR.'includes/post-type.php';
require_once KIF_DIR.'includes/meta-boxes.php';
require_once KIF_DIR.'includes/helpers.php';
require_once KIF_DIR.'includes/ajax.php';
require_once KIF_DIR.'includes/shortcode.php';
require_once KIF_DIR.'admin/settings-page.php';
require_once KIF_DIR.'includes/rest-api.php';

function kif_enqueue_assets(){
    $settings = get_option('kif_settings', ['accent_color'=>'#ff7a1c','price_step'=>10,'grid_columns'=>'auto','dark_mode'=>1]);
    wp_enqueue_style('kif-style', KIF_URL.'assets/css/festival-calendar.css', [], KIF_VER);
    $accent = !empty($settings['accent_color']) ? $settings['accent_color'] : '#ff7a1c';
    wp_add_inline_style('kif-style', ':root{--fcp-accent: '.$accent.';}');

    wp_enqueue_script('kif-script', KIF_URL.'assets/js/festival-calendar.js', ['jquery'], KIF_VER, true);
    wp_localize_script('kif-script', 'kifAjax', [
        'ajax_url'  => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('kif_nonce'),
        'copyOk'    => __('Skopiowano link!','kalendarz-imprez-i-festiwali'),
        'saveOk'    => __('Opis zaktualizowany.','kalendarz-imprez-i-festiwali'),
        'saveErr'   => __('Błąd zapisu opisu.','kalendarz-imprez-i-festiwali'),
        'priceStep' => intval($settings['price_step'] ?? 10),
    ]);
}
add_action('wp_enqueue_scripts','kif_enqueue_assets');

function kif_admin_assets($hook){
    if (strpos($hook, 'kalendarz-imprez-i-festiwali') !== false){
        wp_enqueue_style('kif-admin', KIF_URL.'admin/admin-styles.css', [], KIF_VER);
    }
}
add_action('admin_enqueue_scripts','kif_admin_assets');

register_activation_hook(__FILE__, function(){ kif_register_post_type(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

function kif_custom_single_template($single_template) {
    global $post;
    if ($post->post_type === 'festival_event') {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-festival_event.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $single_template;
}
add_filter('single_template', 'kif_custom_single_template');

add_action('wp_enqueue_scripts', function() {
    if (is_singular('festival_event')) {
        wp_enqueue_style('festival_calendar_style', plugin_dir_url(__FILE__) . 'assets/css/festival-calendar.css', [], KIF_VER);
    }
});

// ==========================================
// === POMOCNIK: KRAJE, FLAGI, ISO ==========
// ==========================================
function kif_get_country_data($country_name) {
    $map = [
        'Polska' => ['iso' => 'PL', 'flag' => '🇵🇱'],
        'Niemcy' => ['iso' => 'DE', 'flag' => '🇩🇪'],
        'Holandia' => ['iso' => 'NL', 'flag' => '🇳🇱'],
        'Belgia' => ['iso' => 'BE', 'flag' => '🇧🇪'],
        'Czechy' => ['iso' => 'CZ', 'flag' => '🇨🇿'],
        'Słowacja' => ['iso' => 'SK', 'flag' => '🇸🇰'],
        'Chorwacja' => ['iso' => 'HR', 'flag' => '🇭🇷'],
        'Hiszpania' => ['iso' => 'ES', 'flag' => '🇪🇸'],
        'Francja' => ['iso' => 'FR', 'flag' => '🇫🇷'],
        'Wielka Brytania' => ['iso' => 'GB', 'flag' => '🇬🇧'],
        'Włochy' => ['iso' => 'IT', 'flag' => '🇮🇹'],
        'Węgry' => ['iso' => 'HU', 'flag' => '🇭🇺'],
        'Rumunia' => ['iso' => 'RO', 'flag' => '🇷🇴'],
        'USA' => ['iso' => 'US', 'flag' => '🇺🇸']
    ];
    return isset($map[$country_name]) ? $map[$country_name] : ['iso' => 'PL', 'flag' => '🌍'];
}


// ==========================================
// === ZNACZNIKI SCHEMA.ORG DLA WYDARZEŃ ====
// ==========================================

add_action('wp_head', 'kif_add_event_schema');
function kif_add_event_schema() {
    if (!is_singular('festival_event')) return;

    global $post;
    $id = $post->ID;

    $date_raw     = get_post_meta($id, '_kif_date', true);
    $time_raw     = get_post_meta($id, '_kif_time', true);
    $date_end_raw = get_post_meta($id, '_kif_date_end', true);
    $country      = get_post_meta($id, '_kif_country', true) ?: 'Polska';
    $city         = get_post_meta($id, '_kif_city', true);
    $venue        = get_post_meta($id, '_kif_venue', true);
    $price        = get_post_meta($id, '_kif_price', true);
    $currency     = get_post_meta($id, '_kif_currency', true) ?: 'PLN';
    $ticket_url   = get_post_meta($id, '_kif_ticket', true);
    $on_sale      = get_post_meta($id, '_kif_on_sale', true);
    $is_paid      = get_post_meta($id, '_kif_is_paid', true);
    $headliners   = get_post_meta($id, '_kif_headliners', true);
    $thumb_url    = get_the_post_thumbnail_url($id, 'full');
    
    $country_data = kif_get_country_data($country);
    
    $desc = get_post_meta($id, '_kif_custom_desc', true);
    if (empty($desc)) {
        $desc = wp_trim_words($post->post_content, 30);
    }

    $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
    $start_date = $d1_parts[0];
    $start_time = !empty($time_raw) ? $time_raw : (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '20:00');
    
    if (!$start_date) return;

    $start_iso = $start_date . 'T' . $start_time . ':00+01:00'; 
    
    $d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
    $end_date = !empty($d2_parts[0]) ? $d2_parts[0] : $start_date;
    $end_iso = $end_date . 'T23:59:00+01:00';

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'MusicEvent',
        'name'     => get_the_title($id),
        'startDate'=> $start_iso,
        'endDate'  => $end_iso,
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'location' => [
            '@type' => 'Place',
            'name'  => $venue ?: 'TBA',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $city ?: 'TBA',
                'addressCountry' => $country_data['iso'] // Dynamiczne pobieranie kodu ISO Kraju dla Google
            ]
        ]
    ];

    if ($desc) {
        $schema['description'] = strip_tags($desc);
    }

    if ($thumb_url) {
        $schema['image'] = [$thumb_url];
    }

    if ($is_paid === 'tak' && !empty($price)) {
        $numeric_price = preg_replace('/[^0-9.,]/', '', $price); 
        $numeric_price = str_replace(',', '.', $numeric_price);
        
        if (!empty($numeric_price)) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'url'   => $ticket_url ?: get_permalink($id),
                'price' => $numeric_price,
                'priceCurrency' => $currency, // Dynamiczna waluta
                'availability'  => ($on_sale === 'tak') ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'validFrom'     => date('Y-m-d\TH:i:sP')
            ];
        }
    }

    if (!empty($headliners)) {
        $performers = array_map('trim', explode(',', $headliners));
        $schema['performer'] = [];
        foreach ($performers as $artist) {
            $schema['performer'][] = [
                '@type' => 'PerformingGroup',
                'name'  => $artist
            ];
        }
    }

    echo "\n";
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

// ==========================================
// === FUNKCJA: PODOBNE IMPREZY (3 KOLUMNY) ==
// ==========================================

function kif_display_related_events($current_id) {
    $current_type  = get_post_meta($current_id, '_kif_category', true);
    $current_genre = get_post_meta($current_id, '_kif_genre', true);
    $current_price = get_post_meta($current_id, '_kif_price', true);
    
    $current_genres_arr = array_filter(array_map('trim', preg_split('/[,;]+/', strtolower($current_genre))));
    $current_price_val = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $current_price)));

    $today = date('Y-m-d');

    $args = [
        'post_type'      => 'festival_event',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'post__not_in'   => [$current_id],
        'meta_key'       => '_kif_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => '_kif_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE'
            ]
        ]
    ];

    $upcoming = get_posts($args);
    $scored_events = [];

    foreach ($upcoming as $ev) {
        $score = 0;
        $ev_type  = get_post_meta($ev->ID, '_kif_category', true);
        $ev_genre = get_post_meta($ev->ID, '_kif_genre', true);
        $ev_price = get_post_meta($ev->ID, '_kif_price', true);
        
        if (!empty($current_type) && $ev_type === $current_type) $score += 30;
        
        if (!empty($ev_genre)) {
            $ev_genres_arr = array_filter(array_map('trim', preg_split('/[,;]+/', strtolower($ev_genre))));
            $intersect = array_intersect($current_genres_arr, $ev_genres_arr);
            if (count($intersect) > 0) $score += 20 * count($intersect); 
        }
        
        $ev_price_val = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $ev_price)));
        if ($current_price_val > 0 && $ev_price_val > 0) {
            if (abs($current_price_val - $ev_price_val) <= 40) $score += 10;
        } elseif (empty($current_price) && empty($ev_price)) {
            $score += 10;
        }

        if ($score > 0) {
            $scored_events[] = [
                'id'    => $ev->ID,
                'score' => $score,
                'date'  => get_post_meta($ev->ID, '_kif_date', true)
            ];
        }
    }

    if (empty($scored_events)) return;

    usort($scored_events, function($a, $b) {
        if ($a['score'] === $b['score']) return strcmp($a['date'], $b['date']);
        return $b['score'] - $a['score'];
    });

    $top_events = array_slice($scored_events, 0, 3);
    ?>

    <div class="kif-related-events" style="margin-top: 60px; margin-bottom: 40px; border-top: 2px solid #f0f0f1; padding-top: 40px;">
        <h3 style="margin-bottom: 25px; font-size: 1.5rem; font-weight: bold; color: #333;">🔥 Sprawdź również podobne wydarzenia:</h3>
        
        <div class="kif-list-related">
            
            <?php foreach ($top_events as $item) : 
                $rel_id         = $item['id'];
                $rel_title      = get_the_title($rel_id);
                $rel_city       = get_post_meta($rel_id, '_kif_city', true);
                
                $rel_date_start = get_post_meta($rel_id, '_kif_date', true);
                $rel_date_end   = get_post_meta($rel_id, '_kif_date_end', true);
                
                $d1_p = explode('T', str_replace(' ', 'T', $rel_date_start));
                $d2_p = explode('T', str_replace(' ', 'T', $rel_date_end));
                
                $s = $d1_p[0] ? date('d.m.Y', strtotime($d1_p[0])) : '';
                $e = $d2_p[0] ? date('d.m.Y', strtotime($d2_p[0])) : '';
                
                if ($e && $e !== $s) {
                    $display_date = date('d.m', strtotime($s)) . ' - ' . $e;
                } else {
                    $display_date = $s;
                }
                
                $rel_thumb    = get_the_post_thumbnail_url($rel_id, 'medium_large');
                $rel_link     = get_permalink($rel_id);
            ?>
                <article class="kif-card kif-event-card" style="display: flex; flex-direction: column; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; text-decoration: none; color: inherit;">
                    <?php if($rel_thumb): ?>
                        <div style="position: relative !important; width: 100% !important; aspect-ratio: 1.91 / 1 !important; background: #000 !important; overflow: hidden !important; border-radius: 12px 12px 0 0 !important; padding: 0 !important; margin: 0 !important; display: block !important; z-index: 1 !important;">
                            <div style="position: absolute !important; top: -10% !important; left: -10% !important; right: -10% !important; bottom: -10% !important; background-image: url('<?php echo esc_url($rel_thumb); ?>') !important; background-size: cover !important; background-position: center !important; filter: blur(20px) brightness(0.4) !important; z-index: 2 !important;"></div>
                            <div style="position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-image: url('<?php echo esc_url($rel_thumb); ?>') !important; background-size: contain !important; background-position: center !important; background-repeat: no-repeat !important; z-index: 3 !important; filter: none !important; opacity: 1 !important;"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="kif-card-body" style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h4 style="margin: 0 0 10px 0; font-size: 1.1rem; line-height: 1.3;"><?php echo esc_html($rel_title); ?></h4>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 20px; line-height: 1.5;">
                            📅 <?php echo esc_html($display_date); ?><br>
                            📍 <?php echo esc_html($rel_city ?: 'Brak lokalizacji'); ?>
                        </div>
                        <a href="<?php echo esc_url($rel_link); ?>" class="kif-btn" style="margin-top: auto; text-align: center; display: block; text-decoration: none; background-color: var(--fcp-accent, #ff7a1c); color: #fff; padding: 10px 15px; border-radius: 6px; font-weight: 600;">Szczegóły</a>
                    </div>
                </article>
            <?php endforeach; ?>
            
        </div>
    </div>
    
    <style>
        .kif-list-related { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 20px; 
        }
        
        .kif-related-events .kif-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; 
        }

        @media (max-width: 1024px) { 
            .kif-list-related { 
                grid-template-columns: repeat(2, 1fr); 
            } 
        }

        @media (max-width: 768px) {
            .kif-list-related { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
    <?php
}

// ==========================================
// === GENERATOR PLIKÓW iCal / Outlook (.ics) ==
// ==========================================
add_action('template_redirect', 'kif_generate_ics_file');
function kif_generate_ics_file() {
    if (isset($_GET['kif_ics']) && is_numeric($_GET['kif_ics'])) {
        $id = intval($_GET['kif_ics']);
        if (get_post_type($id) === 'festival_event') {
            $title = get_the_title($id);
            $date_raw = get_post_meta($id, '_kif_date', true);
            $time_raw = get_post_meta($id, '_kif_time', true);
            $cal_end_date = get_post_meta($id, '_kif_cal_end_date', true);
            $cal_end_time = get_post_meta($id, '_kif_cal_end_time', true);
            $date_end_raw = get_post_meta($id, '_kif_date_end', true);
            
            $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
            $evStartRaw = $d1_parts[0];
            $evTime = $time_raw ?: (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '');
            
            $d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
            $evEndRaw = $d2_parts[0];

            if (!$evStartRaw) return;

            $city = get_post_meta($id, '_kif_city', true);
            $venue = get_post_meta($id, '_kif_venue', true);
            $where = implode(', ', array_filter([$venue, $city]));
            $permalink = get_permalink($id);

            try {
                $icsStartObj = new DateTime($evStartRaw . ' ' . ($evTime ?: '00:00'));
                $icsStart = $icsStartObj->format('Ymd\THis');

                $exportEndDate = $cal_end_date ?: ($evEndRaw ?: $evStartRaw);
                $exportEndTime = $cal_end_time ?: '23:59';
                $icsEndObj = new DateTime($exportEndDate . ' ' . $exportEndTime);
                $icsEnd = $icsEndObj->format('Ymd\THis');

                $icalData  = "BEGIN:VCALENDAR\r\n";
                $icalData .= "VERSION:2.0\r\n";
                $icalData .= "PRODID:-//Shining Beats//Kalendarz Imprez//PL\r\n";
                $icalData .= "CALSCALE:GREGORIAN\r\n";
                $icalData .= "BEGIN:VEVENT\r\n";
                $icalData .= "UID:" . md5($id . $icsStart) . "@shiningbeats.pl\r\n";
                $icalData .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
                $icalData .= "SUMMARY:" . $title . "\r\n";
                $icalData .= "DTSTART;TZID=Europe/Warsaw:" . $icsStart . "\r\n";
                $icalData .= "DTEND;TZID=Europe/Warsaw:" . $icsEnd . "\r\n";
                $icalData .= "LOCATION:" . $where . "\r\n";
                $icalData .= "DESCRIPTION:Więcej informacji: " . $permalink . "\r\n";
                $icalData .= "URL:" . $permalink . "\r\n";
                $icalData .= "END:VEVENT\r\n";
                $icalData .= "END:VCALENDAR\r\n";

                $filename = sanitize_title($title) . '.ics';
                
                header('Content-Type: text/calendar; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                
                echo $icalData;
                exit;
            } catch (Exception $e) {}
        }
    }
}

// ==========================================
// === MODUŁ ARCHIWUM ([kif_archiwum]) ======
// ==========================================

add_shortcode('kif_archiwum', 'kif_archive_shortcode');
function kif_archive_shortcode($atts) {
    $today = date('Y-m-d');
    
    $args = [
        'post_type'      => 'festival_event',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'meta_key'       => '_kif_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => '_kif_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE'
            ]
        ]
    ];

    $archive_query = new WP_Query($args);

    if (!$archive_query->have_posts()) {
        return '<p style="text-align:center; padding: 40px;">Brak minionych wydarzeń w archiwum.</p>';
    }

    ob_start();
    ?>
    <div class="kif-archive-wrapper">
        <div class="kif-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            
            <?php while ($archive_query->have_posts()) : $archive_query->the_post();
                $id       = get_the_ID();
                $title    = get_the_title();
                $city     = get_post_meta($id, '_kif_city', true);
                $date_raw = get_post_meta($id, '_kif_date', true);
                
                $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
                $date_fmt = $d1_parts[0] ? date('d.m.Y', strtotime($d1_parts[0])) : '';
                
                $thumb    = get_the_post_thumbnail_url($id, 'medium_large');
                $link     = get_permalink($id);
            ?>
                <article class="kif-card kif-archive-card" style="display: flex; flex-direction: column; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s; text-decoration: none; color: inherit; position: relative; opacity: 0.85;">
                    
                    <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: #fff; padding: 6px 12px; font-size: 0.75rem; font-weight: bold; border-radius: 6px; z-index: 3; letter-spacing: 0.5px;">
                        WYDARZENIE ZAKOŃCZONE
                    </div>

                    <?php if($thumb): ?>
                        <div class="kif-archive-thumb-pancerny" style="filter: grayscale(80%); transition: filter 0.3s; position: relative !important; width: 100% !important; aspect-ratio: 1.91 / 1 !important; background: #000 !important; overflow: hidden !important; border-radius: 0; padding: 0 !important; margin: 0 !important; display: block !important; z-index: 1 !important;">
                            <div style="position: absolute !important; top: -10% !important; left: -10% !important; right: -10% !important; bottom: -10% !important; background-image: url('<?php echo esc_url($thumb); ?>') !important; background-size: cover !important; background-position: center !important; filter: blur(20px) brightness(0.4) !important; z-index: 2 !important;"></div>
                            <div style="position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-image: url('<?php echo esc_url($thumb); ?>') !important; background-size: contain !important; background-position: center !important; background-repeat: no-repeat !important; z-index: 3 !important; filter: none !important; opacity: 1 !important;"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="kif-card-body" style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h4 style="margin: 0 0 10px 0; font-size: 1.1rem; line-height: 1.3; color: #555;"><?php echo esc_html($title); ?></h4>
                        <div style="font-size: 0.9rem; color: #888; margin-bottom: 20px; line-height: 1.5;">
                            📅 <?php echo esc_html($date_fmt); ?><br>
                            📍 <?php echo esc_html($city ?: 'Brak podanej lokalizacji'); ?>
                        </div>
                        <a href="<?php echo esc_url($link); ?>" class="kif-btn" style="margin-top: auto; text-align: center; display: block; text-decoration: none; background-color: #ddd; color: #555; padding: 10px 15px; border-radius: 6px; font-weight: 600; transition: background 0.2s;">Przypomnij sobie line-up</a>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
            
        </div>
    </div>

    <style>
        .kif-archive-card:hover { 
            transform: translateY(-5px); 
            opacity: 1 !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        }
        .kif-archive-card:hover .kif-archive-thumb-pancerny {
            filter: grayscale(0%) !important;
        }
        .kif-archive-card:hover .kif-btn {
            background-color: var(--fcp-accent, #ff7a1c) !important;
            color: #fff !important;
        }
    </style>
    <?php
    return ob_get_clean();
}
