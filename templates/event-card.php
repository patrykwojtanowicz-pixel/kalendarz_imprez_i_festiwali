<?php
/**
 * Template: Event Card
 * Context: listing (grid)
 * Requires: $event_id (int)
 */

if (!defined('ABSPATH')) exit;
if (empty($event_id)) return;

$title       = get_the_title($event_id);
$date_iso    = get_post_meta($event_id, '_kif_date', true);
$city        = get_post_meta($event_id, '_kif_city', true);
$venue       = get_post_meta($event_id, '_kif_venue', true);
$price_raw   = get_post_meta($event_id, '_kif_price', true);
$genre_raw   = get_post_meta($event_id, '_kif_genre', true);
$thumb       = get_the_post_thumbnail_url($event_id, 'medium_large');
$types_arr   = wp_get_post_terms($event_id, 'event_type', ['fields'=>'names']);
$featured    = (bool) get_post_meta($event_id, '_kif_featured', true);

// helpery z includes/helpers.php
if (!function_exists('kif_fmt_dt') || !function_exists('kif_price_to_int') || !function_exists('kif_headliners_array')) {
  require_once dirname(__DIR__) . '/includes/helpers.php';
}

$month_key   = $date_iso ? date_i18n('Y-m', strtotime($date_iso)) : 'inne';
$price_num   = kif_price_to_int($price_raw);
$place_txt   = trim(($venue ? $venue : '') . (($venue && $city) ? ', ' : '') . ($city ? $city : ''));

$more=0; $heads = kif_headliners_array($event_id, 3, $more);
$genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));
?>
<article
  class="kif-event-card"
  data-featured="<?php echo $featured ? '1' : '0'; ?>"
  data-id="<?php echo esc_attr($event_id); ?>"
  data-month="<?php echo esc_attr($month_key); ?>"
  data-city="<?php echo esc_attr($city); ?>"
  data-venue="<?php echo esc_attr($venue); ?>"
  data-genre="<?php echo esc_attr($genre_raw); ?>"
  data-type="<?php echo esc_attr(implode(',', $types_arr)); ?>"
  data-price="<?php echo esc_attr($price_num); ?>"
>
  <?php if ($thumb): ?>
    <div class="kif-card-thumb">
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" />
      <?php if ($featured): ?>
        <span class="kif-badge-featured" style="position:absolute;left:10px;top:10px;">POLECAMY</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="kif-card-body">
    <h4 class="kif-card-title"><?php echo esc_html($title); ?></h4>

    <?php if (!empty($heads)): ?>
      <div class="kif-headliners-row">
        <span class="kif-headliners-label"><?php esc_html_e('Headlinerzy:', 'kalendarz-imprez-i-festiwali'); ?></span>
        <div class="kif-tags">
          <?php foreach ($heads as $hn): ?>
            <span class="kif-tag headliner"><?php echo esc_html($hn); ?></span>
          <?php endforeach; ?>
          <?php if ($more > 0): ?>
            <span class="kif-headliners-more"> i nie tylko</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="kif-card-meta">
      <?php if ($date_iso): ?>
        <div><?php echo esc_html(kif_fmt_dt($date_iso)); ?></div>
      <?php endif; ?>
      <?php if ($place_txt): ?>
        <div><?php echo esc_html($place_txt); ?></div>
      <?php endif; ?>
      <?php if (!empty($types_arr)): ?>
        <div class="kif-type"><?php echo esc_html__('Typ: ', 'kalendarz-imprez-i-festiwali') . esc_html($types_arr[0]); ?></div>
      <?php endif; ?>
      <?php if ($price_raw !== ''): ?>
        <div><?php echo esc_html__('Cena biletu: ', 'kalendarz-imprez-i-festiwali') . esc_html($price_raw); ?> PLN</div>
      <?php endif; ?>

      <?php if ($genre_tags): ?>
        <div class="kif-tags">
          <?php foreach ($genre_tags as $tg): ?>
            <span class="kif-tag"><?php echo esc_html($tg); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="kif-card-actions">
      <button class="kif-expand"><?php esc_html_e('Więcej informacji', 'kalendarz-imprez-i-festiwali'); ?></button>
    </div>
  </div>
</article>
