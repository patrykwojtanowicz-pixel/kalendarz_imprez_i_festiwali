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

/** Kolumna miniatury w liście wydarzeń (admin) */
add_filter('manage_festival_event_posts_columns', function($cols){
    $new = [];
    foreach($cols as $k=>$v){
        if($k==='title') $new['thumbnail'] = __('Miniatura','kalendarz-imprez-i-festiwali');
        $new[$k] = $v;
    }
    return $new;
});
add_action('manage_festival_event_posts_custom_column', function($col,$post_id){
    if($col==='thumbnail'){
        $thumb = get_the_post_thumbnail($post_id, [80,80]);
        echo $thumb ?: '<span style="color:#999;">—</span>';
    }
},10,2);
add_action('admin_head', function(){
    echo '<style>.column-thumbnail{width:90px;text-align:center}</style>';
});

// === DODATKOWE KOLUMNY W WIDOKU WP-ADMIN ===
add_filter('manage_festival_event_posts_columns', function($columns){
    // Wstaw nowe kolumny po tytule
    $new = [];
    foreach($columns as $key => $label){
        $new[$key] = $label;
        if($key === 'title'){
            $new['kif_date'] = '📅 Data imprezy';
            $new['kif_featured'] = '💎 Polecane';
        }
    }
    return $new;
});

// === WYPEŁNIENIE DANYCH W KOLUMNACH ===
add_action('manage_festival_event_posts_custom_column', function($column, $post_id){
    if($column === 'kif_date'){
        $date = get_post_meta($post_id, '_kif_date', true);
        echo $date ? esc_html(date_i18n('d.m.Y H:i', strtotime($date))) : '—';
    }
    if($column === 'kif_featured'){
        $feat = get_post_meta($post_id, '_kif_featured', true);
        echo $feat ? '<span style="color:#f39c12;font-weight:bold;">✔️ Tak</span>' : '—';
    }
}, 10, 2);

// === SORTOWANIE PO DACIE IMPREZY ===
add_filter('manage_edit-festival_event_sortable_columns', function($columns){
    $columns['kif_date'] = 'kif_date';
    return $columns;
});

add_action('pre_get_posts', function($query){
    if(!is_admin() || !$query->is_main_query()) return;
    if($orderby = $query->get('orderby')){
        if($orderby === 'kif_date'){
            $query->set('meta_key', '_kif_date');
            $query->set('orderby', 'meta_value');
        }
    }
});
