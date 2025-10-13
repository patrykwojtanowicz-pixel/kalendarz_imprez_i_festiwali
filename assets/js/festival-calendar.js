(function($){
  var STORAGE_KEY='kif_view_mode';

  function updatePriceLabel($root){
    var max=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10);
    $root.find('.kif-price-label').text('Pokaż do '+max+' PLN');
  }

  function applyFilters($root){
    var m=$root.find('.kif-filter-month').val()||'',
        c=$root.find('.kif-filter-city').val()||'',
        v=$root.find('.kif-filter-venue').val()||'',
        g=$root.find('.kif-filter-genre').val()||'',
        t=$root.find('.kif-filter-type').val()||'',
        pmax=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10);

    function match($card){
      var cm=$card.data('month')||'',
          cc=$card.data('city')||'',
          cv=$card.data('venue')||'',
          cg=($card.data('genre')||'').toString(),
          ct=($card.data('type')||'').toString(),
          cp=parseInt($card.data('price')||'0',10);
      if(m && String(cm)!==String(m)) return false;
      if(c && String(cc)!==String(c)) return false;
      if(v && String(cv)!==String(v)) return false;
      if(g){
        var gs=cg.split(/[,;]+/).map(function(s){return s.trim();});
        if(gs.indexOf(g)===-1) return false;
      }
      if(t){
        var ts=ct.split(/[,;]+/).map(function(s){return s.trim();});
        if(ts.indexOf(t)===-1) return false;
      }
      if(cp>pmax) return false;
      return true;
    }

    var $featCards=$root.find('.kif-featured .kif-card');
    var anyFeat=false;
    $featCards.each(function(){ var ok=match($(this)); $(this).toggleClass('kif-hidden', !ok); if(ok) anyFeat=true; });
    $root.find('.kif-featured').toggleClass('kif-hidden', !anyFeat);

    var $cards=$root.find('.kif-event-card');
    $cards.each(function(){ var ok=match($(this)); $(this).toggleClass('kif-hidden', !ok); });
  }

  function setMode($root, mode){
    $root.removeClass('kif-show-prosty kif-show-rozszerzony').addClass(mode==='rozszerzony'?'kif-show-rozszerzony':'kif-show-prosty');
    $root.find('.kif-view-btn').attr('aria-pressed','false').filter('[data-mode="'+mode+'"]').attr('aria-pressed','true');
    try{ localStorage.setItem(STORAGE_KEY, mode); }catch(e){}
  }

  function openModal(id){
    $.get(kifAjax.ajax_url,{action:'kif_get_event_details',nonce:kifAjax.nonce,id:id},function(r){
      if(!r||!r.success) return; var ev=r.data;
      function fmt(iso){ if(!iso) return ''; var d=new Date(iso); if(isNaN(d)) return iso; var p=n=>('0'+n).slice(-2); return p(d.getDate())+'.'+p(d.getMonth()+1)+'.'+d.getFullYear()+', '+p(d.getHours())+':'+p(d.getMinutes()); }
      var date=fmt(ev.date), where=[ev.location,ev.city].filter(Boolean).join(', '), priceText = ev.price ? (ev.price.toString().trim()+' PLN') : '';

      // oficjalne kolory/logotypy (SVG)
      function icon(n){var I={
        fb:'<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 8h2V6a3 3 0 0 1 3-3h2v3h-2v2h2v3h-2v7h-3v-7H9z"/></svg>',
        msgr:'<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.5 2 2 6.2 2 11.5c0 2.6 1 4.9 2.7 6.6L4 22l3.8-2c1.3.4 2.7.7 4.2.7 5.5 0 10-4.2 10-9.5S17.5 2 12 2zm.9 12.4l-2.5-2.6-4.3 2.6 5-5.4 2.4 2.5 4.4-2.5-5 5.4z"/></svg>',
        x:'<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 3l7.5 9.5L3.5 21H6l6-7 6 7h2.5l-6.9-8.5L21 3h-2.4l-6 6.9L6.5 3H3z"/></svg>',
        ig:'<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 2.5a5.5 5.5 0 1 1-5.5 5.5A5.5 5.5 0 0 1 12 6.5zm6-1a1 1 0 1 1-1 1 1 0 0 1 1-1z"/></svg>',
        wa:'<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M20 4.6A10 10 0 1 0 3.4 20L2 22l2-.5A10 10 0 1 0 20 4.6zM12 20a8 8 0 0 1-4-.9l-.3-.1-2.8.7.7-2.7-.2-.3A8 8 0 1 1 12 20z"/></svg>'
      }; return I[n]||''; }
      function share(u,t){var te=encodeURIComponent(t||''), e=encodeURIComponent(u||location.href);
        return '<div class="kif-share"><span class="label">Udostępnij:</span>'
          +'<a class="kif-share-btn fb" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='+e+'">'+icon('fb')+'</a>'
          +'<a class="kif-share-btn msgr" target="_blank" rel="noopener" href="https://www.facebook.com/dialog/send?app_id=174829003346&link='+e+'&redirect_uri='+e+'">'+icon('msgr')+'</a>'
          +'<a class="kif-share-btn x" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='+e+'&text='+te+'">'+icon('x')+'</a>'
          +'<button class="kif-share-btn ig" data-copy="'+(u||'')+'" title="Skopiuj link">'+icon('ig')+'</button>'
          +'<a class="kif-share-btn wa" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text='+te+'%20-%20'+e+'">'+icon('wa')+'</a>'
          +'</div>';
      }

      function tagsFromList(list, headsCsv){
        var out='',heads=(headsCsv||'').split(',').map(function(s){return s.trim().toLowerCase();}).filter(Boolean);
        (list||'').split(/\r?\n/).map(function(s){return s.trim();}).filter(Boolean).forEach(function(n){
          var isH=heads.indexOf(n.toLowerCase())>=0;
          out+='<span class="kif-tag'+(isH?' headliner':'')+'">'+$('<div>').text(n).html()+'</span>';
        });
        return out||'<span class="kif-tag">Brak lineup’u</span>';
      }
      function fullHeadlinersSplit(list, headsCsv){
        var all=(list||'').split(/\r?\n/).map(function(s){return s.trim();}).filter(Boolean);
        var heads=(headsCsv||'').split(',').map(function(s){return s.trim();}).filter(Boolean);
        var rest=all.filter(function(n){return heads.map(function(h){return h.toLowerCase();}).indexOf(n.toLowerCase())<0;});
        var h1=heads.length?('<div class="kif-tags">'+heads.map(function(h){return '<span class="kif-tag headliner">'+$('<div>').text(h).html()+'</span>';}).join(' ')+'</div>'):'';
        var h2=rest.length?('<div class="kif-tags">'+rest.map(function(r){return '<span class="kif-tag">'+$('<div>').text(r).html()+'</span>';}).join(' ')+'</div>'):'';
        return (h1||h2)?(h1+(h2?('<div style="margin-top:.35rem"></div>'+h2):'')):'<div class="kif-tags"><span class="kif-tag">Brak lineup’u</span></div>';
      }
      function daysStages(jsonStr){try{var o=JSON.parse(jsonStr||'{}'),h='';Object.keys(o).forEach(function(day){h+='<div class="kif-day"><div class="kif-day-title">'+$('<div>').text(day).html()+'</div><div class="kif-tags">';var st=o[day]||{};Object.keys(st).forEach(function(s){h+='<div><strong>'+$('<div>').text(s).html()+':</strong> '+(st[s]||[]).map(function(n){return '<span class="kif-tag">'+$('<div>').text(n).html()+'</span>';}).join(' ')+'</div>';});h+='</div></div>';});return h||'<div>Brak danych.</div>'}catch(e){return '<div>Błędny JSON timetable.</div>';} }
      function timetable(jsonStr){try{var d=JSON.parse(jsonStr||'{}'),h='';Object.keys(d).forEach(function(day){h+='<div class="kif-timetable-wrap"><div class="kif-day-title">'+$('<div>').text(day).html()+'</div>';var st=Object.keys(d[day]||{}),times={};st.forEach(function(s){(d[day][s]||[]).forEach(function(x){times[x.time]=true;});});var tl=Object.keys(times).sort();h+='<div class="kif-grid">';tl.forEach(function(t){h+='<div class="kif-time-col">'+t+'</div>';st.forEach(function(s){var slot=(d[day][s]||[]).find(function(x){return x.time===t;}); if(slot){h+='<div class="kif-slot">'+$('<div>').text(slot.artist).html()+'<br><small>'+$('<div>').text(s).html()+'</small></div>';} else {h+='<div class="kif-slot" style="opacity:.35">—</div>';}});});h+='</div></div>';});return h||'<div>Brak timetable.</div>'}catch(e){return '<div>Błędny JSON timetable.</div>';} }

      var lineupHtml='';
      switch(ev.lineup_mode||'full'){
        case 'full_headliners': lineupHtml=fullHeadlinersSplit(ev.lineup||'', ev.headliners||''); break;
        case 'headliners_only': lineupHtml=fullHeadlinersSplit((ev.headliners||'').split(',').join('\\n'), ev.headliners||''); break;
        case 'days_stages': lineupHtml=daysStages(ev.timetable_json||'{}'); break;
        case 'timetable': lineupHtml=timetable(ev.timetable_json||'{}'); break;
        default: lineupHtml=tagsFromList(ev.lineup||'', ev.headliners||'');
      }

      var $m=$('<div class="kif-overlay" role="dialog" aria-modal="true">\
        <div class="kif-modal">\
          <button class="kif-modal-close" aria-label="Zamknij">×</button>\
          <header class="kif-modal-header">\
            <div>\
              <h2 class="kif-modal-title">'+$('<div>').text(ev.title).html()+'</h2>\
              <div class="kif-meta">'+([where,date].filter(Boolean).join(' | '))+(ev.types&&ev.types.length?(' | Typ: '+$('<div>').text(ev.types[0]).html()):'')+'</div>\
            </div>\
            <div class="kif-price-box">'+(priceText?('<div class="kif-price-amount">💳 '+$('<div>').text(priceText).html()+'</div>'):'')+(ev.ticket?('<a class="kif-btn kif-buy" target="_blank" rel="noopener" href="'+ev.ticket+'">Kup bilet</a>'):'')+'</div>\
          </header>\
          <div class="kif-modal-body">'+(ev.thumb?('<img class="kif-thumb" src="'+ev.thumb+'" alt="">'):'')+(lineupHtml?('<div class="kif-lineup"><h3>Line-up</h3>'+lineupHtml+'</div>'):'')+'\
            <div class="kif-description" contenteditable="true" data-id="'+ev.id+'">'+(ev.content||'')+'</div>'+(ev.custom_desc?('<div class="kif-custom-desc"><h3>Dodatkowy opis</h3><p>'+ $('<div>').text(ev.custom_desc).html() +'</p></div>'):'')+'\
          </div>\
          <div class="kif-modal-footer">\
            <button class="kif-btn kif-save" data-id="'+ev.id+'">💾 Zapisz opis</button>'+share(ev.permalink, ev.title)+'\
            <div class="kif-genres">'+(function(){var out=''; (ev.genre||'').split(/[,;]+/).map(function(s){return s.trim();}).filter(Boolean).forEach(function(g){ out+='<span class=\"kif-tag\">'+$('<div>').text(g).html()+'</span>'; }); return out;})()+'</div>\
          </div>\
        </div>\
      </div>');

      $('body').append($m);

      function close(){ $m.find('.kif-modal').css('animation','kifModalOut .2s ease-in forwards'); $m.css('animation','kifFadeOut .2s ease forwards'); setTimeout(function(){ $m.remove(); }, 200); }
      $m.on('click', function(e){ if(e.target===this) close(); });
      $m.find('.kif-modal-close').on('click', close);
      $(document).on('keydown.kifEsc', function(e){ if(e.key==='Escape'){ close(); $(document).off('keydown.kifEsc'); } });

      $m.on('click','.kif-ig',function(){ var link=$(this).data('copy')||location.href; if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(link).then(function(){ alert(kifAjax.copyOk); }); } });

      $m.on('click','.kif-save',function(){ var id=$(this).data('id'); var html=$m.find('.kif-description').html(); $.post(kifAjax.ajax_url,{action:'kif_update_event_description',nonce:kifAjax.nonce,id:id,html:html},function(res){ alert(res&&res.success?kifAjax.saveOk:kifAjax.saveErr); }); });
    });
  }

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

    $root.on('change input', '.kif-filter-month,.kif-filter-city,.kif-filter-venue,.kif-filter-genre,.kif-filter-type,.kif-range-max', function(){
      updatePriceLabel($root); applyFilters($root);
    });

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
