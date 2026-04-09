<?php
if (!defined('ABSPATH')) exit;

get_header();

$id = get_the_ID();
$title = get_the_title($id);

// --- 1. DATY I CZAS ---
$date_raw = get_post_meta($id, '_kif_date', true);
$date_end_raw = get_post_meta($id, '_kif_date_end', true);
$time_raw = get_post_meta($id, '_kif_time', true);
$custom_date = get_post_meta($id, '_kif_custom_date', true); 

$d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
$evStartRaw = $d1_parts[0];
$evTime = $time_raw ?: (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '');

$d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
$evEndRaw = $d2_parts[0];

$d1 = $evStartRaw ? date('d.m.Y', strtotime($evStartRaw)) : '';
$d2 = $evEndRaw ? date('d.m.Y', strtotime($evEndRaw)) : '';
$timeStr = $evTime ? ' | Start: ' . $evTime : '';

$dateDisplay = '';
if ($custom_date) {
    $dateDisplay = $custom_date . $timeStr;
} elseif($d1) {
    if($d2 && $d2 !== $d1) {
        $dateDisplay = $d1 . ' - ' . $d2 . $timeStr;
    } else {
        $dateDisplay = $d1 . $timeStr;
    }
}

// --- 2. LOKALIZACJA (Zaktualizowana o kraj i flagę) ---
$country = get_post_meta($id, '_kif_country', true) ?: 'Polska';
$country_data = function_exists('kif_get_country_data') ? kif_get_country_data($country) : ['flag'=>'🌍'];

$city = get_post_meta($id, '_kif_city', true);
$venue = get_post_meta($id, '_kif_venue', true);

// Tekst do wyświetlenia (z flagą)
$where_display = $country_data['flag'] . ' ' . implode(', ', array_filter([$city, $venue]));

// Czysty tekst do nawigacji Google Maps (Miasto, Lokalizacja, Kraj)
$where_clean = implode(', ', array_filter([$venue, $city, $country]));

$metaLine = implode(' | ', array_filter([$where_display, $dateDisplay]));

// --- 3. GATUNKI ---
$genre_raw = get_post_meta($id, '_kif_genre', true);
$genresHtml = '';
if ($genre_raw) {
    $tags = array_filter(array_map('trim', preg_split('/[,;]+/', $genre_raw)));
    foreach($tags as $t) {
        $genresHtml .= '<span class="kif-tag">'.esc_html($t).'</span> ';
    }
}

$featured = get_post_meta($id, '_kif_featured', true);

// --- 4. BILETY I STATUSY (Zaktualizowana waluta) ---
$is_paid = get_post_meta($id, '_kif_is_paid', true) ?: 'tak';
$on_sale = get_post_meta($id, '_kif_on_sale', true) ?: 'tak';
$sale_reason = get_post_meta($id, '_kif_sale_reason', true);

$price = get_post_meta($id, '_kif_price', true);
$currency = get_post_meta($id, '_kif_currency', true) ?: 'PLN'; // Pobieranie waluty
$priceText = $price ? trim($price) . ' ' . $currency : ''; // Łączenie ceny z dynamiczną walutą

$ticket = get_post_meta($id, '_kif_ticket', true);
$alebilet = get_post_meta($id, '_kif_alebilet', true);
$more_info = get_post_meta($id, '_kif_more_info', true);

// Styl bazowy dla wszystkich przycisków w sekcji biletowej, aby były identyczne
$btn_style = 'display: flex; align-items: center; justify-content: center; width: 100%; min-height: 46px; margin-top: 10px; padding: 10px; border-radius: 8px; font-weight: 600; text-decoration: none; text-align: center; box-sizing: border-box; font-size: 0.95rem; line-height: 1.2; transition: all 0.2s;';

$badgeHtml = '';
if($is_paid === 'nie'){
    $badgeHtml = '<div class="kif-badge-sale kif-badge-free" style="width:100%; text-align:center; font-size:0.9rem; margin-bottom:10px;">WSTĘP FREE</div>';
} elseif($on_sale === 'nie'){
    if($sale_reason === 'sprzedaz_nie_ruszyla') $badgeHtml = '<div class="kif-badge-sale kif-badge-upcoming" style="width:100%; text-align:center; font-size:0.9rem; margin-bottom:10px;">SPRZEDAŻ WKRÓTCE</div>';
    elseif($sale_reason === 'wyprzedane') $badgeHtml = '<div class="kif-badge-sale kif-badge-soldout" style="width:100%; text-align:center; font-size:0.9rem; margin-bottom:10px;">SOLD OUT</div>';
}

$priceBoxHtml = '';
if($is_paid === 'nie'){
    $priceBoxHtml = $badgeHtml;
} elseif($on_sale === 'tak'){
    if($priceText) $priceBoxHtml .= '<div class="kif-price-amount" style="text-align:center; margin-bottom:10px; font-weight:bold; font-size:1.2rem;">💳 '.esc_html($priceText).'</div>';
} else {
    $priceBoxHtml = $badgeHtml;
}

// Przyciski akcji
if($ticket) {
    $priceBoxHtml .= '<a href="'.esc_url($ticket).'" class="kif-btn kif-buy" target="_blank" rel="noopener" style="'.$btn_style.' background-color: var(--fcp-accent, #ff7a1c); color: #fff;">Kup bilet</a>';
}
if($alebilet) {
    $priceBoxHtml .= '<a href="'.esc_url($alebilet).'" class="kif-btn kif-btn-alebilet" target="_blank" rel="noopener" style="'.$btn_style.' background-color: #003b95; color: #fff;">Bilety na Alebilet</a>';
}
if($more_info) {
    $priceBoxHtml .= '<a href="'.esc_url($more_info).'" class="kif-btn kif-more-info" target="_blank" rel="noopener" style="'.$btn_style.' background-color: #f1f3f4; color: #333; border: 1px solid #ddd;">Więcej informacji</a>';
}

// Przycisk trasy - ulepszony link do nawigacji z uwzględnieniem kraju
if(trim($where_clean)) {
    $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($where_clean);
    $priceBoxHtml .= '<a href="'.esc_url($mapsUrl).'" target="_blank" rel="noopener" class="kif-btn kif-route-btn" style="'.$btn_style.' background-color: #f1f3f4; color: #333; border: 1px solid #ddd;">🚗 Wyznacz trasę</a>';
}

// --- 5. EKSPORT DO KALENDARZA ---
$calHtml = '';
if($evStartRaw) {
    $cal_end_date = get_post_meta($id, '_kif_cal_end_date', true);
    $cal_end_time = get_post_meta($id, '_kif_cal_end_time', true);

    try {
        $icsStartObj = new DateTime($evStartRaw . ' ' . ($evTime ?: '00:00'));
        $icsStart = $icsStartObj->format('Ymd\THis');
        $exportEndDate = $cal_end_date ?: ($evEndRaw ?: $evStartRaw);
        $exportEndTime = $cal_end_time ?: '23:59';
        $icsEndObj = new DateTime($exportEndDate . ' ' . $exportEndTime);
        $icsEnd = $icsEndObj->format('Ymd\THis');
        $gcalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=".urlencode($title)."&dates={$icsStart}/{$icsEnd}&details=".urlencode(get_permalink($id))."&location=".urlencode($where_clean);
        $icalUrl = add_query_arg('kif_ics', $id, get_permalink($id));

        $calHtml = '
        <div class="kif-calendar-export">
            <span class="kif-calendar-export-label" style="font-weight: 600; font-size: 0.9rem; color: #555;">🗓️ Dodaj do kalendarza:</span>
            <div class="kif-calendar-export-btns" style="margin-top: 5px;">
                <a href="'.esc_url($gcalUrl).'" target="_blank" rel="noopener" class="kif-btn-sec">Google</a>
                <a href="'.esc_url($icalUrl).'" class="kif-btn-sec">iCal / Outlook</a>
            </div>
        </div>';
    } catch (Exception $e) {}
}

// --- 6. TREŚCI ---
$thumb = get_the_post_thumbnail_url($id, 'large');
$content = apply_filters('the_content', get_post_field('post_content', $id));
$custom_desc = get_post_meta($id, '_kif_custom_desc', true);

// Lineup logic
$lineup_raw = get_post_meta($id, '_kif_lineup', true);
$heads_raw = get_post_meta($id, '_kif_headliners', true);
$lineup_mode = get_post_meta($id, '_kif_lineup_mode', true) ?: 'full';
$all_artists = array_filter(array_map('trim', explode("\n", $lineup_raw)));
$headliners = array_filter(array_map('trim', explode(",", $heads_raw)));
$headliners_lower = array_map('strtolower', $headliners);

function build_tags($artists, $headliners_lower) {
    $out = '';
    foreach($artists as $a) {
        $out .= in_array(strtolower($a), $headliners_lower) 
            ? '<span class="kif-tag headliner"><strong>'.esc_html($a).'</strong></span> ' 
            : '<span class="kif-tag">'.esc_html($a).'</span> ';
    }
    return $out;
}

$lineupHtml = '';
if($lineup_mode === 'full_headliners') {
    $rest = array_filter($all_artists, function($a) use ($headliners_lower) { return !in_array(strtolower($a), $headliners_lower); });
    $lineupHtml = build_tags($headliners, $headliners_lower) . '<div style="margin-top:.35rem"></div>' . build_tags($rest, []);
} elseif($lineup_mode === 'headliners_only') {
    $lineupHtml = build_tags($headliners, $headliners_lower);
} else {
    $lineupHtml = build_tags($all_artists, $headliners_lower);
}
if(!$lineupHtml) $lineupHtml = '<p class="kif-lineup-placeholder">Lineup zostanie podany wkrótce...</p>';
?>

<div class="kif-single-wrapper">
    <div class="kif-modal kif-modal-single">
        <header class="kif-modal-header" style="display: block;">
            <a href="https://shiningbeats.pl/kalendarz-imprez-i-festiwali/" class="kif-back-link" style="display: inline-block; margin-bottom: 15px;">← Wróć do kalendarza</a>

<?php if($thumb): ?>
    <div style="position: relative !important; width: 100% !important; aspect-ratio: 1.91 / 1 !important; background: #000 !important; overflow: hidden !important; border-radius: 12px !important; margin-bottom: 25px !important; display: block !important; padding: 0 !important;">
        <img src="<?php echo esc_url($thumb); ?>" aria-hidden="true" style="position: absolute !important; top: -5% !important; left: -5% !important; width: 110% !important; height: 110% !important; object-fit: cover !important; filter: blur(15px) brightness(0.5) !important; z-index: 1 !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: none !important;">
        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" data-pin-nopin="true" style="position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; object-fit: contain !important; z-index: 2 !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: none !important; background: transparent !important;">
    </div>
<?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 280px;">
                    <div class="kif-modal-title-row" style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.5rem;">
                        <h1 class="kif-modal-title" style="margin:0; font-size: 1.8rem;"><?php echo esc_html($title); ?></h1>
                        <?php if($featured): ?>
                            <span class="kif-badge-featured" style="background:#8e44ff;color:#fff;font-weight:700;font-size:.85rem;padding:4px 10px;border-radius:8px;display:inline-block;">POLECAMY</span>
                        <?php endif; ?>
                        <span class="kif-countdown kif-countdown-modal kif-hidden" data-date="<?php echo esc_attr($evStartRaw); ?>" data-time="<?php echo esc_attr($evTime); ?>"></span>
                    </div>
                    <div class="kif-meta" style="margin-bottom: 10px; color: #666;"><?php echo esc_html($metaLine); ?></div>
                    <?php if($genresHtml): ?>
                        <div class="kif-genres kif-genres-top" style="margin-bottom: 20px;"><?php echo $genresHtml; ?></div>
                    <?php endif; ?>
                    
                    <div class="kif-cal-desktop">
                        <?php echo $calHtml; ?>
                    </div>
                </div>
                
                <div class="kif-price-box" style="margin-top: 0; width: 100%; max-width: 300px; background: #fff; padding: 20px; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                    <?php echo $priceBoxHtml; ?>
                </div>
            </div>
        </header>
        
        <div class="kif-modal-body">
            <?php if(!empty($all_artists) || !empty($headliners)): ?>
            <div class="kif-lineup">
                <h3>Lineup</h3>
                <?php echo $lineupHtml; ?>
            </div>
            <?php endif; ?>

            <?php if($custom_desc): ?>
                <div class="kif-custom-desc-accordion" style="margin: 30px 0;">
                    <button class="kif-accordion-btn" type="button" style="width: 100%; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border: 1px solid #ddd; padding: 15px 20px; font-size: 1.1rem; font-weight: bold; border-radius: 8px; cursor: pointer;">
                        <span>📝 Dodatkowe informacje</span>
                        <span class="kif-accordion-icon">▼</span>
                    </button>
                    <div class="kif-accordion-content" style="display: none; padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
                        <div><?php echo apply_filters('the_content', $custom_desc); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="kif-cal-mobile" style="margin-bottom: 25px;">
                <?php echo $calHtml; ?>
            </div>

            <div class="kif-description">
                <?php echo $content; ?>
            </div>
            
            <?php if (function_exists('kif_display_related_events')) kif_display_related_events($id); ?>
        </div>
    </div>
</div>

<style>
.kif-cal-mobile { display: none; padding-top: 15px; border-top: 1px solid #eee; }
.kif-btn:hover { filter: brightness(95%); transform: translateY(-1px); }
.kif-accordion-icon { transition: transform 0.3s; }

@media (max-width: 768px) {
    .kif-cal-desktop { display: none !important; }
    .kif-cal-mobile { display: block !important; }
    .kif-price-box { max-width: 100% !important; margin-top: 20px !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if(typeof jQuery !== 'undefined') {
        jQuery(function($){
            function updateSingleCountdown() {
                var now = new Date().getTime();
                $('.kif-countdown-modal').each(function(){
                    var $t = $(this);
                    var dateStr = $t.attr('data-date'); 
                    var timeStr = $t.attr('data-time') || '00:00'; 
                    if(!dateStr) return;
                    
                    var timePart = timeStr.length >= 5 ? timeStr.substring(0, 5) + ':00' : '00:00:00';
                    var fullDateStr = String(dateStr).replace(' ', 'T') + 'T' + timePart;
                    var evDate = new Date(fullDateStr).getTime();
                    
                    // Zabezpieczenie dla Safari (gdzie brakuje natywnego wsparcia dla T w starszych wersjach)
                    if (isNaN(evDate)) {
                        evDate = new Date(String(dateStr).replace(/-/g, '/') + ' ' + timePart).getTime();
                    }
                    if (isNaN(evDate)) return; 
                    
                    var diff = evDate - now;
                    if(diff > 0) {
                        var d = Math.floor(diff / (1000 * 60 * 60 * 24));
                        var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        
                        var text = '⏳ Za ';
                        if(d === 1) text += '1 dzień, ';
                        else if(d > 1) text += d + ' dni, ';
                        text += h + ' godz. ' + m + ' min';
                        
                        $t.text(text).removeClass('kif-hidden');
                    } else {
                        $t.text('🎉 Trwa!').removeClass('kif-hidden');
                    }
                });
            }
            updateSingleCountdown();
            setInterval(updateSingleCountdown, 60000);

            $('.kif-accordion-btn').on('click', function(){
                var $btn = $(this);
                var $content = $btn.next('.kif-accordion-content');
                var $icon = $btn.find('.kif-accordion-icon');
                $content.slideToggle(250, function() {
                    $icon.css('transform', $content.is(':visible') ? 'rotate(180deg)' : 'rotate(0deg)');
                    $btn.css('border-radius', $content.is(':visible') ? '8px 8px 0 0' : '8px');
                });
            });
        });
    }
});
</script>

<?php get_footer(); ?>
