<?php
if (!defined('ABSPATH')) exit;

/**
 * Rejestracja dedykowanych endpointów dla aplikacji mobilnej
 */
add_action('rest_api_init', function () {
    register_rest_route('kif/v1', '/events', [
        'methods'             => 'GET',
        'callback'            => 'kif_api_get_all_events',
        'permission_callback' => '__return_true', // Publiczny dostęp do czytania
    ]);
});

function kif_api_get_all_events($request) {
    // Pobieramy nadchodzące wydarzenia (lub wszystkie, w zależności od potrzeb)
    $args = [
        'post_type'      => 'festival_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Na razie pobieramy wszystkie, później można dodać paginację
        'meta_key'       => '_kif_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC'
    ];

    $events = get_posts($args);
    $data = [];

    foreach ($events as $post) {
        $id = $post->ID;
        
        $data[] = [
            'id'             => $id,
            'title'          => get_the_title($id),
            'date'           => get_post_meta($id, '_kif_date', true),
            'time'           => get_post_meta($id, '_kif_time', true),
            'country'        => get_post_meta($id, '_kif_country', true) ?: 'Polska',
            'city'           => get_post_meta($id, '_kif_city', true),
            'venue'          => get_post_meta($id, '_kif_venue', true),
            'price'          => get_post_meta($id, '_kif_price', true),
            'currency'       => get_post_meta($id, '_kif_currency', true) ?: 'PLN',
            'genre'          => get_post_meta($id, '_kif_genre', true),
            'thumb'          => get_the_post_thumbnail_url($id, 'medium_large'),
            'category'       => get_post_meta($id, '_kif_category', true),
            'featured'       => (bool) get_post_meta($id, '_kif_featured', true),
            'ticket_url'     => get_post_meta($id, '_kif_ticket', true),
            'alebilet_url'   => get_post_meta($id, '_kif_alebilet', true),
            // Możemy tu dodać też lineup, ale to powiększy listę. 
            // Lepiej pobrać go dopiero po kliknięciu w szczegóły wydarzenia w aplikacji.
        ];
    }

    return new WP_REST_Response($data, 200);
}
