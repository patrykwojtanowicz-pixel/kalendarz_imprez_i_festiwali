<?php
if (!defined('ABSPATH')) exit;

function kif_add_metaboxes(){
    add_meta_box('kif_event_meta','🗓️ Szczegóły wydarzenia','kif_event_meta_cb','festival_event','normal','high');
}
add_action('add_meta_boxes','kif_add_metaboxes');

function kif_event_meta_cb($post){
    $date_raw      = get_post_meta($post->ID,'_kif_date',true);
    $date_end_raw = get_post_meta($post->ID,'_kif_date_end',true);
    $time_raw      = get_post_meta($post->ID,'_kif_time',true);

    $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
    $date = $d1_parts[0];
    $time = !empty($time_raw) ? $time_raw : (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '');

    $d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
    $date_end = $d2_parts[0];

    $custom_date  = get_post_meta($post->ID,'_kif_custom_date',true);
    $cal_end_date = get_post_meta($post->ID,'_kif_cal_end_date',true);
    $cal_end_time = get_post_meta($post->ID,'_kif_cal_end_time',true);

    $country   = get_post_meta($post->ID,'_kif_country',true) ?: 'Polska'; // NOWE POLE
    $city      = get_post_meta($post->ID,'_kif_city',true);
    $venue     = get_post_meta($post->ID,'_kif_venue',true);
    
    $price     = get_post_meta($post->ID,'_kif_price',true);
    $currency  = get_post_meta($post->ID,'_kif_currency',true) ?: 'PLN'; // NOWE POLE
    
    $genre     = get_post_meta($post->ID,'_kif_genre',true);
    $ticket    = get_post_meta($post->ID,'_kif_ticket',true);
    $alebilet = get_post_meta($post->ID,'_kif_alebilet',true);
    $more      = get_post_meta($post->ID,'_kif_more_info',true);
    $lineup    = get_post_meta($post->ID,'_kif_lineup',true);
    $heads     = get_post_meta($post->ID,'_kif_headliners',true);
    $mode      = get_post_meta($post->ID,'_kif_lineup_mode',true);
    $tt        = get_post_meta($post->ID,'_kif_timetable',true);
    $on_sale   = get_post_meta($post->ID,'_kif_on_sale',true) ?: 'tak';
    $sale_reason = get_post_meta($post->ID,'_kif_sale_reason',true);
    $is_paid   = get_post_meta($post->ID,'_kif_is_paid',true) ?: 'tak';
    $featured = get_post_meta($post->ID,'_kif_featured',true);
    $category = get_post_meta($post->ID, '_kif_category', true);
    
    $custom_desc = get_post_meta($post->ID, '_kif_custom_desc', true);

    wp_nonce_field('kif_event_save_action','kif_event_nonce'); ?>
    
    <style>
      .kif-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .kif-grid .full{grid-column:1/-1}
      .kif-grid label{font-weight:600;margin-bottom:4px;display:block}
    </style>

    <div class="kif-grid">
      <div class="full" style="background:#f4f4f4; padding:15px; border-left:4px solid #8e44ff; margin-bottom: 10px;">
        <p style="margin:0 0 5px 0;"><label>📝 Dodatkowy opis (Rozwijany na stronie wydarzenia)</label></p>
        <textarea name="kif_event_description_field" rows="4" style="width:100%;"><?php echo esc_textarea($custom_desc); ?></textarea>
      </div>

      <p><label>Data rozpoczęcia</label><input type="date" name="kif_date" value="<?php echo esc_attr($date); ?>" style="width:100%"></p>
      <p><label>Godzina rozpoczęcia</label><input type="time" name="kif_time" value="<?php echo esc_attr($time); ?>" style="width:100%"></p>
      
      <p class="full"><label>Data zakończenia</label><input type="date" name="kif_date_end" value="<?php echo esc_attr($date_end); ?>" style="width:50%"></p>

      <div class="full" style="background:#f9f9f9; padding:12px; border-left:4px solid #ff7a1c; margin-bottom:8px;">
        <p style="margin:0;"><label>Niestandardowy tekst daty</label>
        <input type="text" name="kif_custom_date" value="<?php echo esc_attr($custom_date); ?>" style="width:100%" placeholder="np. 24 i 25 maja 2026">
      </div>

      <div class="full" style="background:#f0f0f1; padding:15px; border-left:4px solid #2271b1;">
        <h4 style="margin:0 0 10px 0;">Eksport do kalendarza</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <p style="margin:0;"><label>Data zakończenia (eksport)</label><input type="date" name="kif_cal_end_date" value="<?php echo esc_attr($cal_end_date); ?>" style="width:100%"></p>
            <p style="margin:0;"><label>Godzina zakończenia (eksport)</label><input type="time" name="kif_cal_end_time" value="<?php echo esc_attr($cal_end_time); ?>" style="width:100%"></p>
        </div>
      </div>

      <p class="full"><label>🌍 Kraj</label>
        <select name="kif_country" style="width:50%">
          <?php
          $countries = ['Polska', 'Niemcy', 'Holandia', 'Belgia', 'Czechy', 'Słowacja', 'Chorwacja', 'Hiszpania', 'Francja', 'Wielka Brytania', 'Włochy', 'Węgry', 'Rumunia', 'USA'];
          foreach ($countries as $c) {
              printf('<option value="%s" %s>%s</option>', esc_attr($c), selected($country, $c, false), esc_html($c));
          }
          ?>
        </select>
      </p>

      <p><label>Miasto</label><input type="text" name="kif_city" value="<?php echo esc_attr($city); ?>" style="width:100%"></p>
      <p><label>Lokalizacja</label><input type="text" name="kif_venue" value="<?php echo esc_attr($venue); ?>" style="width:100%"></p>

      <p><label>Kategoria wydarzenia</label>
        <select name="kif_category" style="width:100%">
          <?php
          $categories = ['' => '— Wybierz kategorię —', 'Impreza klubowa' => 'Impreza klubowa', 'Koncert' => 'Koncert', 'Impreza halowa' => 'Impreza halowa', 'Festiwal' => 'Festiwal'];
          foreach ($categories as $value => $label) {
              printf('<option value="%s" %s>%s</option>', esc_attr($value), selected($category, $value, false), esc_html($label));
          }
          ?>
        </select>
      </p>

      <p><label>Czy trzeba zapłacić za wstęp?</label>
        <select name="kif_is_paid" style="width:100%">
          <option value="tak" <?php selected($is_paid,'tak'); ?>>Tak</option>
          <option value="nie" <?php selected($is_paid,'nie'); ?>>Nie (wstęp wolny)</option>
        </select>
      </p>

      <p><label>Czy bilety są w sprzedaży?</label>
        <select name="kif_on_sale" style="width:100%">
          <option value="tak" <?php selected($on_sale,'tak'); ?>>Tak</option>
          <option value="nie" <?php selected($on_sale,'nie'); ?>>Nie</option>
        </select>
      </p>

      <p><label>Powód (jeśli nie)</label>
        <select name="kif_sale_reason" style="width:100%">
          <option value="">—</option>
          <option value="sprzedaz_nie_ruszyla" <?php selected($sale_reason,'sprzedaz_nie_ruszyla'); ?>>Sprzedaż nie ruszyła</option>
          <option value="wyprzedane" <?php selected($sale_reason,'wyprzedane'); ?>>Bilety są wyprzedane</option>
        </select>
      </p>

      <div class="full" style="display:grid; grid-template-columns: 2fr 1fr; gap:12px;">
          <p style="margin:0;"><label>Cena biletu (np. 199 lub „od 179”)</label>
            <input type="text" name="kif_price" value="<?php echo esc_attr($price); ?>" style="width:100%">
          </p>
          <p style="margin:0;"><label>Waluta</label>
            <select name="kif_currency" style="width:100%">
              <?php
              $currencies = ['PLN', 'EUR', 'GBP', 'USD', 'CZK', 'HUF'];
              foreach ($currencies as $curr) {
                  printf('<option value="%s" %s>%s</option>', esc_attr($curr), selected($currency, $curr, false), esc_html($curr));
              }
              ?>
            </select>
          </p>
      </div>

      <p class="full"><label>Gatunek(i) (po przecinkach)</label><input type="text" name="kif_genre" value="<?php echo esc_attr($genre); ?>" style="width:100%"></p>

      <p class="full"><label>Link do biletów</label><input type="url" name="kif_ticket" value="<?php echo esc_attr($ticket); ?>" style="width:100%"></p>
      <p class="full"><label>Znajdź bilet na Alebilet (URL)</label><input type="url" name="kif_alebilet" value="<?php echo esc_attr($alebilet); ?>" style="width:100%"></p>

      <p class="full"><label>Więcej informacji (URL)</label>
        <input type="url" name="kif_more_info" value="<?php echo esc_attr($more); ?>" placeholder="https://example.com/szczegoly-wydarzenia" style="width:100%">
      </p>

      <p class="full"><label>Lineup (jeden artysta na linię)</label>
        <textarea name="kif_lineup" rows="6" style="width:100%"><?php echo esc_textarea($lineup); ?></textarea>
      </p>

      <p class="full"><label>Headlinerzy (po przecinkach)</label>
        <input type="text" name="kif_headliners" value="<?php echo esc_attr($heads); ?>" style="width:100%">
      </p>

      <p><label>Tryb lineup’u</label>
        <select name="kif_lineup_mode" style="width:100%">
          <?php
          $opts = ['full' => 'Pełna lista', 'full_headliners' => 'Pełna lista z headlinerami', 'headliners_only' => 'Tylko headlinerzy', 'days_stages' => 'Pełna lista z podziałem na dni i sceny', 'timetable' => 'Timetable (graficzny)'];
          $cur = $mode ?: 'full';
          foreach($opts as $k=>$v){
            echo '<option value="'.esc_attr($k).'" '.selected($cur,$k,false).'>'.esc_html($v).'</option>';
          } ?>
        </select>
      </p>

      <p><label>Timetable (JSON 24h)</label>
        <textarea name="kif_timetable" rows="4" style="width:100%"><?php echo esc_textarea($tt); ?></textarea>
      </p>

      <p class="full" style="margin-top:12px;">
        <label><input type="checkbox" name="kif_featured" value="1" <?php checked($featured,'1'); ?>> 💎 Polecane przez redakcję</label>
      </p>
    </div>
<?php }

function kif_event_save($post_id){
    if(get_post_type($post_id) !== 'festival_event') return;
    if(!isset($_POST['kif_event_nonce']) || !wp_verify_nonce($_POST['kif_event_nonce'],'kif_event_save_action')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post', $post_id)) return;

    if(isset($_POST['kif_event_description_field'])) {
        update_post_meta($post_id, '_kif_custom_desc', wp_kses_post(wp_unslash($_POST['kif_event_description_field'])));
    }

    if(isset($_POST['kif_date']))         update_post_meta($post_id, '_kif_date', sanitize_text_field(wp_unslash($_POST['kif_date'])));
    if(isset($_POST['kif_time']))         update_post_meta($post_id, '_kif_time', sanitize_text_field(wp_unslash($_POST['kif_time'])));
    if(isset($_POST['kif_date_end']))     update_post_meta($post_id, '_kif_date_end', sanitize_text_field(wp_unslash($_POST['kif_date_end'])));
    if(isset($_POST['kif_custom_date']))  update_post_meta($post_id, '_kif_custom_date', sanitize_text_field(wp_unslash($_POST['kif_custom_date'])));
    if(isset($_POST['kif_cal_end_date'])) update_post_meta($post_id, '_kif_cal_end_date', sanitize_text_field(wp_unslash($_POST['kif_cal_end_date'])));
    if(isset($_POST['kif_cal_end_time'])) update_post_meta($post_id, '_kif_cal_end_time', sanitize_text_field(wp_unslash($_POST['kif_cal_end_time'])));
    
    if(isset($_POST['kif_country']))      update_post_meta($post_id, '_kif_country', sanitize_text_field(wp_unslash($_POST['kif_country']))); // KRAJ
    if(isset($_POST['kif_currency']))     update_post_meta($post_id, '_kif_currency', sanitize_text_field(wp_unslash($_POST['kif_currency']))); // WALUTA
    
    if(isset($_POST['kif_city']))         update_post_meta($post_id, '_kif_city', sanitize_text_field(wp_unslash($_POST['kif_city'])));
    if(isset($_POST['kif_venue']))        update_post_meta($post_id, '_kif_venue', sanitize_text_field(wp_unslash($_POST['kif_venue'])));
    if(isset($_POST['kif_category']))     update_post_meta($post_id, '_kif_category', sanitize_text_field(wp_unslash($_POST['kif_category'])));
    if(isset($_POST['kif_price']))        update_post_meta($post_id, '_kif_price', sanitize_text_field(wp_unslash($_POST['kif_price'])));
    if(isset($_POST['kif_genre']))        update_post_meta($post_id, '_kif_genre', sanitize_text_field(wp_unslash($_POST['kif_genre'])));
    if(isset($_POST['kif_ticket']))       update_post_meta($post_id, '_kif_ticket', esc_url_raw(wp_unslash($_POST['kif_ticket'])));
    if(isset($_POST['kif_alebilet']))     update_post_meta($post_id, '_kif_alebilet', esc_url_raw(wp_unslash($_POST['kif_alebilet'])));
    if(isset($_POST['kif_more_info']))    update_post_meta($post_id, '_kif_more_info', esc_url_raw(wp_unslash($_POST['kif_more_info'])));
    if(isset($_POST['kif_headliners']))   update_post_meta($post_id, '_kif_headliners', sanitize_text_field(wp_unslash($_POST['kif_headliners'])));
    if(isset($_POST['kif_lineup_mode']))  update_post_meta($post_id, '_kif_lineup_mode', sanitize_text_field(wp_unslash($_POST['kif_lineup_mode'])));
    if(isset($_POST['kif_on_sale']))      update_post_meta($post_id, '_kif_on_sale', sanitize_text_field(wp_unslash($_POST['kif_on_sale'])));
    if(isset($_POST['kif_sale_reason']))  update_post_meta($post_id, '_kif_sale_reason', sanitize_text_field(wp_unslash($_POST['kif_sale_reason'])));
    if(isset($_POST['kif_is_paid']))      update_post_meta($post_id, '_kif_is_paid', sanitize_text_field(wp_unslash($_POST['kif_is_paid'])));
    
    if(isset($_POST['kif_lineup']))       update_post_meta($post_id, '_kif_lineup', wp_kses_post(wp_unslash($_POST['kif_lineup'])));
    if(isset($_POST['kif_timetable']))    update_post_meta($post_id, '_kif_timetable', wp_kses_post(wp_unslash($_POST['kif_timetable'])));

    if(isset($_POST['kif_featured'])) {
        update_post_meta($post_id, '_kif_featured', '1');
    } else {
        update_post_meta($post_id, '_kif_featured', '');
    }
}
add_action('save_post', 'kif_event_save');

function kif_quick_edit_custom_box($col, $post_type){
  if($post_type !== 'festival_event') return;
  static $print = false;
  if($print) return;
  $print = true;
  ?>
  <fieldset class="inline-edit-col-right">
    <div class="inline-edit-col">
      <label class="alignleft" style="margin-bottom:10px;">
        <span class="title">💎 Polecane</span>
        <input type="checkbox" name="kif_featured_quick" value="1">
      </label>
      <label class="alignleft" style="width:100%;">
        <span class="title">Typ imprezy</span>
        <select name="kif_category_quick" style="max-width:200px;">
            <option value="">— Brak —</option>
            <option value="Impreza klubowa">Impreza klubowa</option>
            <option value="Koncert">Koncert</option>
            <option value="Impreza halowa">Impreza halowa</option>
            <option value="Festiwal">Festiwal</option>
        </select>
      </label>
    </div>
  </fieldset>
  <?php
}
add_action('quick_edit_custom_box','kif_quick_edit_custom_box',10,2);

function kif_save_quick_edit($post_id){
  if(get_post_type($post_id) !== 'festival_event') return;
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if(!current_user_can('edit_post',$post_id)) return;
  if(!isset($_POST['action']) || $_POST['action'] !== 'inline-save') return;

  if(isset($_POST['kif_featured_quick'])){
    update_post_meta($post_id,'_kif_featured','1');
  } else {
    update_post_meta($post_id,'_kif_featured','');
  }

  if(isset($_POST['kif_category_quick'])){
      update_post_meta($post_id, '_kif_category', sanitize_text_field($_POST['kif_category_quick']));
  }
}
add_action('save_post','kif_save_quick_edit');

add_action('admin_footer', 'kif_quick_edit_js');
function kif_quick_edit_js() {
    $screen = get_current_screen();
    if(!$screen || $screen->post_type !== 'festival_event') return;
    ?>
    <script>
    jQuery(document).ready(function($){
        var $wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $wp_inline_edit.apply(this, arguments);
            var post_id = 0;
            if (typeof(id) === 'object') {
                post_id = parseInt(this.getId(id));
            }
            if (post_id > 0) {
                var edit_row = $('#edit-' + post_id);
                var post_row = $('#post-' + post_id);
                var type = post_row.find('.kif_inline_type').text().trim();
                var is_featured = post_row.find('.column-kif_featured').text().indexOf('Tak') !== -1;
                edit_row.find('select[name="kif_category_quick"]').val(type);
                edit_row.find('input[name="kif_featured_quick"]').prop('checked', is_featured);
            }
        };
    });
    </script>
    <?php
}
