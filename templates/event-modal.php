<?php
/**
 * Template: Event Modal
 * Context: overlay (details)
 * Requires: $event_id (int)
 */

if (!defined('ABSPATH')) exit;
if (empty($event_id)) return;

$p            = get_post($event_id);
if (!$p || $p->post_type !== 'festival_event') return;

$title        = get_the_title($event_id);
$date_iso     = get_post_meta($event_id, '_kif_date', true);
$city         = get_post_meta($event_id, '_kif_city', true);
$venue        = get_post_meta($event_id, '_kif_venue', true);
$price_raw    = get_post_meta($event_id, '_kif_price', true);
$genre_raw    = get_post_meta($event_id, '_kif_genre', true);
$thumb        = get_the_post_thumbnail_url($event_id, 'large');
$ticket       = get_post_meta($event_id, '_kif_ticket', true);
$content_html = apply_filters('the_content', get_post_field('post_content', $event_id));
$custom_desc  = get_post_meta($event_id, '_kif_custom_desc', true);
$lineup       = get_post_meta($event_id, '_kif_lineup', true);
$headliners   = get_post_meta($event_id, '_kif_headliners', true);
$mode         = get_post_meta($event_id, '_kif_lineup_mode', true) ?: 'full';
$timetable    = get_post_meta($event_id, '_kif_timetable', true);
$types_arr    = wp_get_post_terms($event_id, 'event_type', ['fields' => 'names']);
$permalink    = get_permalink($event_id);

// helpery
if (!function_exists('kif_fmt_dt')) {
  require_once dirname(__DIR__) . '/includes/helpers.php';
}
$meta_line = [];
$place_txt = trim(($venue ? $venue : '') . (($venue && $city) ? ', ' : '') . ($city ? $city : ''));
if ($place_txt) $meta_line[] = $place_txt;
if ($date_iso)  $meta_line[] = kif_fmt_dt($date_iso);
if (!empty($types_arr)) $meta_line[] = sprintf('%s %s', __('Typ:', 'kalendarz-imprez-i-festiwali'), $types_arr[0]);

// funkcje pomocnicze tylko dla tego szablonu
$render_tags = function(array $names, $is_headliners=false){
  $out = '';
  foreach ($names as $n) {
    $n = trim($n);
    if (!$n) continue;
    $out .= '<span class="kif-tag'.($is_headliners ? ' headliner' : '').'">'.esc_html($n).'</span> ';
  }
  return $out ?: '<span class="kif-tag">'.esc_html__('Brak lineup’u','kalendarz-imprez-i-festiwali').'</span>';
};

$split_full_headliners = function($list_raw, $heads_csv){
  $all   = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$list_raw))));
  $heads = array_values(array_filter(array_map('trim', explode(',', (string)$heads_csv))));
  $rest  = array_values(array_filter(array_diff($all, $heads), 'strlen'));

  $html = '';
  if ($heads) {
    $html .= '<div class="kif-tags">'.$GLOBALS['render_tags']($heads, true).'</div>';
  }
  if ($rest) {
    $html .= '<div style="margin-top:.35rem" class="kif-tags">'.$GLOBALS['render_tags']($rest, false).'</div>';
  }
  return $html ?: '<div class="kif-tags"><span class="kif-tag">'.esc_html__('Brak lineup’u','kalendarz-imprez-i-festiwali').'</span></div>';
};

$render_days_stages = function($jsonStr){
  $o = json_decode((string)$jsonStr, true);
  if (!$o || !is_array($o)) return '<div>'.esc_html__('Błędny JSON timetable.','kalendarz-imprez-i-festiwali').'</div>';
  $h = '';
  foreach ($o as $day => $stages) {
    $h .= '<div class="kif-day"><div class="kif-day-title">'.esc_html($day).'</div>';
    foreach (($stages ?: []) as $stage => $artists) {
      $h .= '<div><strong>'.esc_html($stage).':</strong> ';
      $row = '';
      foreach (($artists ?: []) as $a) {
        $row .= '<span class="kif-tag">'.esc_html($a).'</span> ';
      }
      $h .= $row ?: '—';
      $h .= '</div>';
    }
    $h .= '</div>';
  }
  return $h ?: '<div>'.esc_html__('Brak danych.','kalendarz-imprez-i-festiwali').'</div>';
};

$render_timetable_grid = function($jsonStr){
  $data = json_decode((string)$jsonStr, true);
  if (!$data || !is_array($data)) return '<div>'.esc_html__('Błędny JSON timetable.','kalendarz-imprez-i-festiwali').'</div>';

  $h = '';
  foreach ($data as $day => $stages) {
    $times = [];
    foreach (($stages ?: []) as $stage => $slots) {
      foreach (($slots ?: []) as $slot) {
        if (!empty($slot['time'])) $times[$slot['time']] = true;
      }
    }
    $timeline = array_keys($times);
    sort($timeline, SORT_STRING);

    $h .= '<div class="kif-timetable-wrap">';
    $h .= '<div class="kif-day-title">'.esc_html($day).'</div>';
    $h .= '<div class="kif-grid">';
    foreach ($timeline as $t) {
      $h .= '<div class="kif-time-col">'.esc_html($t).'</div>';
      foreach (array_keys($stages) as $stage) {
        $slot = null;
        foreach (($stages[$stage] ?: []) as $s) {
          if (isset($s['time']) && $s['time'] === $t) { $slot = $s; break; }
        }
        if ($slot) {
          $h .= '<div class="kif-slot">'.esc_html($slot['artist']).'<br><small>'.esc_html($stage).'</small></div>';
        } else {
          $h .= '<div class="kif-slot" style="opacity:.35">—</div>';
        }
      }
    }
    $h .= '</div></div>';
  }
  return $h ?: '<div>'.esc_html__('Brak timetable.','kalendarz-imprez-i-festiwali').'</div>';
};

// zbuduj sekcję lineup wg trybu
$lineup_html = '';
switch ($mode) {
  case 'full_headliners':
    $lineup_html = $split_full_headliners($lineup, $headliners);
    break;
  case 'headliners_only':
    $list = implode("\n", array_filter(array_map('trim', explode(',', (string)$headliners))));
    $lineup_html = $split_full_headliners($list, $headliners);
    break;
  case 'days_stages':
    $lineup_html = $render_days_stages($timetable);
    break;
  case 'timetable':
    $lineup_html = $render_timetable_grid($timetable);
    break;
  default:
    $arr = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$lineup))));
    $lineup_html = '<div class="kif-tags">'.$render_tags($arr, false).'</div>';
}

// gatunki do tagów w stopce
$genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));

// SHARE — gotowe linki (ikony przez CSS/JS mają klasy kolorów)
$share = function($url, $text) {
  $e = esc_url($url);
  $t = rawurlencode(wp_strip_all_tags($text));
  return '
    <div class="kif-share">
      <span class="label">'.esc_html__('Udostępnij:', 'kalendarz-imprez-i-festiwali').'</span>
      <a class="kif-share-btn fb"   target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='.$e.'"><span aria-hidden="true">f</span></a>
      <a class="kif-share-btn msgr" target="_blank" rel="noopener" href="https://www.facebook.com/dialog/send?app_id=174829003346&link='.$e.'&redirect_uri='.$e.'"><span aria-hidden="true">m</span></a>
      <a class="kif-share-btn x"    target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='.$e.'&text='.$t.'"><span aria-hidden="true">x</span></a>
      <button class="kif-share-btn ig" data-copy="'.esc_attr($url).'" title="'.esc_attr__('Skopiuj link', 'kalendarz-imprez-i-festiwali').'"><span aria-hidden="true">ig</span></button>
      <a class="kif-share-btn wa"   target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text='.$t.'%20-%20'.$e.'"><span aria-hidden="true">wa</span></a>
    </div>
  ';
};
?>
<div class="kif-overlay" role="dialog" aria-modal="true">
  <div class="kif-modal">
    <button class="kif-modal-close" aria-label="<?php esc_attr_e('Zamknij','kalendarz-imprez-i-festiwali'); ?>">×</button>

    <header class="kif-modal-header">
      <div>
        <h2 class="kif-modal-title"><?php echo esc_html($title); ?></h2>
        <?php if ($meta_line): ?>
          <div class="kif-meta"><?php echo esc_html(implode(' | ', $meta_line)); ?></div>
        <?php endif; ?>
      </div>

      <div class="kif-price-box">
        <?php if ($price_raw !== ''): ?>
          <div class="kif-price-amount">💳 <?php echo esc_html($price_raw); ?> PLN</div>
        <?php endif; ?>
        <?php if ($ticket): ?>
          <a class="kif-btn kif-buy" target="_blank" rel="noopener" href="<?php echo esc_url($ticket); ?>">
            <?php esc_html_e('Kup bilet','kalendarz-imprez-i-festiwali'); ?>
          </a>
        <?php endif; ?>
      </div>
    </header>

    <div class="kif-modal-body">
      <?php if ($thumb): ?>
        <img class="kif-thumb" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" />
      <?php endif; ?>

      <?php if ($lineup_html): ?>
        <div class="kif-lineup">
          <h3><?php esc_html_e('Line-up','kalendarz-imprez-i-festiwali'); ?></h3>
          <?php echo $lineup_html; ?>
        </div>
      <?php endif; ?>

      <div class="kif-description" contenteditable="true" data-id="<?php echo esc_attr($event_id); ?>">
        <?php echo $content_html; ?>
      </div>

      <?php if (!empty($custom_desc)): ?>
        <div class="kif-custom-desc">
          <h3><?php esc_html_e('Dodatkowy opis','kalendarz-imprez-i-festiwali'); ?></h3>
          <p><?php echo esc_html($custom_desc); ?></p>
        </div>
      <?php endif; ?>
    </div>

    <div class="kif-modal-footer">
      <button class="kif-btn kif-save" data-id="<?php echo esc_attr($event_id); ?>">💾 <?php esc_html_e('Zapisz opis','kalendarz-imprez-i-festiwali'); ?></button>
      <?php echo $share($permalink, $title); ?>

      <?php if ($genre_tags): ?>
        <div class="kif-genres">
          <?php foreach ($genre_tags as $tg): ?>
            <span class="kif-tag"><?php echo esc_html($tg); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
