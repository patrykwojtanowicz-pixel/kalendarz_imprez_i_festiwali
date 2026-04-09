<?php
if (!defined('ABSPATH')) exit;

function kif_calendar_shortcode($atts){
    $atts = shortcode_atts(['mode'=>'rozszerzony'], $atts, 'festival_calendar');
    $settings = kif_get_settings();

    $events = get_posts([
        'post_type'   => 'festival_event',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_key'    => '_kif_date',
        'orderby'     => 'meta_value',
        'order'       => 'ASC'
    ]);

    $months = $cities = $venues = $genres = $types = $countries_filter = [];
    $max_price = 0;

    $today_cutoff = date('Y-m-d');

    $events = array_filter($events, function($p) use ($today_cutoff){
        $start_raw = get_post_meta($p->ID, '_kif_date', true);
        $end_raw   = get_post_meta($p->ID, '_kif_date_end', true) ?: $start_raw;
        $end = explode('T', str_replace(' ', 'T', $end_raw))[0];
        
        if(!$end) return false;
        return $end >= $today_cutoff; 
    });

    foreach($events as $p){
        $d_raw = get_post_meta($p->ID, '_kif_date', true);
        $d = explode('T', str_replace(' ', 'T', $d_raw))[0];
        
        if($d){
            $k = date_i18n('Y-m', strtotime($d));
            $months[$k] = date_i18n('F Y', strtotime($d));
        }

        $price = kif_price_to_int(get_post_meta($p->ID, '_kif_price', true));
        if($price > $max_price) $max_price = $price;

        $c = get_post_meta($p->ID, '_kif_city', true);
        if($c) $cities[$c] = true;

        $country = get_post_meta($p->ID, '_kif_country', true) ?: 'Polska';
        $countries_filter[$country] = true;

        $v = get_post_meta($p->ID, '_kif_venue', true);
        if($v) $venues[$v] = true;

        $g = get_post_meta($p->ID, '_kif_genre', true);
        if($g){
            foreach(preg_split('/[,;]+/', $g) as $tg){
                $tg = trim($tg);
                if($tg) $genres[$tg] = true;
            }
        }

        $cat = get_post_meta($p->ID, '_kif_category', true);
        if($cat) $types[$cat] = true;
    }

    if($max_price <= 0) $max_price = 1000;
    $max_price = ceil($max_price / 100) * 100;

    $featured_ids = [];
    foreach($events as $p){
        if(get_post_meta($p->ID, '_kif_featured', true))
            $featured_ids[] = $p->ID;
    }

    ob_start(); ?>
    
    <style>
        /* SZYBKIE FILTRY (PRESETY) */
        .kif-quick-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* GŁÓWNY KONTENER SZCZEGÓŁOWYCH FILTRÓW - SZTYWNA SIATKA GRID NA 1 LINIĘ */
        #kif-unique-cal .kif-filters {
            display: grid !important;
            /* 8 kolumn: Szukaj (1.5fr), 5x identyczne Selecty (1fr), Cena zwężona (1.1fr), Wyczyść (auto) */
            grid-template-columns: 1.5fr repeat(5, 1fr) 1.1fr max-content !important;
            gap: 8px !important; 
            margin-bottom: 35px !important;
            width: 100% !important;
            align-items: end !important; 
        }
        
        #kif-unique-cal .kif-filter {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            min-width: 0 !important; /* Gwarancja skalowania w jednej linii */
            margin: 0 !important;
            padding: 0 !important;
            position: relative !important;
        }

        /* ETYKIETY NAD FILTRAMI */
        #kif-unique-cal .kif-filter label {
            font-size: 0.65rem !important;
            margin-bottom: 6px !important;
            font-weight: 700 !important;
            color: inherit !important; 
            opacity: 0.6 !important; 
            text-transform: uppercase !important;
            text-align: center !important;
            display: block !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        /* RESETOWANIE WŁAŚCIWOŚCI MOTYWU */
        #kif-unique-cal .kif-filter select,
        #kif-unique-cal .kif-filter input,
        #kif-unique-cal .kif-btn-clear,
        #kif-unique-cal .kif-price-inputs span {
            margin: 0 !important;
            position: static !important;
            transform: none !important; 
            max-width: 100% !important;
            min-width: 0 !important; 
        }

        /* BIAŁE PIGUŁKI */
        #kif-unique-cal .kif-filter select,
        #kif-unique-cal .kif-filter input[type="text"],
        #kif-unique-cal .kif-filter input[type="number"],
        #kif-unique-cal .kif-btn-clear {
            width: 100% !important;
            height: 36px !important; 
            padding: 0 10px !important;
            border-radius: 25px !important; 
            border: 1px solid #e0e0e0 !important; 
            background: #ffffff !important; 
            color: #222222 !important;
            font-size: 0.8rem !important; 
            font-weight: 500 !important;
            box-sizing: border-box !important;
            transition: all 0.2s !important;
            -webkit-appearance: none !important; 
            appearance: none !important;
        }

        /* STRZAŁKA W SELECTACH (Zmniejszony padding żeby pomieścić tekst) */
        #kif-unique-cal .kif-filter select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23333333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 8px center !important;
            padding-right: 20px !important;
            cursor: pointer !important;
            text-align: center !important;
        }
        
        #kif-unique-cal .kif-filter select:focus,
        #kif-unique-cal .kif-filter input:focus {
            border-color: var(--fcp-accent, #ff7a1c) !important;
            outline: 2px solid var(--fcp-accent, #ff7a1c) !important;
            outline-offset: 1px !important;
        }

        /* SEKCJA Z CENAMI OD - DO (Zwężona i zbita) */
        #kif-unique-cal .kif-price-inputs {
            display: grid !important;
            grid-template-columns: 1fr auto 1fr !important;
            align-items: center !important;
            gap: 4px !important;
            width: 100% !important;
        }
        #kif-unique-cal .kif-price-inputs input {
            padding: 0 4px !important;
            text-align: center !important;
            width: 100% !important;
        }
        #kif-unique-cal .kif-price-inputs span {
            color: inherit !important;
            opacity: 0.6 !important;
            font-weight: bold !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* PRZYCISK CZYSZCZENIA (Bez ikony) */
        #kif-unique-cal .kif-btn-clear {
            cursor: pointer !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            border-color: #777 !important;
            color: #777 !important;
            white-space: nowrap !important;
            padding: 0 16px !important; 
        }
        #kif-unique-cal .kif-btn-clear:hover {
            background: #eee !important;
            color: #333 !important;
        }

        /* Ukrycie obcych wtyczek "upiększających" selecty */
        .select2-container, .nice-select, .kif-filter span.select2 { 
            display: none !important; 
        }

        /* WIDOK MOBILE - CZYSTA SIATKA (2 ELEMENTY NA LINIĘ) */
        @media (max-width: 768px) {
            #kif-unique-cal .kif-filters {
                grid-template-columns: 1fr 1fr !important; /* DWA W RZĘDZIE NA SZTYWNO */
            }

            #kif-unique-cal .kif-filter.kif-search-text,
            #kif-unique-cal .kif-filter.kif-filter-price,
            #kif-unique-cal .kif-filter.kif-filter-clear {
                grid-column: span 2 !important;
            }
            
            #kif-unique-cal .kif-btn-clear {
                margin-top: 5px !important;
            }

            #kif-unique-cal .kif-list {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
            }
            #kif-unique-cal .kif-event-card {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
    </style>

    <div id="kif-unique-cal" class="kif-cal kif-mode-rozszerzony"
         data-default-mode="rozszerzony"
         data-price-max="<?php echo esc_attr($max_price); ?>"
         data-step="<?php echo esc_attr(intval($settings['price_step'])); ?>"
         data-grid="<?php echo esc_attr($settings['grid_columns']); ?>">

      <div class="kif-toolbar">
        
        <div class="kif-quick-filters">
          <button type="button" class="kif-quick-btn active" data-range="all">Wszystkie</button>
          <button type="button" class="kif-quick-btn" data-range="today">Dzisiaj</button>
          <button type="button" class="kif-quick-btn" data-range="week">W tym tygodniu</button>
          <button type="button" class="kif-quick-btn" data-range="weekend">W ten weekend</button>
          <button type="button" class="kif-quick-btn" data-range="next_week">W przyszłym tygodniu</button>
        </div>

        <div class="kif-filters">
          <div class="kif-filter kif-search-text">
            <label for="kif-search">Szukaj</label>
            <input type="text" id="kif-search" class="kif-search-input" placeholder="Wpisz nazwę wydarzenia..." />
          </div>

          <div class="kif-filter"><label>Kraj</label>
            <select class="kif-filter-country">
              <option value="">Wszystkie</option>
              <?php foreach(array_keys($countries_filter) as $ctr){ echo '<option value="'.esc_attr($ctr).'">'.esc_html($ctr).'</option>'; } ?>
            </select>
          </div>

          <div class="kif-filter"><label>Miesiąc</label>
            <select class="kif-filter-month">
              <option value="">Wszystkie</option>
              <?php foreach($months as $k=>$label){ echo '<option value="'.esc_attr($k).'">'.esc_html($label).'</option>'; } ?>
            </select>
          </div>

          <div class="kif-filter"><label>Miasto</label>
            <select class="kif-filter-city">
              <option value="">Wszystkie</option>
              <?php foreach(array_keys($cities) as $c){ echo '<option value="'.esc_attr($c).'">'.esc_html($c).'</option>'; } ?>
            </select>
          </div>

          <div class="kif-filter"><label>Gatunek</label>
            <select class="kif-filter-genre">
              <option value="">Wszystkie</option>
              <?php foreach(array_keys($genres) as $g){ echo '<option value="'.esc_attr($g).'">'.esc_html($g).'</option>'; } ?>
            </select>
          </div>

          <div class="kif-filter"><label>Kategoria</label>
            <select class="kif-filter-type">
              <option value="">Wszystkie</option>
              <?php foreach(array_keys($types) as $t){ echo '<option value="'.esc_attr($t).'">'.esc_html($t).'</option>'; } ?>
            </select>
          </div>

          <div class="kif-filter kif-filter-price">
            <label>Cena biletu</label>
            <div class="kif-price-inputs">
              <input type="number" class="kif-price-min" placeholder="Od" min="0" />
              <span>-</span>
              <input type="number" class="kif-price-max" placeholder="Do" min="0" />
            </div>
          </div>

          <div class="kif-filter kif-filter-clear">
              <label>&nbsp;</label> 
              <button type="button" class="kif-btn-clear" title="Zresetuj filtry">Wyczyść</button>
          </div>
        </div>

      </div>

      <?php if(!empty($featured_ids)): ?>
        <section class="kif-featured">
          <div class="kif-featured-header">
            <span class="kif-badge-featured">POLECAMY</span>
          </div>
          <div class="kif-list kif-featured-list">
            <?php foreach($featured_ids as $id):
              $title = get_the_title($id);
              
              $date_raw = get_post_meta($id, '_kif_date', true);
              $date_end_raw = get_post_meta($id, '_kif_date_end', true);
              $time_raw = get_post_meta($id, '_kif_time', true);

              $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
              $date = $d1_parts[0];
              $time = $time_raw ?: (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '');

              $d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
              $date_end = $d2_parts[0];
              
              $mkey  = $date ? date_i18n('Y-m', strtotime($date)) : 'inne';

              $d1_disp = $date ? date('d.m.Y', strtotime($date)) : '';
              $d2_disp = $date_end ? date('d.m.Y', strtotime($date_end)) : '';
              $time_disp = $time ? ' | Start: ' . $time : '';
              
              $display_date_html = '';
              if ($d1_disp) {
                  if ($d2_disp && $d2_disp !== $d1_disp) {
                      $display_date_html = $d1_disp . ' - ' . $d2_disp . $time_disp;
                  } else {
                      $display_date_html = $d1_disp . $time_disp;
                  }
              }

              $country = get_post_meta($id, '_kif_country', true) ?: 'Polska';
              $country_data = function_exists('kif_get_country_data') ? kif_get_country_data($country) : ['flag'=>'🌍'];
              $currency = get_post_meta($id, '_kif_currency', true) ?: 'PLN';

              $city  = get_post_meta($id, '_kif_city', true);
              $venue = get_post_meta($id, '_kif_venue', true);
              $price_raw = get_post_meta($id, '_kif_price', true);
              $price_num = kif_price_to_int($price_raw);
              $genre_raw = get_post_meta($id, '_kif_genre', true);
              $thumb = get_the_post_thumbnail_url($id, 'medium_large');
              $category = get_post_meta($id, '_kif_category', true); 

              $on_sale     = get_post_meta($id, '_kif_on_sale', true) ?: 'tak';
              $sale_reason = get_post_meta($id, '_kif_sale_reason', true);
              $is_paid     = get_post_meta($id, '_kif_is_paid', true) ?: 'tak';
              $alebilet    = get_post_meta($id, '_kif_alebilet', true); 

              $headliners_raw  = (string) get_post_meta($id, '_kif_headliners', true);
              $headliners_arr  = array_filter(array_map('trim', explode(',', $headliners_raw)));
              $has_headliners  = count($headliners_arr) > 0;
              $heads = $has_headliners ? $headliners_arr : [];

              $lineup_raw = (string) get_post_meta($id, '_kif_lineup', true);
              $lineup_artists = array_filter(array_map('trim', explode("\n", $lineup_raw)));
            ?>
              <article class="kif-card kif-event-card kif-featured-card"
                       data-featured="1"
                       data-id="<?php echo esc_attr($id); ?>"
                       data-title="<?php echo esc_attr($title); ?>"
                       data-month="<?php echo esc_attr($mkey); ?>"
                       data-country="<?php echo esc_attr($country); ?>"
                       data-city="<?php echo esc_attr($city); ?>"
                       data-venue="<?php echo esc_attr($venue); ?>"
                       data-genre="<?php echo esc_attr($genre_raw); ?>"
                       data-type="<?php echo esc_attr($category); ?>"
                       data-price="<?php echo esc_attr($price_num); ?>"
                       data-date="<?php echo esc_attr($date); ?>" 
                       data-date-end="<?php echo esc_attr($date_end); ?>"
                       data-time="<?php echo esc_attr($time); ?>">

                <?php if($thumb): ?>
                  <div class="kif-card-thumb" style="position: relative !important; width: 100% !important; aspect-ratio: 1.91 / 1 !important; background: #000 !important; overflow: hidden !important; border-radius: 12px 12px 0 0 !important; padding: 0 !important; margin: 0 !important; display: block !important; z-index: 1 !important;">
                    <div style="position: absolute !important; top: -10% !important; left: -10% !important; right: -10% !important; bottom: -10% !important; background-image: url('<?php echo esc_url($thumb); ?>') !important; background-size: cover !important; background-position: center !important; filter: blur(20px) brightness(0.4) !important; z-index: 2 !important;"></div>
                    <div style="position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-image: url('<?php echo esc_url($thumb); ?>') !important; background-size: contain !important; background-position: center !important; background-repeat: no-repeat !important; z-index: 3 !important; filter: none !important; opacity: 1 !important;"></div>
                    <div class="kif-countdown kif-countdown-thumb kif-hidden" data-date="<?php echo esc_attr($date); ?>" style="position: absolute !important; top: 10px !important; left: 10px !important; bottom: auto !important; right: auto !important; z-index: 4 !important; white-space: nowrap !important; background: rgba(0,0,0,0.8) !important; color: #fff !important; padding: 6px 10px !important; border-radius: 6px !important; font-size: 12px !important; font-weight: bold !important; line-height: 1.2 !important; width: max-content !important; height: max-content !important; display: inline-block !important;"></div>
                  </div>
                <?php else: ?>
                  <div class="kif-countdown kif-countdown-inline kif-hidden" data-date="<?php echo esc_attr($date); ?>" style="margin:15px 15px 0 15px;"></div>
                <?php endif; ?>

                <div class="kif-card-body">
                  <h4 class="kif-card-title"><?php echo esc_html($title); ?></h4>

                  <?php if($has_headliners): ?>
                    <div class="kif-headliners-row">
                      <span class="kif-headliners-label"><?php echo count($heads) === 1 ? 'Headliner:' : 'Headlinerzy:'; ?></span>
                      <div class="kif-tags">
                        <?php foreach($heads as $hn){ echo '<span class="kif-tag headliner">'.esc_html($hn).'</span>'; } ?>
                      </div>
                    </div>
                  <?php elseif(!empty($lineup_artists)): ?>
                    <div class="kif-headliners-row">
                      <span class="kif-headliners-label"><?php echo count($lineup_artists) === 1 ? 'Wystąpi:' : 'Wystąpią:'; ?></span>
                      <div class="kif-tags">
                        <?php
                          $preview = array_slice($lineup_artists, 0, 3);
                          foreach($preview as $artist){ echo '<span class="kif-tag">'.esc_html($artist).'</span>'; }
                          if(count($lineup_artists) > 3){ echo '<span class="kif-headliners-more"> i nie tylko</span>'; }
                        ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="kif-card-meta">
                    <?php
                    if($is_paid === 'nie'){
                        echo '<div><span class="kif-badge-sale kif-badge-free">WSTĘP FREE</span></div>';
                    } elseif($on_sale === 'nie'){
                        if($sale_reason === 'sprzedaz_nie_ruszyla'){
                            echo '<div><span class="kif-badge-sale kif-badge-upcoming">SPRZEDAŻ WKRÓTCE</span></div>';
                        } elseif($sale_reason === 'wyprzedane'){
                            echo '<div><span class="kif-badge-sale kif-badge-soldout">SOLD OUT</span></div>';
                        }
                    }
                    ?>
                    <div><?php echo esc_html($display_date_html); ?></div>
                    <div>
                      <?php 
                      $loc_parts = array_filter([$city, $venue]);
                      echo esc_html($country_data['flag']) . ' ' . esc_html(implode(', ', $loc_parts)); 
                      ?>
                    </div>

                    <?php if($on_sale === 'tak' && $is_paid === 'tak' && $price_raw!==''): ?>
                      <div>Cena biletu: <?php echo esc_html($price_raw . ' ' . $currency); ?></div>
                    <?php endif; ?>

                    <?php
                    $genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));
                    if($genre_tags){
                        echo '<div class="kif-tags">';
                        foreach($genre_tags as $tg){ echo '<span class="kif-tag">'.esc_html($tg).'</span>'; }
                        echo '</div>';
                    } ?>
                  </div>
                  <div class="kif-card-actions">
                    <a href="<?php echo esc_url(get_permalink($id)); ?>" class="kif-expand kif-btn" style="text-align:center; text-decoration:none;">Więcej informacji</a>
                    <?php if($alebilet): ?>
                      <a href="<?php echo esc_url($alebilet); ?>" target="_blank" rel="noopener" class="kif-btn kif-btn-alebilet">Znajdź bilet na Alebilet</a>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <div class="kif-months kif-view-rozszerzony">
        <?php
        $cur='';
        foreach($events as $p){
            $id = $p->ID;
            $title = get_the_title($id);

            $date_raw = get_post_meta($id, '_kif_date', true);
            $date_end_raw = get_post_meta($id, '_kif_date_end', true);
            $time_raw = get_post_meta($id, '_kif_time', true);

            $d1_parts = explode('T', str_replace(' ', 'T', $date_raw));
            $date = $d1_parts[0];
            $time = $time_raw ?: (isset($d1_parts[1]) ? substr($d1_parts[1], 0, 5) : '');

            $d2_parts = explode('T', str_replace(' ', 'T', $date_end_raw));
            $date_end = $d2_parts[0];
            
            $mkey  = $date ? date_i18n('Y-m', strtotime($date)) : 'inne';
            $mlabel = $date ? date_i18n('F Y', strtotime($date)) : 'Inne';

            if($mkey !== $cur){
              if($cur !== '') echo '</div></section>';
              echo '<section class="kif-month" id="kif-month-'.esc_attr($mkey).'" data-month="'.esc_attr($mkey).'">
                    <div class="kif-month-header"><button class="kif-month-toggle" aria-expanded="true">▼</button><h3>'.esc_html($mlabel).'</h3></div>
                    <div class="kif-list">';
              $cur = $mkey;
            }

            $d1_disp = $date ? date('d.m.Y', strtotime($date)) : '';
            $d2_disp = $date_end ? date('d.m.Y', strtotime($date_end)) : '';
            $time_disp = $time ? ' | Start: ' . $time : '';
            
            $display_date_html = '';
            if ($d1_disp) {
                if ($d2_disp && $d2_disp !== $d1_disp) {
                    $display_date_html = $d1_disp . ' - ' . $d2_disp . $time_disp;
                } else {
                    $display_date_html = $d1_disp . $time_disp;
                }
            }

            $country = get_post_meta($id, '_kif_country', true) ?: 'Polska';
            $country_data = function_exists('kif_get_country_data') ? kif_get_country_data($country) : ['flag'=>'🌍'];
            $currency = get_post_meta($id, '_kif_currency', true) ?: 'PLN';

            $city  = get_post_meta($id,'_kif_city',true);
            $venue = get_post_meta($id,'_kif_venue',true);
            $price_raw = get_post_meta($id,'_kif_price',true);
            $price_num = kif_price_to_int($price_raw);
            $genre_raw = get_post_meta($id,'_kif_genre',true);
            $thumb = get_the_post_thumbnail_url($id,'medium_large');
            $category = get_post_meta($id, '_kif_category', true); 

            $on_sale     = get_post_meta($id, '_kif_on_sale', true) ?: 'tak';
            $sale_reason = get_post_meta($id, '_kif_sale_reason', true);
            $is_paid     = get_post_meta($id, '_kif_is_paid', true) ?: 'tak';
            $alebilet    = get_post_meta($id, '_kif_alebilet', true); 

            $headliners_raw  = (string) get_post_meta($id, '_kif_headliners', true);
            $headliners_arr  = array_filter(array_map('trim', explode(',', $headliners_raw)));
            $has_headliners  = count($headliners_arr) > 0;
            $heads = $has_headliners ? $headliners_arr : [];

            $lineup_raw = (string) get_post_meta($id, '_kif_lineup', true);
            $lineup_artists = array_filter(array_map('trim', explode("\n", $lineup_raw)));

            $genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));

            echo '<article class="kif-event-card"
                   data-id="'.esc_attr($id).'"
                   data-title="'.esc_attr($title).'"
                   data-month="'.esc_attr($mkey).'"
                   data-country="'.esc_attr($country).'"
                   data-city="'.esc_attr($city).'"
                   data-venue="'.esc_attr($venue).'"
                   data-genre="'.esc_attr($genre_raw).'"
                   data-type="'.esc_attr($category).'"
                   data-price="'.esc_attr($price_num).'"
                   data-date="'.esc_attr($date).'"
                   data-date-end="'.esc_attr($date_end).'"
                   data-time="'.esc_attr($time).'">';

            if($thumb) {
                echo '<div class="kif-card-thumb" style="position: relative !important; width: 100% !important; aspect-ratio: 1.91 / 1 !important; background: #000 !important; overflow: hidden !important; border-radius: 12px 12px 0 0 !important; padding: 0 !important; margin: 0 !important; display: block !important; z-index: 1 !important;">';
                echo '<div style="position: absolute !important; top: -10% !important; left: -10% !important; right: -10% !important; bottom: -10% !important; background-image: url(\''.esc_url($thumb).'\') !important; background-size: cover !important; background-position: center !important; filter: blur(20px) brightness(0.4) !important; z-index: 2 !important;"></div>';
                echo '<div style="position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-image: url(\''.esc_url($thumb).'\') !important; background-size: contain !important; background-position: center !important; background-repeat: no-repeat !important; z-index: 3 !important; filter: none !important; opacity: 1 !important;"></div>';
                echo '</div>';
            }
            
            echo '<div class="kif-card-body"><h4 class="kif-card-title">'.esc_html($title).'</h4>';

            if($has_headliners){
              $h_label = count($heads) === 1 ? 'Headliner:' : 'Headlinerzy:';
              echo '<div class="kif-headliners-row"><span class="kif-headliners-label">'.esc_html($h_label).'</span><div class="kif-tags">';
              foreach($heads as $hn){ echo '<span class="kif-tag headliner">'.esc_html($hn).'</span>'; }
              echo '</div></div>';
            } elseif(!empty($lineup_artists)){
              $l_label = count($lineup_artists) === 1 ? 'Wystąpi:' : 'Wystąpią:';
              echo '<div class="kif-headliners-row"><span class="kif-headliners-label">'.esc_html($l_label).'</span><div class="kif-tags">';
              $preview = array_slice($lineup_artists, 0, 3);
              foreach($preview as $artist){ echo '<span class="kif-tag">'.esc_html($artist).'</span>'; }
              if(count($lineup_artists) > 3){ echo '<span class="kif-headliners-more"> i nie tylko</span>'; }
              echo '</div></div>';
            }

            echo '<div class="kif-card-meta">';

            if($is_paid === 'nie'){
                echo '<div><span class="kif-badge-sale kif-badge-free">WSTĘP FREE</span></div>';
            } elseif($on_sale === 'nie'){
                if($sale_reason === 'sprzedaz_nie_ruszyla'){
                    echo '<div><span class="kif-badge-sale kif-badge-upcoming">SPRZEDAŻ WKRÓTCE</span></div>';
                } elseif($sale_reason === 'wyprzedane'){
                    echo '<div><span class="kif-badge-sale kif-badge-soldout">SOLD OUT</span></div>';
                }
            }

            echo '<div>' . esc_html($display_date_html) . '</div>';
            
            $loc_parts = array_filter([$city, $venue]);
            echo '<div>' . esc_html($country_data['flag']) . ' ' . esc_html(implode(', ', $loc_parts)) . '</div>';

            if($on_sale === 'tak' && $is_paid === 'tak' && $price_raw!==''){
                echo '<div> Cena biletu: '.esc_html($price_raw . ' ' . $currency).'</div>';
            }

            if($genre_tags){
                echo '<div class="kif-tags">';
                foreach($genre_tags as $tg){ echo '<span class="kif-tag">'.esc_html($tg).'</span>'; }
                echo '</div>';
            }
            
            echo '</div><div class="kif-card-actions">';
            echo '<a href="'.esc_url(get_permalink($id)).'" class="kif-expand kif-btn" style="text-align:center; text-decoration:none;">Więcej informacji</a>';
            if($alebilet) {
                echo '<a href="'.esc_url($alebilet).'" target="_blank" rel="noopener" class="kif-btn kif-btn-alebilet">Znajdź bilet na Alebilet</a>';
            }
            echo '</div></article>';
        }
        if($cur!=='') echo '</div></section>';
        ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('festival_calendar','kif_calendar_shortcode');
