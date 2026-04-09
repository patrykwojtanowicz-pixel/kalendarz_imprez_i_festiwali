<?php
if (!defined('ABSPATH')) exit;

function kif_register_post_type(){
    $labels = [
        'name'          => __('Wydarzenia','kalendarz-imprez-i-festiwali'),
        'singular_name' => __('Wydarzenie','kalendarz-imprez-i-festiwali'),
        'add_new'       => __('Dodaj nowe','kalendarz-imprez-i-festiwali'),
        'edit_item'     => __('Edytuj wydarzenie','kalendarz-imprez-i-festiwali'),
        'menu_name'     => __('Wydarzenia','kalendarz-imprez-i-festiwali'),
    ];
    register_post_type('festival_event', [
        'labels'        => $labels,
        'public'        => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => ['title','editor','thumbnail'],
        'show_in_rest'  => true,
        'show_in_menu'  => 'kalendarz-imprez-i-festiwali',
        'rewrite'       => ['slug' => 'wydarzenie'],
    ]);
}
add_action('init','kif_register_post_type');

// === ZINTEGROWANE ZARZĄDZANIE KOLUMNAMI ===
add_filter('manage_festival_event_posts_columns', function($columns){
    $new = [];
    foreach($columns as $key => $label){
        // Całkowicie ignorujemy domyślną, błędną datę WordPressa
        if($key === 'date') continue;

        // Kiedy dojdziemy do tytułu, ustawiamy nasze kolumny w pożądanej kolejności
        if($key === 'title'){
            $new['thumbnail']    = __('Miniatura','kalendarz-imprez-i-festiwali');
            $new['title']        = $label; // Zostawiamy tytuł
            $new['kif_date']     = '📅 Data imprezy';
            $new['kif_type']     = 'Typ imprezy';
            $new['kif_featured'] = '💎 Polecane';
            continue;
        }
        $new[$key] = $label;
    }
    return $new;
});

// === WYPEŁNIENIE DANYCH W KOLUMNACH ===
add_action('manage_festival_event_posts_custom_column', function($column, $post_id){
    // 1. Miniatura
    if($column === 'thumbnail'){
        $thumb = get_the_post_thumbnail($post_id, [80,80]);
        echo $thumb ?: '<span style="color:#999;">—</span>';
    }
    
    // 2. Naprawiona Data Imprezy
    if($column === 'kif_date'){
        $date_raw = get_post_meta($post_id, '_kif_date', true);
        $time_raw = get_post_meta($post_id, '_kif_time', true);

        if ($date_raw) {
            $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
            $evStartRaw = $d1_parts[0];
            $evTime = !empty($time_raw) ? $time_raw : (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '00:00');
            echo '<strong>' . date('d.m.Y', strtotime($evStartRaw)) . '</strong><br>';
            echo '<span style="color:#666;">Start: ' . $evTime . '</span>';
        } else {
            echo '—';
        }
    }

    // 3. Typ imprezy (z ukrytym polem dla Szybkiej Edycji)
    if($column === 'kif_type'){
        $type = get_post_meta($post_id, '_kif_category', true);
        if ($type) {
            echo '<span style="background:#e0f0fa; color:#2271b1; padding:4px 8px; border-radius:12px; font-weight:600; font-size:12px;">' . esc_html($type) . '</span>';
        } else {
            echo '<span style="color:#999; font-style:italic;">Brak typu</span>';
        }
        echo '<div class="hidden kif_inline_type" id="kif_type_' . $post_id . '">' . esc_attr($type) . '</div>';
    }

    // 4. Polecane
    if($column === 'kif_featured'){
        $feat = get_post_meta($post_id, '_kif_featured', true);
        echo $feat ? '<span style="color:#f39c12;font-weight:bold;">✔️ Tak</span>' : '—';
    }
}, 10, 2);

// Ostylowanie miniatury
add_action('admin_head', function(){
    echo '<style>.column-thumbnail{width:90px;text-align:center}</style>';
});

// === SORTOWANIE PO DACIE IMPREZY ===
add_filter('manage_edit-festival_event_sortable_columns', function($columns){
    $columns['kif_date'] = 'kif_date';
    return $columns;
});

add_action('pre_get_posts', function($query){
    if(!is_admin() || !$query->is_main_query()) return;
    
    // Upewniamy się, że sortujemy dla odpowiedniego typu postu
    if($query->get('post_type') === 'festival_event' && $query->get('orderby') === 'kif_date'){
        $query->set('meta_key', '_kif_date');
        $query->set('orderby', 'meta_value');
    }
});
