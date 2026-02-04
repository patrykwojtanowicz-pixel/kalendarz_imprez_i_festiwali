(function($){
  var STORAGE_KEY='kif_view_mode';

  // -----------------------------
  // Aktualizacja etykiety suwaka
  // -----------------------------
  function updatePriceLabel($root){
    var max=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10);
    $root.find('.kif-price-label').text('Pokaż do '+max+' PLN');
  }

  // -----------------------------
  // Filtrowanie wydarzeń
  // -----------------------------
  function applyFilters($root){
    var m=$root.find('.kif-filter-month').val()||'',
        c=$root.find('.kif-filter-city').val()||'',
        g=$root.find('.kif-filter-genre').val()||'',
        t=$root.find('.kif-filter-type').val()||'',
        pmax=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10),
        q=($root.find('.kif-search-input').val()||'').toLowerCase().trim();

    function match($card){
      var cm=$card.data('month')||'',
          cc=$card.data('city')||'',
          cg=($card.data('genre')||'').toString(),
          ct=($card.data('type')||'').toString(),
          cp=parseInt($card.data('price')||'0',10);

      if(m && String(cm)!==String(m)) return false;
      if(c && String(cc)!==String(c)) return false;

      if(g){
        var gs=cg.split(/[,;]+/).map(function(s){return s.trim();});
        if(gs.indexOf(g)===-1) return false;
      }
      if(t){
        var ts=ct.split(/[,;]+/).map(function(s){return s.trim();});
        if(ts.indexOf(t)===-1) return false;
      }
      if(cp>pmax) return false;

      if(q){
        var txt = (
          ($card.data('title')||'')+' '+
          ($card.data('city')||'')+' '+
          ($card.data('venue')||'')+' '+
          ($card.data('genre')||'')
        ).toLowerCase();
        if(txt.indexOf(q)===-1) return false;
      }
      return true;
    }

    var $allCards = $root.find('.kif-event-card');
    $allCards.each(function(){
      var ok = match($(this));
      $(this).toggleClass('kif-hidden', !ok);
    });

    var anyFeatVisible = $root
      .find('.kif-featured .kif-event-card')
      .filter(':not(.kif-hidden)')
      .length > 0;

    $root.find('.kif-featured').toggleClass('kif-hidden', !anyFeatVisible);
  }

  // -----------------------------
  // Zmiana trybu widoku
  // -----------------------------
  function setMode($root, mode){
    $root.removeClass('kif-show-prosty kif-show-rozszerzony')
         .addClass(mode==='rozszerzony'?'kif-show-rozszerzony':'kif-show-prosty');
    $root.find('.kif-view-btn')
         .attr('aria-pressed','false')
         .filter('[data-mode="'+mode+'"]').attr('aria-pressed','true');
    try{ localStorage.setItem(STORAGE_KEY, mode); }catch(e){}
  }

  // -----------------------------
  // Otwieranie modala wydarzenia
  // -----------------------------
  function openModal(id){
    $.get(kifAjax.ajax_url,{action:'kif_get_event_details',nonce:kifAjax.nonce,id:id},function(r){
      if(!r||!r.success) return;
      var ev=r.data;

      function fmt(iso){
        if(!iso) return '';
        var d=new Date(iso); if(isNaN(d)) return iso;
        var p=n=>('0'+n).slice(-2);
        return p(d.getDate())+'.'+p(d.getMonth()+1)+'.'+d.getFullYear()+', '+p(d.getHours())+':'+p(d.getMinutes());
      }

      var dateStart = fmt(ev.date);
      var dateEnd = ev.date_end ? fmt(ev.date_end) : '';
      var dateDisplay = (dateEnd && dateEnd!==dateStart)
        ? dateStart + ' – ' + dateEnd
        : dateStart;

      var where = [ev.location, ev.city].filter(Boolean).join(', ');
      var priceText = ev.price ? (ev.price.toString().trim()+' PLN') : '';

      function esc(s){
        if (!s) return '';
        var txt = document.createElement('textarea');
        txt.innerHTML = s;
        var decoded = txt.value;
        return $('<div>').text(decoded).html();
      }

      var titleEsc = esc(ev.title);
      var whereEsc = esc(where);
      var typesEsc = (ev.types&&ev.types.length)?(' | Typ: '+esc(ev.types[0])):'';

      function tagsFromList(list, headsCsv){
        var out='',heads=(headsCsv||'').split(',').map(s=>s.trim().toLowerCase()).filter(Boolean);
        (list||'').split(/\r?\n/).map(s=>s.trim()).filter(Boolean).forEach(function(n){
          var isH = heads.indexOf(n.toLowerCase())>=0;
          out += `<span class="kif-tag${isH?' headliner':''}">${isH?'<strong>':''}${esc(n)}${isH?'</strong>':''}</span>`;
        });
        return out || '<p class="kif-lineup-placeholder">Line-up zostanie podany wkrótce...</p>';
      }

      function fullHeadlinersSplit(list, headsCsv){
        var all=(list||'').split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
        var heads=(headsCsv||'').split(',').map(s=>s.trim()).filter(Boolean);
        var headsLower=heads.map(h=>h.toLowerCase());
        var rest=all.filter(n=>headsLower.indexOf(n.toLowerCase())<0);

        var h1 = heads.length ? `<div class="kif-tags">${heads.map(h=>`<span class="kif-tag headliner"><strong>${esc(h)}</strong></span>`).join(' ')}</div>` : '';
        var h2 = rest.length ? `<div class="kif-tags">${rest.map(r=>`<span class="kif-tag">${esc(r)}</span>`).join(' ')}</div>` : '';
        return (h1||h2) ? (h1 + (h2 ? `<div style="margin-top:.35rem"></div>${h2}` : ''))
                        : '<p class="kif-lineup-placeholder">Line-up zostanie podany wkrótce...</p>';
      }

      var lineupHtml='';
      switch(ev.lineup_mode||'full'){
        case 'full_headliners': lineupHtml=fullHeadlinersSplit(ev.lineup||'', ev.headliners||''); break;
        case 'headliners_only': lineupHtml=fullHeadlinersSplit((ev.headliners||'').split(',').join('\n'), ev.headliners||''); break;
        default: lineupHtml=tagsFromList(ev.lineup||'', ev.headliners||'');
      }

      var genresHtml = ((ev.genre||'').split(/[,;]+/).map(s=>s.trim()).filter(Boolean)
                        .map(g=>`<span class="kif-tag">${esc(g)}</span>`).join(' '));

      // 🆕 Status sprzedaży / płatności
      var onSale = ev.on_sale || 'tak';
      var saleReason = ev.sale_reason || '';
      var isPaid = ev.is_paid || 'tak';

      // 🆕 Badge w modalu — dodany WSTĘP FREE
      var badgeHtml = '';
      if(isPaid === 'nie'){
        badgeHtml = `<div class="kif-badge-sale kif-badge-free" style="font-size:0.9rem;">WSTĘP FREE</div>`;
      } else if(onSale === 'nie'){
        if(saleReason === 'sprzedaz_nie_ruszyla'){
          badgeHtml = `<div class="kif-badge-sale kif-badge-upcoming" style="font-size:0.9rem;">SPRZEDAŻ WKRÓTCE</div>`;
        } else if(saleReason === 'wyprzedane'){
          badgeHtml = `<div class="kif-badge-sale kif-badge-soldout" style="font-size:0.9rem;">SOLD OUT</div>`;
        }
      }

      // 🆕 Logika wyświetlania ceny / badge
      var priceBoxHtml = '';
      if(isPaid === 'nie'){
        priceBoxHtml = badgeHtml;
      } else if(onSale === 'tak'){
        priceBoxHtml = `
          ${priceText ? `<div class="kif-price-amount">💳 ${esc(priceText)}</div>` : ``}
          ${ev.ticket ? `<a class="kif-btn kif-buy" target="_blank" rel="noopener" href="${esc(ev.ticket)}">Kup bilet</a>` : ``}
        `;
      } else {
        priceBoxHtml = badgeHtml;
      }

      if(ev.more_info && ev.more_info.trim()){
        priceBoxHtml += `<a class="kif-btn kif-more-info" target="_blank" rel="noopener" href="${esc(ev.more_info)}">Więcej informacji</a>`;
      }

      var metaLine = [whereEsc, dateDisplay].filter(Boolean).join(' | ') + typesEsc;

      var modalHtml = `
        <div class="kif-overlay" role="dialog" aria-modal="true">
          <button class="kif-modal-close" aria-label="Zamknij">×</button>
          <div class="kif-modal">
            <header class="kif-modal-header">
              <div>
<div class="kif-modal-title-row" style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
  <h2 class="kif-modal-title" style="margin:0;">${titleEsc}</h2>
  ${ev.featured ? `<span class="kif-badge-featured" style="background:#8e44ff;color:#fff;font-weight:700;font-size:.85rem;padding:4px 10px;border-radius:8px;display:inline-block;line-height:1;">POLECAMY</span>` : ``}
</div>
<div class="kif-meta">${metaLine}</div>
${genresHtml ? `<div class="kif-genres kif-genres-top">${genresHtml}</div>` : ``}

              </div>
              <div class="kif-price-box">${priceBoxHtml}</div>
            </header>
            <div class="kif-modal-body">
              ${ev.thumb ? `<img class="kif-thumb" src="${esc(ev.thumb)}" alt="">` : ``}
              ${lineupHtml ? `<div class="kif-lineup"><h3>Line-up</h3>${lineupHtml}</div>` : ``}
              <div class="kif-description" contenteditable="true" data-id="${ev.id}">${ev.content||''}</div>
              ${ev.custom_desc ? `<div class="kif-custom-desc"><h3>Dodatkowy opis</h3><p>${esc(ev.custom_desc)}</p></div>` : ``}
            </div>
          </div>
        </div>
      `;

      var $m=$(modalHtml);
      $('body').append($m);

      function close(){
        $m.find('.kif-modal').css('animation','kifModalOut .2s ease-in forwards');
        $m.css('animation','kifFadeOut .2s ease forwards');
        setTimeout(function(){ $m.remove(); },200);
      }

      $m.on('click', function(e){ if(e.target===this) close(); });
      $m.find('.kif-modal-close').on('click', close);
      $(document).on('keydown.kifEsc', function(e){ if(e.key==='Escape'){ close(); $(document).off('keydown.kifEsc'); } });

      $m.on('click','.kif-save',function(){
        var id=$(this).data('id');
        var html=$m.find('.kif-description').html();
        $.post(kifAjax.ajax_url,{
          action:'kif_update_event_description',
          nonce:kifAjax.nonce,
          id:id, html:html
        },function(res){
          alert(res&&res.success?kifAjax.saveOk:kifAjax.saveErr);
        });
      });
    });
  }

  // -----------------------------
  // Inicjalizacja
  // -----------------------------
  function init($root){
    var pmax = parseInt($root.data('price-max')||'1000',10);
    var step = parseInt($root.data('step')||'10',10);
    $root.find('.kif-range-max').attr({max:pmax, step:step}).val(pmax);
    updatePriceLabel($root);

    var defaultMode=$root.data('default-mode')||'prosty', saved=null;
    try{ saved=localStorage.getItem(STORAGE_KEY);}catch(e){}
    var mode=saved||defaultMode||'prosty';
    setMode($root, mode);

    $root.on('click','.kif-view-btn', function(){ setMode($root, $(this).data('mode')); });
    $root.on('change input', '.kif-filter-month,.kif-filter-city,.kif-filter-genre,.kif-filter-type,.kif-range-max', function(){
      updatePriceLabel($root);
      applyFilters($root);
    });
    $root.on('input', '.kif-search-input', function(){ applyFilters($root); });

    $root.on('click','.kif-month-toggle', function(){
      var $btn=$(this), $list=$btn.closest('.kif-month').find('>.kif-list');
      var open=$btn.attr('aria-expanded')==='true';
      $btn.attr('aria-expanded', open?'false':'true').text(open?'►':'▼');
      $list.slideToggle(160);
    });

    $root.on('click','.kif-expand', function(e){
      e.preventDefault();
      var id=$(this).closest('[data-id]').data('id');
      if(id) openModal(parseInt(id,10));
    });

    applyFilters($root);
  }

  $(function(){ $('.kif-cal').each(function(){ init($(this)); }); });

})(jQuery);
