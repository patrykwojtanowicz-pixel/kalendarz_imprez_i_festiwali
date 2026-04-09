(function($){
  var STORAGE_KEY='kif_view_mode';

  // -----------------------------
  // ✅ Silnik odliczania czasu na kafelkach
  // -----------------------------
  function updateCountdowns() {
    var now = new Date().getTime();
    $('.kif-countdown').each(function(){
      var $t = $(this);
      var dateStr = $t.attr('data-date'); 
      
      // KLUCZOWA POPRAWKA: Szuka atrybutu niezależnie od nazwy klasy, zapobiega uciekaniu do '00:00'
      var timeStr = $t.attr('data-time') || $t.closest('[data-time]').attr('data-time') || '00:00'; 
      if(!dateStr) return;
      
      var timePart = timeStr.length >= 5 ? timeStr.substring(0, 5) + ':00' : '00:00:00';
      var fullDateStr = String(dateStr).replace(' ', 'T') + 'T' + timePart;
      
      // Zabezpieczenie kompatybilności dat (szczególnie dla iOS Safari)
      var evDate = new Date(fullDateStr).getTime();
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
        
        $t.text(text).removeClass('kif-hidden kif-started');
      } else {
        $t.text('🎉 Impreza już ruszyła!').removeClass('kif-hidden').addClass('kif-started');
      }
    });
  }
  
  setInterval(updateCountdowns, 60000);

  // -----------------------------
  // Filtrowanie wydarzeń
  // -----------------------------
  function applyFilters($root){
    var m=$root.find('.kif-filter-month').val()||'',
        country=$root.find('.kif-filter-country').val()||'',
        c=$root.find('.kif-filter-city').val()||'',
        g=$root.find('.kif-filter-genre').val()||'',
        t=$root.find('.kif-filter-type').val()||'',
        
        valMin = $root.find('.kif-price-min').val(),
        valMax = $root.find('.kif-price-max').val(),
        pmin = valMin === '' ? 0 : parseInt(valMin, 10),
        pmax = valMax === '' ? Infinity : parseInt(valMax, 10),
        
        q=($root.find('.kif-search-input').val()||'').toLowerCase().trim(),
        quickRange = $root.find('.kif-quick-btn.active').data('range') || 'all';

    var now = new Date();
    now.setHours(0,0,0,0);
    var nowTime = now.getTime();
    var todayEnd = new Date(now);
    todayEnd.setHours(23,59,59,999);

    var dayOfWeek = now.getDay() || 7; 
    var weekStart = new Date(now);
    weekStart.setDate(now.getDate() - dayOfWeek + 1); 
    var weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6); 
    weekEnd.setHours(23,59,59,999);

    var weekendStart = new Date(weekStart);
    weekendStart.setDate(weekStart.getDate() + 4); 
    weekendStart.setHours(0,0,0,0);

    var nextWeekStart = new Date(weekStart);
    nextWeekStart.setDate(weekStart.getDate() + 7);
    var nextWeekEnd = new Date(nextWeekStart);
    nextWeekEnd.setDate(nextWeekStart.getDate() + 6);
    nextWeekEnd.setHours(23,59,59,999);

    function match($card){
      var cm=$card.data('month')||'',
          cCountry=$card.data('country')||'Polska',
          cc=$card.data('city')||'',
          cg=($card.data('genre')||'').toString(),
          ct=($card.data('type')||'').toString(),
          cp=parseInt($card.data('price')||'0',10);

      if(m && String(cm)!==String(m)) return false;
      if(country && String(cCountry)!==String(country)) return false;
      if(c && String(cc)!==String(c)) return false;
      if(g){
        var gs=cg.split(/[,;]+/).map(function(s){return s.trim();});
        if(gs.indexOf(g)===-1) return false;
      }
      if(t){
        var ts=ct.split(/[,;]+/).map(function(s){return s.trim();});
        if(ts.indexOf(t)===-1) return false;
      }
      if(cp < pmin || cp > pmax) return false;
      if(q){
        var txt = (($card.data('title')||'')+' '+cCountry+' '+($card.data('city')||'')+' '+($card.data('venue')||'')+' '+($card.data('genre')||'')).toLowerCase();
        if(txt.indexOf(q)===-1) return false;
      }

      if(quickRange !== 'all') {
        var dStartRaw = $card.attr('data-date');
        if(!dStartRaw) return false; 
        var dEndRaw = $card.attr('data-date-end') || dStartRaw;
        var evStart = new Date(dStartRaw + 'T00:00:00').getTime();
        var evEnd = new Date(dEndRaw + 'T23:59:59').getTime();

        if(quickRange === 'today') {
          if(evEnd < nowTime || evStart > todayEnd.getTime()) return false;
        } else if(quickRange === 'week') {
          if(evEnd < weekStart.getTime() || evStart > weekEnd.getTime()) return false;
        } else if(quickRange === 'weekend') {
          if(evEnd < weekendStart.getTime() || evStart > weekEnd.getTime()) return false;
        } else if(quickRange === 'next_week') {
          if(evEnd < nextWeekStart.getTime() || evStart > nextWeekEnd.getTime()) return false;
        }
      }
      return true;
    }

    var $allCards = $root.find('.kif-event-card');
    $allCards.each(function(){ $(this).toggleClass('kif-hidden', !match($(this))); });

    var anyFeatVisible = $root.find('.kif-featured .kif-event-card').filter(':not(.kif-hidden)').length > 0;
    $root.find('.kif-featured').toggleClass('kif-hidden', !anyFeatVisible);
    
    $root.find('.kif-month').each(function() {
      var $sec = $(this);
      if ($sec.find('.kif-event-card').filter(':not(.kif-hidden)').length === 0) {
          $sec.hide();
      } else {
          $sec.show();
      }
    });
  }

  function setMode($root, mode){
    $root.removeClass('kif-show-prosty kif-show-rozszerzony').addClass(mode==='rozszerzony'?'kif-show-rozszerzony':'kif-show-prosty');
    $root.find('.kif-view-btn').attr('aria-pressed','false').filter('[data-mode="'+mode+'"]').attr('aria-pressed','true');
    try{ localStorage.setItem(STORAGE_KEY, mode); }catch(e){}
  }

  function init($root){
    var defaultMode=$root.data('default-mode')||'prosty', saved=null;
    try{ saved=localStorage.getItem(STORAGE_KEY);}catch(e){}
    var mode=saved||defaultMode||'prosty';
    setMode($root, mode);

    $root.on('click','.kif-view-btn', function(){ setMode($root, $(this).data('mode')); });
    
    $root.on('change input', '.kif-filter-month, .kif-filter-country, .kif-filter-city, .kif-filter-genre, .kif-filter-type, .kif-price-min, .kif-price-max', function(){
      if($(this).hasClass('kif-filter-month') && $(this).val() !== '') {
         $root.find('.kif-quick-btn').removeClass('active');
         $root.find('.kif-quick-btn[data-range="all"]').addClass('active');
      }
      applyFilters($root);
    });
    
    $root.on('input', '.kif-search-input', function(){ applyFilters($root); });

    $root.on('click', '.kif-quick-btn', function() {
      $root.find('.kif-quick-btn').removeClass('active');
      $(this).addClass('active');
      if($(this).data('range') !== 'all') $root.find('.kif-filter-month').val(''); 
      applyFilters($root);
    });

    $root.on('click', '.kif-btn-clear', function() {
        $root.find('.kif-search-input').val('');
        $root.find('.kif-filter-country, .kif-filter-month, .kif-filter-city, .kif-filter-genre, .kif-filter-type').val('');
        $root.find('.kif-price-min, .kif-price-max').val('');
        $root.find('.kif-quick-btn').removeClass('active');
        $root.find('.kif-quick-btn[data-range="all"]').addClass('active');
        applyFilters($root);
    });

    $root.on('click','.kif-month-toggle', function(){
      var $btn=$(this), $list=$btn.closest('.kif-month').find('>.kif-list');
      var open=$btn.attr('aria-expanded')==='true';
      $btn.attr('aria-expanded', open?'false':'true').text(open?'►':'▼');
      $list.slideToggle(160);
    });

    applyFilters($root);
    updateCountdowns(); 
  }

  $(function(){ $('.kif-cal').each(function(){ init($(this)); }); });

})(jQuery);
