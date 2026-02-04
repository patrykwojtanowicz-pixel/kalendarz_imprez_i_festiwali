<?php
/**
 * Template: Event Modal
 * Finalna wersja:
 * - tagi gatunków pod metą
 * - znak "&" poprawnie renderowany
 * - komunikat o braku lineup'u
 * - przycisk "Więcej informacji" (jeśli istnieje link)
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kif_safe_text')) {
  function kif_safe_text($s){
    $s = is_string($s) ? $s : '';
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return esc_html($s);
  }
}

$event_id = isset($event_id) ? intval($event_id) : get_the_ID();
if (empty($event_id)) return;

// --- Dane wydarzenia ---
$title      = get_the_title($event_id);
$date       = get_post_meta($event_id, '_kif_date', true);
$city       = get_post_meta($event_id, '_kif_city', true);
$venue      = get_post_meta($event_id, '_kif_venue', true);
$price      = get_post_meta($event_id, '_kif_price', true);
$genre      = get_post_meta($event_id, '_kif_genre', true);
$ticket     = get_post_meta($event_id, '_kif_ticket', true);
$more_info  = get_post_meta($event_id, '_kif_more_info', true); // ✅ poprawny meta key
$thumb      = get_the_post_thumbnail_url($event_id, 'large');
$content    = apply_filters('the_content', get_post_field('post_content', $event_id));
$types_arr  = wp_get_post_terms($event_id, 'event_type', ['fields' => 'names']);
$headliners = get_post_meta($event_id, '_kif_headliners', true);
$lineup     = get_post_meta($event_id, '_kif_lineup', true);

// --- Budowanie meta linii ---
$meta_line = [];
if ($venue) $meta_line[] = $venue;
if ($city)  $meta_line[] = $city;
if ($date)  $meta_line[] = date_i18n('d.m.Y, H:i', strtotime($date));
if (!empty($types_arr)) $meta_line[] = 'Typ: ' . $types_arr[0];
?>

<style>
  .kif-lineup-placeholder {
    font-style: italic;
    opacity: 0.75;
    margin: 0.5rem 0 0.8rem;
    color: #555;
  }
  .kif-btn.kif-more-info {
    background: #555;
    margin-top: .35rem;
    display: inline-block;
    text-decoration: none;
    color: #fff;
    padding: .5rem 1rem;
    border-radius: 8px;
    font-size: .9rem;
    transition: transform .15s, box-shadow .15s, background .2s;
    box-shadow: 0 2px 6px rgba(0,0,0,.25);
  }
  .kif-btn.kif-more-info:hover {
    background: #333;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0,0,0,.3);
  }
</style>

<div class="kif-overlay" role="dialog" aria-modal="true">
  <div class="kif-modal">
    <button class="kif-modal-close" aria-label="Zamknij">×</button>

    <header class="kif-modal-header">
      <div>
        <h2 class="kif-modal-title"><?php echo esc_html($title); ?></h2>

        <?php if ($meta_line): ?>
          <div class="kif-meta"><?php echo esc_html(implode(' | ', $meta_line)); ?></div>
        <?php endif; ?>

        <?php
        // 🎵 Gatunki muzyczne – tuż pod metą
        $genres_raw = get_post_meta($event_id, '_kif_genre', true);
        if (!empty($genres_raw)):
          $genres_arr = array_filter(array_map('trim', preg_split('/[,;]+/', $genres_raw)));
          if ($genres_arr):
        ?>
          <div class="kif-genres kif-genres-top">
            <?php foreach ($genres_arr as $g): ?>
              <span class="kif-tag"><?php echo esc_html($g); ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; endif; ?>
      </div>

      <div class="kif-price-box">
        <?php if ($price !== ''): ?>
          <div class="kif-price-amount">💳 <?php echo esc_html($price); ?> PLN</div>
        <?php endif; ?>

        <?php if ($ticket): ?>
          <a class="kif-btn kif-buy" target="_blank" rel="noopener" href="<?php echo esc_url($ticket); ?>">
            Kup bilet
          </a>
        <?php endif; ?>

        <?php if (!empty($more_info)): // ✅ pojawia się tylko, gdy link istnieje ?>
          <a class="kif-btn kif-more-info" target="_blank" rel="noopener" href="<?php echo esc_url($more_info); ?>">
            Więcej informacji
          </a>
        <?php endif; ?>
      </div>
    </header>

    <div class="kif-modal-body">
      <?php if ($thumb): ?>
        <img class="kif-thumb" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
      <?php endif; ?>

      <?php
      $has_lineup = !empty(trim($lineup));
      $has_heads = !empty(trim($headliners));
      ?>
      <div class="kif-lineup">
        <h3>Lineup</h3>

        <?php if ($has_heads): ?>
          <div class="kif-tags">
            <?php foreach (explode(',', $headliners) as $h): ?>
              <span class="kif-tag headliner"><strong><?php echo kif_safe_text(trim($h)); ?></strong></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($has_lineup): ?>
          <div class="kif-tags" style="margin-top:.4rem;">
            <?php foreach (explode("\n", $lineup) as $a): ?>
              <span class="kif-tag"><?php echo kif_safe_text(trim($a)); ?></span>
            <?php endforeach; ?>
          </div>
        <?php elseif (!$has_heads): ?>
          <p class="kif-lineup-placeholder">Lineup zostanie podany wkrótce...</p>
        <?php endif; ?>
      </div>

      <div class="kif-description" contenteditable="true"><?php echo $content; ?></div>
    </div>
  </div>
</div>
