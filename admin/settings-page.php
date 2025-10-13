<?php
if (!defined('ABSPATH')) exit;

function kif_add_admin_menu(){
    add_menu_page(__('Kalendarz imprez i festiwali','kalendarz-imprez-i-festiwali'), __('Kalendarz imprez i festiwali','kalendarz-imprez-i-festiwali'),'manage_options','kalendarz-imprez-i-festiwali','kif_render_settings_page','dashicons-calendar-alt',6);
    add_submenu_page('kalendarz-imprez-i-festiwali', __('Ustawienia','kalendarz-imprez-i-festiwali'), __('Ustawienia','kalendarz-imprez-i-festiwali'),'manage_options','fcp-settings','kif_render_settings_page');
    add_submenu_page('kalendarz-imprez-i-festiwali', __('Wydarzenia','kalendarz-imprez-i-festiwali'), __('Wydarzenia','kalendarz-imprez-i-festiwali'),'manage_options','edit.php?post_type=festival_event');
}
add_action('admin_menu','kif_add_admin_menu');

function kif_register_settings(){ register_setting('kif_settings_group','kif_settings'); }
add_action('admin_init','kif_register_settings');

function kif_render_settings_page(){
    $settings = get_option('kif_settings', ['accent_color'=>'#ff7a1c','price_step'=>10,'grid_columns'=>'auto','dark_mode'=>1]);
    if(isset($_POST['kif_reset_defaults'])){
        check_admin_referer('kif_reset_defaults_action');
        update_option('kif_settings', ['accent_color'=>'#ff7a1c','price_step'=>10,'grid_columns'=>'auto','dark_mode'=>1]);
        echo '<div class="notice notice-success is-dismissible"><p>'.esc_html__('Przywrócono wartości domyślne.','kalendarz-imprez-i-festiwali').'</p></div>';
        $settings = get_option('kif_settings');
    } ?>
    <div class="wrap kif-settings">
      <h1><?php _e('Ustawienia kalendarza','kalendarz-imprez-i-festiwali'); ?></h1>
      <?php if(isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php _e('Ustawienia zapisano pomyślnie ✅','kalendarz-imprez-i-festiwali'); ?></p></div>
      <?php endif; ?>
      <form method="post" action="options.php" id="kif-settings-form">
        <?php settings_fields('kif_settings_group'); ?>
        <table class="form-table">
          <tr>
            <th><label for="accent_color">Kolor akcentu</label></th>
            <td>
              <input type="color" id="accent_color" name="kif_settings[accent_color]" value="<?php echo esc_attr($settings['accent_color']); ?>">
              <span class="kif-color-preview" style="background: <?php echo esc_attr($settings['accent_color']); ?>"></span>
            </td>
          </tr>
          <tr>
            <th><label for="price_step">Krok suwaka ceny (PLN)</label></th>
            <td><input type="number" id="price_step" name="kif_settings[price_step]" value="<?php echo esc_attr(intval($settings['price_step'])); ?>" min="1" step="1"></td>
          </tr>
          <tr>
            <th><label for="grid_columns">Liczba kolumn w siatce</label></th>
            <td>
              <select id="grid_columns" name="kif_settings[grid_columns]">
                <?php foreach(['auto','2','3','4'] as $opt){ echo '<option value="'.esc_attr($opt).'" '.selected($settings['grid_columns'],$opt,false).'>'.esc_html($opt).'</option>'; } ?>
              </select>
              <div class="kif-grid-preview" data-cols="<?php echo esc_attr($settings['grid_columns']); ?>">
                <span></span><span></span><span></span><span></span>
              </div>
            </td>
          </tr>
          <tr>
            <th><label for="dark_mode">Tryb ciemny</label></th>
            <td><label><input type="checkbox" id="dark_mode" name="kif_settings[dark_mode]" value="1" <?php checked($settings['dark_mode'],1); ?>> <?php _e('Włącz ciemny motyw','kalendarz-imprez-i-festiwali'); ?></label></td>
          </tr>
        </table>
        <?php submit_button(__('Zapisz ustawienia','kalendarz-imprez-i-festiwali')); ?>
      </form>
      <form method="post" style="margin-top:8px;">
        <?php wp_nonce_field('kif_reset_defaults_action'); ?>
        <input type="hidden" name="kif_reset_defaults" value="1">
        <button class="button"><?php _e('Przywróć domyślne','kalendarz-imprez-i-festiwali'); ?></button>
      </form>
      <script>
        (function(){
          const color = document.getElementById('accent_color');
          const prev = document.querySelector('.kif-color-preview');
          color && color.addEventListener('input', e => prev.style.background = e.target.value);
          const grid = document.getElementById('grid_columns');
          const preview = document.querySelector('.kif-grid-preview');
          grid && grid.addEventListener('change', e => preview.dataset.cols = e.target.value);
        })();
      </script>
    </div>
<?php }
