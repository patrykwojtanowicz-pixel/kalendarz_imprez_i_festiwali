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
function kif_get_settings(){
    $defaults = ['accent_color'=>'#ff7a1c','price_step'=>10,'grid_columns'=>'auto','dark_mode'=>1];
    return wp_parse_args(get_option('kif_settings', []), $defaults);
}
