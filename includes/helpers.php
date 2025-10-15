<?php
if (!defined('ABSPATH')) exit;

function kif_fmt_dt($iso){
    if(empty($iso)) return '';
    $t = strtotime($iso); if(!$t) return esc_html($iso);
    return date_i18n('d.m.Y, H:i', $t);
}
function kif_price_to_int($raw){
    if(preg_match('/(\d+)/',(string)$raw,$m)) return intval($m[1],10);
    return 0;
}
/** Zwraca do 3 headlinerów i liczbę „more” (ilu więcej jest) */
function kif_headliners_array($post_id, $limit=3, &$more=0){
    $headsCsv = (string)get_post_meta($post_id,'_kif_headliners',true);
    $heads = array_filter(array_map('trim', explode(',', $headsCsv)));
    if(empty($heads)){
        $lines = preg_split('/\r?\n/', (string)get_post_meta($post_id,'_kif_lineup',true));
        $heads = array_filter(array_map('trim',$lines));
    }
    $more = max(0, count($heads) - $limit);
    return array_slice($heads, 0, $limit);
}

/**
 * Zwraca dane do wyświetlenia artystów na kartach wydarzeń.
 * Jeśli podano headlinerów – są preferowani. W przeciwnym razie wykorzystywany
 * jest line-up.
 */
function kif_get_display_artists($post_id, $limit = 3){
    $heads_raw = array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_kif_headliners', true))));

    if(!empty($heads_raw)){
        return [
            'label'      => 'Headlinerzy:',
            'items'      => array_slice($heads_raw, 0, $limit),
            'more'       => max(0, count($heads_raw) - $limit),
            'highlight'  => true,
        ];
    }

    $lineup_raw = array_filter(array_map('trim', preg_split('/\r?\n/', (string) get_post_meta($post_id, '_kif_lineup', true))));

    if(!empty($lineup_raw)){
        return [
            'label'      => 'Wystąpią:',
            'items'      => array_slice($lineup_raw, 0, $limit),
            'more'       => max(0, count($lineup_raw) - $limit),
            'highlight'  => false,
        ];
    }

    return [
        'label'     => '',
        'items'     => [],
        'more'      => 0,
        'highlight' => false,
    ];
}
function kif_get_settings(){
    $defaults = ['accent_color'=>'#ff7a1c','price_step'=>10,'grid_columns'=>'auto','dark_mode'=>1];
    return wp_parse_args(get_option('kif_settings', []), $defaults);
}
