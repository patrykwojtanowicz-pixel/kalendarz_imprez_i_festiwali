<?php
if (!defined('ABSPATH')) exit;

/**
 * Dodanie metaboxów do edycji wydarzeń
 */
function kif_add_metaboxes(){
    add_meta_box('kif_event_meta','🗓️ Szczegóły wydarzenia','kif_event_meta_cb','festival_event','normal','default');
    add_meta_box('kif_custom_desc','📝 Dodatkowy opis','kif_custom_desc_cb','festival_event','normal','low');
}
add_action('add_meta_boxes','kif_add_metaboxes');

/**
 * Pole główne (Szczegóły wydarzenia)
 */
function kif_event_meta_cb($post){
    $date  = get_post_meta($post->ID,'_kif_date',true);
    $date_end = get_post_meta($post->ID,'_kif_date_end',true);
    $city  = get_post_meta($post->ID,'_kif_city',true);
    $venue = get_post_meta($post->ID,'_kif_venue',true);
    $price = get_post_meta($post->ID,'_kif_price',true);
    $genre = get_post_meta($post->ID,'_kif_genre',true);
    $ticket= get_post_meta($post->ID,'_kif_ticket',true);
    $more  = get_post_meta($post->ID,'_kif_more_info',true);
    $lineup= get_post_meta($post->ID,'_kif_lineup',true);
    $heads = get_post_meta($post->ID,'_kif_headliners',true);
    $mode  = get_post_meta($post->ID,'_kif_lineup_mode',true);
    $tt    = get_post_meta($post->ID,'_kif_timetable',true);
    $on_sale = get_post_meta($post->ID,'_kif_on_sale',true) ?: 'tak';
    $sale_reason = get_post_meta($post->ID,'_kif_sale_reason',true);
    $is_paid = get_post_meta($post->ID,'_kif_is_paid',true) ?: 'tak';
    $featured = get_post_meta($post->ID,'_kif_featured',true);

    wp_nonce_field('kif_event_save','kif_event_nonce'); ?>
    <style>
      .kif-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .kif-grid .full{grid-column:1/-1}
      .kif-grid label{font-weight:600;margin-bottom:4px;display:block}
    </style>

    <div class="kif-grid">
      <p><label>Data rozpoczęcia</label><input type="datetime-local" name="kif_date" value="<?php echo esc_attr($date); ?>" style="width:100%"></p>
      <p><label>Data zakończenia</label><input type="datetime-local" name="kif_date_end" value="<?php echo esc_attr($date_end); ?>" style="width:100%"></p>
      <p><label>Miasto</label><input type="text" name="kif_city" value="<?php echo esc_attr($city); ?>" style="width:100%"></p>
      <p><label>Lokalizacja</label><input type="text" name="kif_venue" value="<?php echo esc_attr($venue); ?>" style="width:100%"></p>

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

      <p><label>Powód (jeśli bilety nie są w sprzedaży)</label>
        <select name="kif_sale_reason" style="width:100%">
          <option value="">—</option>
          <option value="sprzedaz_nie_ruszyla" <?php selected($sale_reason,'sprzedaz_nie_ruszyla'); ?>>Sprzedaż nie ruszyła</option>
          <option value="wyprzedane" <?php selected($sale_reason,'wyprzedane'); ?>>Bilety są wyprzedane</option>
        </select>
      </p>

      <p><label>Cena biletu (np. 199 lub „od 179”)</label><input type="text" name="kif_price" value="<?php echo esc_attr($price); ?>" style="width:100%"></p>
      <p class="full"><label>Gatunek(i) (po przecinkach)</label><input type="text" name="kif_genre" value="<?php echo esc_attr($genre); ?>" style="width:100%"></p>

      <p class="full"><label>Link do biletów</label><input type="url" name="kif_ticket" value="<?php echo esc_attr($ticket); ?>" style="width:100%"></p>
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

      <p class="full" style="margin-top:12px;">
        <label><input type="checkbox" name="kif_featured" value="1" <?php checked($featured,'1'); ?>> 💎 Polecane przez redakcję</label>
      </p>
    </div>
<?php }

/**
 * Dodatkowy opis
 */
function kif_custom_desc_cb($post){
    $custom = get_post_meta($post->ID,'_kif_custom_desc',true);
    echo '<textarea name="kif_custom_desc" rows="4" style="width:100%">'.esc_textarea($custom).'</textarea>';
}

/**
 * Zapis metadanych wydarzenia
 */
function kif_event_save($post_id){
    if(!isset($_POST['kif_event_nonce']) || !wp_verify_nonce($_POST['kif_event_nonce'],'kif_event_save')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;

    $fields = [
        '_kif_date'         => sanitize_text_field($_POST['kif_date'] ?? ''),
        '_kif_date_end'     => sanitize_text_field($_POST['kif_date_end'] ?? ''),
        '_kif_city'         => sanitize_text_field($_POST['kif_city'] ?? ''),
        '_kif_venue'        => sanitize_text_field($_POST['kif_venue'] ?? ''),
        '_kif_price'        => sanitize_text_field($_POST['kif_price'] ?? ''),
        '_kif_genre'        => sanitize_text_field($_POST['kif_genre'] ?? ''),
        '_kif_ticket'       => esc_url_raw($_POST['kif_ticket'] ?? ''),
        '_kif_more_info'    => esc_url_raw($_POST['kif_more_info'] ?? ''),
        '_kif_lineup'       => wp_kses_post($_POST['kif_lineup'] ?? ''),
        '_kif_headliners'   => sanitize_text_field($_POST['kif_headliners'] ?? ''),
        '_kif_lineup_mode'  => sanitize_text_field($_POST['kif_lineup_mode'] ?? 'full'),
        '_kif_timetable'    => wp_kses_post($_POST['kif_timetable'] ?? ''),
        '_kif_on_sale'      => sanitize_text_field($_POST['kif_on_sale'] ?? 'tak'),
        '_kif_sale_reason'  => sanitize_text_field($_POST['kif_sale_reason'] ?? ''),
        '_kif_is_paid'      => sanitize_text_field($_POST['kif_is_paid'] ?? 'tak'),
        '_kif_featured'     => isset($_POST['kif_featured']) ? '1' : '', // ✅ poprawiony zapis
    ];

    foreach($fields as $k=>$v){
        update_post_meta($post_id, $k, $v);
    }
}
add_action('save_post_festival_event','kif_event_save');

/**
 * Szybka edycja – checkbox „Polecane”
 */
function kif_quick_edit_custom_box($col, $post_type){
  if($post_type !== 'festival_event') return;
  if($col === 'title'){ ?>
    <fieldset class="inline-edit-col-right">
      <div class="inline-edit-col">
        <label class="alignleft">
          <span class="title">💎 Polecane</span>
          <input type="checkbox" name="kif_featured_quick" value="1">
        </label>
      </div>
    </fieldset>
  <?php }
}
add_action('quick_edit_custom_box','kif_quick_edit_custom_box',10,2);

/**
 * Zapis szybki (Quick Edit)
 */
function kif_save_quick_edit($post_id){
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if(!current_user_can('edit_post',$post_id)) return;

  // ✅ poprawiony zapis, by nie zerować pola przy braku checkboxa
  if(isset($_POST['kif_featured_quick'])){
    update_post_meta($post_id,'_kif_featured','1');
  } elseif(array_key_exists('kif_featured_quick', $_POST)) {
    update_post_meta($post_id,'_kif_featured','');
  }
}
add_action('save_post_festival_event','kif_save_quick_edit');
