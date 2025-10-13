<?php
/*
Plugin Name: Kalendarz imprez i festiwali
Description: Zaawansowany kalendarz wydarzeń muzycznych: typy imprez (Klub/Hala/Festiwal), panel ustawień z podglądem, pojedynczy suwak ceny, headlinerzy (max 3 + „i nie tylko”), modal ze szczegółami i socialami (oficjalne logotypy). Styl zgodny z Newsblock.
Version: 1.2
Author: Shining Beats
Text Domain: kalendarz-imprez-i-festiwali
*/

if (!defined('ABSPATH')) exit;

define('KIF_VER', '1.2');
define('KIF_DIR', plugin_dir_path(__FILE__));
define('KIF_URL', plugin_dir_url(__FILE__));

require_once KIF_DIR.'includes/post-type.php';
require_once KIF_DIR.'includes/taxonomies.php';
require_once KIF_DIR.'includes/meta-boxes.php';
require_once KIF_DIR.'includes/helpers.php';
require_once KIF_DIR.'includes/ajax.php';
require_once KIF_DIR.'includes/shortcode.php';
require_once KIF_DIR.'admin/settings-page.php';

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

register_activation_hook(__FILE__, function(){ kif_register_post_type(); kif_register_taxonomies(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });
