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
