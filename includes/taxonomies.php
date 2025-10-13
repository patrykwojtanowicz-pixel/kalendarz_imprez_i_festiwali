<?php
if (!defined('ABSPATH')) exit;

function kif_register_taxonomies(){
    $labels = [
        'name'          => __('Typy imprez','kalendarz-imprez-i-festiwali'),
        'singular_name' => __('Typ imprezy','kalendarz-imprez-i-festiwali'),
        'menu_name'     => __('Typ imprezy','kalendarz-imprez-i-festiwali'),
        'all_items'     => __('Wszystkie typy','kalendarz-imprez-i-festiwali'),
        'add_new_item'  => __('Dodaj nowy typ','kalendarz-imprez-i-festiwali'),
        'search_items'  => __('Szukaj typów','kalendarz-imprez-i-festiwali'),
    ];
    register_taxonomy('event_type', 'festival_event', [
        'labels'            => $labels,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug'=>'typ-imprezy'],
    ]);

    if(!get_option('kif_default_event_types_created')){
        wp_insert_term('Klub','event_type');
        wp_insert_term('Hala','event_type');
        wp_insert_term('Festiwal','event_type');
        update_option('kif_default_event_types_created', 1);
    }
}
add_action('init','kif_register_taxonomies');

/** Filtr po typie imprezy w panelu (lista wydarzeń) */
add_action('restrict_manage_posts', function($post_type){
    if($post_type==='festival_event'){
        $selected = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '';
        $terms = get_terms(['taxonomy'=>'event_type','hide_empty'=>false]);
        echo '<select name="event_type"><option value="">'.esc_html__('Wszystkie typy','kalendarz-imprez-i-festiwali').'</option>';
        foreach($terms as $t){
            printf('<option value="%s"%s>%s</option>',
                esc_attr($t->slug),
                selected($selected,$t->slug,false),
                esc_html($t->name)
            );
        }
        echo '</select>';
    }
});
add_filter('parse_query', function($q){
    global $pagenow;
    if($pagenow==='edit.php' && isset($_GET['event_type']) && $_GET['event_type']!==''){
        $q->query_vars['tax_query'] = [[
            'taxonomy' => 'event_type',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['event_type'])
        ]];
    }
});
