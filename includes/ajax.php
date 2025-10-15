<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX: Pobiera dane szczegółowe wydarzenia (do modala)
 */
function kif_get_event_details(){
    check_ajax_referer('kif_nonce','nonce');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if(!$id) wp_send_json_error('Brak ID');
    $p = get_post($id);
    if(!$p || $p->post_type !== 'festival_event') wp_send_json_error('Nie znaleziono');

    $terms = wp_get_post_terms($id, 'event_type', ['fields' => 'names']);

    $data = [
        'id'             => $id,
        'title'          => get_the_title($id),
        'date'           => get_post_meta($id, '_kif_date', true),
        'location'       => get_post_meta($id, '_kif_venue', true),
        'city'           => get_post_meta($id, '_kif_city', true),
        'price'          => get_post_meta($id, '_kif_price', true),
        'genre'          => get_post_meta($id, '_kif_genre', true),
        'thumb'          => get_the_post_thumbnail_url($id, 'large'),
        'ticket'         => get_post_meta($id, '_kif_ticket', true),
        'more_info'      => get_post_meta($id, '_kif_more_info', true), // ✅ DODANE pole dla "Więcej informacji"
        'content'        => apply_filters('the_content', get_post_field('post_content', $id)),
        'custom_desc'    => get_post_meta($id, '_kif_custom_desc', true),
        'permalink'      => get_permalink($id),
        'lineup'         => html_entity_decode(get_post_meta($id, '_kif_lineup', true), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'headliners'     => html_entity_decode(get_post_meta($id, '_kif_headliners', true), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'lineup_mode'    => get_post_meta($id, '_kif_lineup_mode', true),
        'timetable_json' => get_post_meta($id, '_kif_timetable', true),
        'featured'       => (bool) get_post_meta($id, '_kif_featured', true),
        'types'          => $terms,
    ];

    wp_send_json_success($data);
}
add_action('wp_ajax_kif_get_event_details', 'kif_get_event_details');
add_action('wp_ajax_nopriv_kif_get_event_details', 'kif_get_event_details');

/**
 * AJAX: Aktualizacja opisu wydarzenia
 */
function kif_update_event_description(){
    check_ajax_referer('kif_nonce', 'nonce');

    if (!current_user_can('edit_posts'))
        wp_send_json_error('Brak uprawnień');

    $id = intval($_POST['id'] ?? 0);
    $html = wp_kses_post($_POST['html'] ?? '');

    if (!$id) wp_send_json_error('Brak ID');

    $res = wp_update_post([
        'ID'           => $id,
        'post_content' => $html,
    ], true);

    if (is_wp_error($res))
        wp_send_json_error($res->get_error_message());

    wp_send_json_success(true);
}
add_action('wp_ajax_kif_update_event_description', 'kif_update_event_description');
