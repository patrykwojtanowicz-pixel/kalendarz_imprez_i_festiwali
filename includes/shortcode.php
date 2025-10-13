<?php
if (!defined('ABSPATH')) exit;

function kif_calendar_shortcode($atts){
    $atts = shortcode_atts(['mode'=>'prosty'], $atts, 'festival_calendar');
    $settings = kif_get_settings();

    $events = get_posts([
        'post_type'=>'festival_event','post_status'=>'publish','numberposts'=>-1,
        'meta_key'=>'_kif_date','orderby'=>'meta_value','order'=>'ASC'
    ]);
    $months=$cities=$venues=$genres=$types=[];
    $max_price=0;
    foreach($events as $p){
        $d=get_post_meta($p->ID,'_kif_date',true);
        if($d){ $k=date_i18n('Y-m', strtotime($d)); $months[$k]=date_i18n('F Y', strtotime($d)); }
        $price=kif_price_to_int(get_post_meta($p->ID,'_kif_price',true)); if($price>$max_price) $max_price=$price;
        $c=get_post_meta($p->ID,'_kif_city',true); if($c) $cities[$c]=true;
        $v=get_post_meta($p->ID,'_kif_venue',true); if($v) $venues[$v]=true;
        $g=get_post_meta($p->ID,'_kif_genre',true);
        if($g){ foreach(preg_split('/[,;]+/',$g) as $tg){ $tg=trim($tg); if($tg) $genres[$tg]=true; } }
        $ts=wp_get_post_terms($p->ID,'event_type',['fields'=>'names']); foreach($ts as $t){ $types[$t]=true; }
    }
    if($max_price<=0) $max_price=1000;
    $max_price = ceil($max_price/100)*100;

    $featured_ids=[];
    foreach($events as $p){ if(get_post_meta($p->ID,'_kif_featured',true)) $featured_ids[]=$p->ID; }

    ob_start(); ?>
    <div class="kif-cal kif-mode-<?php echo esc_attr($atts['mode']); ?>" data-default-mode="<?php echo esc_attr($atts['mode']); ?>" data-price-max="<?php echo esc_attr($max_price); ?>" data-step="<?php echo esc_attr(intval($settings['price_step'])); ?>" data-grid="<?php echo esc_attr($settings['grid_columns']); ?>">
      <div class="kif-toolbar">
        <div class="kif-view-toggle" role="group" aria-label="Przełącznik trybu widoku">
          <button class="kif-view-btn" data-mode="prosty" aria-pressed="true">Tryb prosty</button>
          <button class="kif-view-btn" data-mode="rozszerzony" aria-pressed="false">Tryb rozszerzony</button>
        </div>
        <div class="kif-filters">
          <div class="kif-filter"><label>Miesiąc</label>
            <select class="kif-filter-month"><option value="">Wszystkie</option>
            <?php foreach($months as $k=>$label){ echo '<option value="'.esc_attr($k).'">'.esc_html($label).'</option>'; } ?>
            </select>
          </div>
          <div class="kif-filter"><label>Miasto</label>
            <select class="kif-filter-city"><option value="">Wszystkie</option>
            <?php foreach(array_keys($cities) as $c){ echo '<option value="'.esc_attr($c).'">'.esc_html($c).'</option>'; } ?>
            </select>
          </div>
          <div class="kif-filter"><label>Lokalizacja</label>
            <select class="kif-filter-venue"><option value="">Wszystkie</option>
            <?php foreach(array_keys($venues) as $v){ echo '<option value="'.esc_attr($v).'">'.esc_html($v).'</option>'; } ?>
            </select>
          </div>
          <div class="kif-filter"><label>Gatunek</label>
            <select class="kif-filter-genre"><option value="">Wszystkie</option>
            <?php foreach(array_keys($genres) as $g){ echo '<option value="'.esc_attr($g).'">'.esc_html($g).'</option>'; } ?>
            </select>
          </div>
          <div class="kif-filter"><label>Typ imprezy</label>
            <select class="kif-filter-type"><option value="">Wszystkie</option>
            <?php foreach(array_keys($types) as $t){ echo '<option value="'.esc_attr($t).'">'.esc_html($t).'</option>'; } ?>
            </select>
          </div>
          <div class="kif-filter kif-filter-price">
            <label>Cena biletu</label>
            <div class="kif-price-label">Pokaż do <?php echo esc_html($max_price); ?> PLN</div>
            <div class="kif-price-slider"><input type="range" class="kif-range-max" min="0" max="<?php echo esc_attr($max_price); ?>" step="<?php echo esc_attr(intval($settings['price_step'])); ?>" value="<?php echo esc_attr($max_price); ?>"></div>
          </div>
        </div>
      </div>

      <?php if(!empty($featured_ids)): ?>
      <section class="kif-featured">
        <div class="kif-featured-header"><span class="kif-badge-featured">POLECAMY</span></div>
        <div class="kif-list kif-featured-list">
        <?php foreach($featured_ids as $id):
            $title=get_the_title($id);
            $date=get_post_meta($id,'_kif_date',true);
            $mkey=$date?date_i18n('Y-m', strtotime($date)):'inne';
            $city=get_post_meta($id,'_kif_city',true);
            $venue=get_post_meta($id,'_kif_venue',true);
            $price_raw=get_post_meta($id,'_kif_price',true);
            $price_num=kif_price_to_int($price_raw);
            $genre_raw=get_post_meta($id,'_kif_genre',true);
            $thumb=get_the_post_thumbnail_url($id,'medium_large');
            $types_arr=wp_get_post_terms($id,'event_type',['fields'=>'names']);
            $heads_more=0; $heads=kif_headliners_array($id,3,$heads_more);
        ?>
          <article class="kif-card kif-featured-card" data-featured="1" data-id="<?php echo esc_attr($id); ?>" data-month="<?php echo esc_attr($mkey); ?>" data-city="<?php echo esc_attr($city); ?>" data-venue="<?php echo esc_attr($venue); ?>" data-genre="<?php echo esc_attr($genre_raw); ?>" data-type="<?php echo esc_attr(implode(',', $types_arr)); ?>" data-price="<?php echo esc_attr($price_num); ?>">
            <?php if($thumb): ?><div class="kif-card-thumb"><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>"></div><?php endif; ?>
            <div class="kif-card-body">
              <h4 class="kif-card-title"><?php echo esc_html($title); ?></h4>
              <div class="kif-headliners-row">
                <span class="kif-headliners-label">Headlinerzy:</span>
                <div class="kif-tags">
                  <?php foreach($heads as $hn){ echo '<span class="kif-tag headliner">'.esc_html($hn).'</span>'; } ?>
                  <?php if($heads_more>0){ echo '<span class="kif-headliners-more"> i nie tylko</span>'; } ?>
                </div>
              </div>
              <div class="kif-card-meta">
                <div><?php echo esc_html(kif_fmt_dt($date)); ?></div>
                <div><?php echo esc_html(trim(($venue?$venue:'').(($venue&&$city)?', ':'').($city?$city:''))); ?></div>
                <div class="kif-type"><?php echo !empty($types_arr)?('Typ: '.esc_html($types_arr[0])):''; ?></div>
                <?php if($price_raw!==''): ?><div>Cena biletu: <?php echo esc_html($price_raw); ?> PLN</div><?php endif; ?>
                <?php $genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));
                if($genre_tags){
                    echo '<div class="kif-tags">';
                    foreach($genre_tags as $tg){ echo '<span class="kif-tag">'.esc_html($tg).'</span>'; }
                    echo '</div>';
                } ?>
              </div>
              <div class="kif-card-actions"><button class="kif-expand">Więcej informacji</button></div>
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
            $id=$p->ID; if(get_post_meta($id,'_kif_featured',true)) continue;
            $title=get_the_title($id); $date=get_post_meta($id,'_kif_date',true);
            $mkey=$date?date_i18n('Y-m', strtotime($date)):'inne'; $mlabel=$date?date_i18n('F Y', strtotime($date)):'Inne';
            if($mkey!==$cur){ if($cur!==''){ echo '</div></section>'; } echo '<section class="kif-month" id="kif-month-'.esc_attr($mkey).'" data-month="'.esc_attr($mkey).'"><div class="kif-month-header"><button class="kif-month-toggle" aria-expanded="true">▼</button><h3>'.esc_html($mlabel).'</h3></div><div class="kif-list">'; $cur=$mkey; }
            $city=get_post_meta($id,'_kif_city',true); $venue=get_post_meta($id,'_kif_venue',true);
            $price_raw=get_post_meta($id,'_kif_price',true); $price_num=kif_price_to_int($price_raw); $genre_raw=get_post_meta($id,'_kif_genre',true);
            $thumb=get_the_post_thumbnail_url($id,'medium_large'); $types_arr=wp_get_post_terms($id,'event_type',['fields'=>'names']);
            $heads_more=0; $heads=kif_headliners_array($id,3,$heads_more);
            $genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));
            echo '<article class="kif-event-card" data-featured="0" data-id="'.esc_attr($id).'" data-month="'.esc_attr($mkey).'" data-city="'.esc_attr($city).'" data-venue="'.esc_attr($venue).'" data-genre="'.esc_attr($genre_raw).'" data-type="'.esc_attr(implode(',', $types_arr)).'" data-price="'.esc_attr($price_num).'">';
            if($thumb){ echo '<div class="kif-card-thumb"><img src="'.esc_url($thumb).'" alt="'.esc_attr($title).'"></div>'; }
            echo '<div class="kif-card-body"><h4 class="kif-card-title">'.esc_html($title).'</h4>';
            echo '<div class="kif-headliners-row"><span class="kif-headliners-label">Headlinerzy:</span><div class="kif-tags">';
            foreach($heads as $hn){ echo '<span class="kif-tag headliner">'.esc_html($hn).'</span>'; } if($heads_more>0){ echo '<span class="kif-headliners-more"> i nie tylko</span>'; }
            echo '</div></div>';
            echo '<div class="kif-card-meta"><div>'.esc_html(kif_fmt_dt($date)).'</div>';
            $place = trim(($venue?$venue:'').(($venue&&$city)?', ':'').($city?$city:'')); if($place) echo '<div>'.esc_html($place).'</div>';
            echo '<div class="kif-type">'.(!empty($types_arr)?('Typ: '.esc_html($types_arr[0])):'').'</div>';
            if($price_raw!=='') echo '<div> Cena biletu: '.esc_html($price_raw).' PLN</div>';
            if($genre_tags){
                echo '<div class="kif-tags">';
                foreach($genre_tags as $tg){ echo '<span class="kif-tag">'.esc_html($tg).'</span>'; }
                echo '</div>';
            }
            echo '</div><div class="kif-card-actions"><button class="kif-expand">Więcej informacji</button></div></article>';
        }
        if($cur!==''){ echo '</div></section>'; }
        ?>
      </div>

      <div class="kif-simple kif-view-prosty">
        <div class="kif-list">
        <?php
        foreach($events as $p){
            $id=$p->ID; if(get_post_meta($id,'_kif_featured',true)) continue;
            $title=get_the_title($id); $date=get_post_meta($id,'_kif_date',true);
            $mkey=$date?date_i18n('Y-m', strtotime($date)):'inne';
            $city=get_post_meta($id,'_kif_city',true); $venue=get_post_meta($id,'_kif_venue',true);
            $price_raw=get_post_meta($id,'_kif_price',true); $price_num=kif_price_to_int($price_raw); $genre_raw=get_post_meta($id,'_kif_genre',true);
            $thumb=get_the_post_thumbnail_url($id,'medium_large'); $types_arr=wp_get_post_terms($id,'event_type',['fields'=>'names']);
            $heads_more=0; $heads=kif_headliners_array($id,3,$heads_more);
            $genre_tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$genre_raw)));
            echo '<article class="kif-event-card" data-featured="0" data-id="'.esc_attr($id).'" data-month="'.esc_attr($mkey).'" data-city="'.esc_attr($city).'" data-venue="'.esc_attr($venue).'" data-genre="'.esc_attr($genre_raw).'" data-type="'.esc_attr(implode(',', $types_arr)).'" data-price="'.esc_attr($price_num).'">';
            if($thumb){ echo '<div class="kif-card-thumb"><img src="'.esc_url($thumb).'" alt="'.esc_attr($title).'"></div>'; }
            echo '<div class="kif-card-body"><h4 class="kif-card-title">'.esc_html($title).'</h4>';
            echo '<div class="kif-headliners-row"><span class="kif-headliners-label">Headlinerzy:</span><div class="kif-tags">';
            foreach($heads as $hn){ echo '<span class="kif-tag headliner">'.esc_html($hn).'</span>'; } if($heads_more>0){ echo '<span class="kif-headliners-more"> i nie tylko</span>'; }
            echo '</div></div>';
            echo '<div class="kif-card-meta"><div>'.esc_html(kif_fmt_dt($date)).'</div>';
            $place = trim(($venue?$venue:'').(($venue&&$city)?', ':'').($city?$city:'')); if($place) echo '<div>'.esc_html($place).'</div>';
            echo '<div class="kif-type">'.(!empty($types_arr)?('Typ: '.esc_html($types_arr[0])):'').'</div>';
            if($price_raw!=='') echo '<div> Cena biletu: '.esc_html($price_raw).' PLN</div>';
            if($genre_tags){
                echo '<div class="kif-tags">';
                foreach($genre_tags as $tg){ echo '<span class="kif-tag">'.esc_html($tg).'</span>'; }
                echo '</div>';
            }
            echo '</div><div class="kif-card-actions"><button class="kif-expand">Więcej informacji</button></div></article>';
        }
        ?>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('festival_calendar','kif_calendar_shortcode');
