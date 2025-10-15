<?php
if (!defined('ABSPATH')) exit;

function kif_add_metaboxes(){
    add_meta_box('kif_event_meta','🗓️ Szczegóły wydarzenia','kif_event_meta_cb','festival_event','normal','default');
    add_meta_box('kif_featured','💎 Polecane przez redakcję','kif_featured_cb','festival_event','side','high');
    add_meta_box('kif_custom_desc','📝 Dodatkowy opis','kif_custom_desc_cb','festival_event','normal','low');
}
add_action('add_meta_boxes','kif_add_metaboxes');

function kif_event_meta_cb($post){
    $date  = get_post_meta($post->ID,'_kif_date',true);
    $city  = get_post_meta($post->ID,'_kif_city',true);
    $venue = get_post_meta($post->ID,'_kif_venue',true);
    $price = get_post_meta($post->ID,'_kif_price',true);
    $genre = get_post_meta($post->ID,'_kif_genre',true);
    $ticket= get_post_meta($post->ID,'_kif_ticket',true);
    $more  = get_post_meta($post->ID,'_kif_more_info',true);
    $lineup= get_post_meta($post->ID,'_kif_lineup',true);
    $heads = get_post_meta($post->ID,'_kif_headliners',true);
    $mode  = get_post_meta($post->ID,'_kif_lineup_mode',true);
    $event_mode = get_post_meta($post->ID,'_kif_event_mode',true);
    $tt    = get_post_meta($post->ID,'_kif_timetable',true);
    wp_nonce_field('kif_event_save','kif_event_nonce'); ?>
    <style>
      .kif-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .kif-grid .full{grid-column:1/-1}
      .kif-grid label{font-weight:600;margin-bottom:4px;display:block}
    </style>
    <div class="kif-grid">
      <p><label>Data i godzina</label><input type="datetime-local" name="kif_date" value="<?php echo esc_attr($date); ?>" style="width:100%"></p>
      <p><label>Miasto</label><input type="text" name="kif_city" value="<?php echo esc_attr($city); ?>" style="width:100%"></p>
      <p><label>Lokalizacja</label><input type="text" name="kif_venue" value="<?php echo esc_attr($venue); ?>" style="width:100%"></p>
      <p><label>Cena biletu (np. 199 lub „od 179”)</label><input type="text" name="kif_price" value="<?php echo esc_attr($price); ?>" style="width:100%"></p>
      <p class="full"><label>Gatunek(i) (przecinki)</label><input type="text" name="kif_genre" value="<?php echo esc_attr($genre); ?>" style="width:100%"></p>
      <p class="full"><label>Link do biletów</label><input type="url" name="kif_ticket" value="<?php echo esc_attr($ticket); ?>" style="width:100%"></p>

      <p class="full"><label>Więcej informacji (URL)</label>
        <input type="url" name="kif_more_info" value="<?php echo esc_attr($more); ?>" placeholder="https://example.com/szczegoly-wydarzenia" style="width:100%">
        <small style="color:#666">Podaj pełny adres URL (np. do strony z dodatkowymi informacjami o wydarzeniu).</small>
      </p>

      <p class="full"><label>Line-up (jeden artysta na linię)</label>
        <textarea name="kif_lineup" rows="6" style="width:100%"><?php echo esc_textarea($lineup); ?></textarea>
      </p>
      <p class="full"><label>Headlinerzy (przecinki)</label>
        <input type="text" name="kif_headliners" value="<?php echo esc_attr($heads); ?>" style="width:100%">
      </p>
      <p><label>Tryb imprezy</label>
        <select name="kif_event_mode" style="width:100%">
          <?php
          $event_modes = [
            '' => '— wybierz —',
            'Koncert' => 'Koncert',
            'Festiwal' => 'Festiwal',
            'Impreza klubowa' => 'Impreza klubowa',
            'Halówka' => 'Halówka',
          ];
          foreach($event_modes as $value => $label){
              echo '<option value="'.esc_attr($value).'" '.selected($event_mode, $value, false).'>'.esc_html($label).'</option>';
          }
          ?>
        </select>
      </p>
      <p><label>Tryb lineup’u</label>
        <select name="kif_lineup_mode" style="width:100%">
          <?php
          $opts = [
            'full' => 'Pełna lista',
            'full_headliners' => 'Pełna lista z headlinerami',
            'headliners_only' => 'Tylko headlinerzy',
            'days_stages' => 'Pełna lista z podziałem na dni i sceny',
            'timetable' => 'Timetable (graficzny)'
          ];
          $cur = $mode ?: 'full';
          foreach($opts as $k=>$v){
            echo '<option value="'.esc_attr($k).'" '.selected($cur,$k,false).'>'.esc_html($v).'</option>';
          } ?>
        </select>
      </p>
      <p><label>Timetable (JSON 24h)</label>
        <textarea name="kif_timetable" rows="6" style="width:100%"><?php echo esc_textarea($tt); ?></textarea>
      </p>
    </div>
<?php }

function kif_custom_desc_cb($post){
    $custom = get_post_meta($post->ID,'_kif_custom_desc',true);
    echo '<textarea name="kif_custom_desc" rows="4" style="width:100%">'.esc_textarea($custom).'</textarea>';
}

function kif_featured_cb($post){
    $feat = get_post_meta($post->ID,'_kif_featured',true);
    wp_nonce_field('kif_featured_save','kif_featured_nonce');
    echo '<label><input type="checkbox" name="kif_featured" value="1" '.checked($feat,'1',false).'> '.__('Wyróżnij to wydarzenie (POLECAMY)','kalendarz-imprez-i-festiwali').'</label>';
}

function kif_event_save($post_id){
    if(!isset($_POST['kif_event_nonce']) || !wp_verify_nonce($_POST['kif_event_nonce'],'kif_event_save')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;

    update_post_meta($post_id,'_kif_date', sanitize_text_field($_POST['kif_date'] ?? ''));
    update_post_meta($post_id,'_kif_city', sanitize_text_field($_POST['kif_city'] ?? ''));
    update_post_meta($post_id,'_kif_venue', sanitize_text_field($_POST['kif_venue'] ?? ''));
    update_post_meta($post_id,'_kif_price', sanitize_text_field($_POST['kif_price'] ?? ''));
    update_post_meta($post_id,'_kif_genre', sanitize_text_field($_POST['kif_genre'] ?? ''));
    update_post_meta($post_id,'_kif_ticket', esc_url_raw($_POST['kif_ticket'] ?? ''));
    update_post_meta($post_id,'_kif_more_info', esc_url_raw($_POST['kif_more_info'] ?? ''));
    update_post_meta($post_id,'_kif_lineup', wp_kses_post($_POST['kif_lineup'] ?? ''));
    update_post_meta($post_id,'_kif_headliners', sanitize_text_field($_POST['kif_headliners'] ?? ''));
    update_post_meta($post_id,'_kif_event_mode', sanitize_text_field($_POST['kif_event_mode'] ?? ''));
    update_post_meta($post_id,'_kif_lineup_mode', sanitize_text_field($_POST['kif_lineup_mode'] ?? 'full'));
    update_post_meta($post_id,'_kif_timetable', wp_kses_post($_POST['kif_timetable'] ?? ''));
}
add_action('save_post_festival_event','kif_event_save');

function kif_featured_save($post_id){
    if(!isset($_POST['kif_featured_nonce']) || !wp_verify_nonce($_POST['kif_featured_nonce'],'kif_featured_save')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_kif_featured', isset($_POST['kif_featured']) ? '1' : '');
}
add_action('save_post_festival_event','kif_featured_save');

function kif_custom_desc_save($post_id){
    if(!current_user_can('edit_post',$post_id)) return;
    if(isset($_POST['kif_custom_desc'])){
        update_post_meta($post_id,'_kif_custom_desc', sanitize_textarea_field($_POST['kif_custom_desc']));
    }
}
add_action('save_post_festival_event','kif_custom_desc_save');
