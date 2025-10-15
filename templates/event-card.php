<?php
/**
 * Template: Single Event Card
 */

if (!defined('ABSPATH')) exit;

$event_id = isset($event_id) ? intval($event_id) : get_the_ID();
if (!$event_id) return;

$title       = get_the_title($event_id);
$permalink   = get_permalink($event_id);
$date_iso    = get_post_meta($event_id, '_kif_date', true);
$city        = get_post_meta($event_id, '_kif_city', true);
$venue       = get_post_meta($event_id, '_kif_venue', true);
$genre_raw   = get_post_meta($event_id, '_kif_genre', true);
$price_raw   = get_post_meta($event_id, '_kif_price', true);
$price_label = $price_raw ? (is_numeric($price_raw) ? intval($price_raw) . ' PLN' : esc_html($price_raw)) : '—';
$thumb       = get_the_post_thumbnail_url($event_id, 'medium');
$lineup      = get_post_meta($event_id, '_kif_lineup', true);
$headliners  = get_post_meta($event_id, '_kif_headliners', true);
$featured    = get_post_meta($event_id, '_kif_featured', true);
$types_arr   = wp_get_post_terms($event_id, 'event_type', ['fields' => 'names']);
$price_num   = is_numeric($price_raw) ? floatval($price_raw) : 0;

$headliner_list = array_filter(array_map('trim', explode(',', $headliners)));
$headliner_display = array_slice($headliner_list, 0, 3);
$headliner_extra = count($headliner_list) > 3 ? ' <span class="kif-more">i nie tylko</span>' : '';

$genres = array_filter(array_map('trim', explode(',', $genre_raw)));
?>
<div class="kif-event-card"
  data-id="<?php echo esc_attr($event_id); ?>"
  data-title="<?php echo esc_attr($title); ?>"
  data-city="<?php echo esc_attr($city); ?>"
  data-venue="<?php echo esc_attr($venue); ?>"
  data-genre="<?php echo esc_attr(implode(',', $genres)); ?>"
  data-type="<?php echo esc_attr(implode(',', $types_arr)); ?>"
  data-price="<?php echo esc_attr($price_num); ?>"
  data-month="<?php echo esc_attr(substr($date_iso, 0, 7)); ?>"
>
  <?php if ($featured): ?>
    <div class="kif-badge-featured">POLECAMY</div>
  <?php endif; ?>

  <?php if ($thumb): ?>
    <div class="kif-thumb-wrap">
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
    </div>
  <?php endif; ?>

  <div class="kif-content">
    <h3 class="kif-title"><?php echo esc_html($title); ?></h3>

    <div class="kif-meta">
      <?php if ($venue || $city): ?>
        <span class="kif-location"><?php echo esc_html($venue ? "$venue, " : ""); echo esc_html($city); ?></span>
      <?php endif; ?>
      <?php if ($date_iso): ?>
        <span class="kif-date"><?php echo date_i18n('d.m.Y, H:i', strtotime($date_iso)); ?></span>
      <?php endif; ?>
      <?php if (!empty($types_arr)): ?>
        <span class="kif-type"><?php echo esc_html($types_arr[0]); ?></span>
      <?php endif; ?>
    </div>

    <?php if (!empty($headliner_display)): ?>
      <div class="kif-headliners">
        <?php foreach ($headliner_display as $h): ?>
          <span class="kif-tag headliner"><?php echo esc_html($h); ?></span>
        <?php endforeach; ?>
        <?php echo $headliner_extra; ?>
      </div>
    <?php elseif ($lineup): ?>
      <div class="kif-lineup-preview">
        <?php
          $artists = array_filter(array_map('trim', explode("\n", $lineup)));
          $preview = array_slice($artists, 0, 3);
          foreach ($preview as $a) {
            echo '<span class="kif-tag">'.esc_html($a).'</span> ';
          }
        ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($genres)): ?>
      <div class="kif-genres">
        <?php foreach ($genres as $g): ?>
          <span class="kif-tag genre"><?php echo esc_html($g); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="kif-footer">
      <span class="kif-price">💳 <?php echo esc_html($price_label); ?></span>
      <button class="kif-btn kif-expand">więcej informacji</button>
    </div>
  </div>
</div>
