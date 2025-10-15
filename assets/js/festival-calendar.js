(function($){

  // -----------------------------
  // Aktualizacja etykiety suwaka
  // -----------------------------
  function updatePriceLabel($root){
    var max=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10);
    $root.find('.kif-price-label').text('Pokaż do '+max+' PLN');
  }

  // -----------------------------
  // Filtrowanie wydarzeń (wszystkich, w tym POLECAMY)
  // -----------------------------
  function applyFilters($root){
    var m=$root.find('.kif-filter-month').val()||'',
        c=$root.find('.kif-filter-city').val()||'',
        g=$root.find('.kif-filter-genre').val()||'',
        t=$root.find('.kif-filter-type').val()||'',
        pmax=parseInt($root.find('.kif-range-max').val()||$root.data('price-max')||'0',10),
        q=($root.find('.kif-search-input').val()||'').toLowerCase().trim();

    // Warunki dopasowania pojedynczej karty
    function match($card){
      var cm=$card.data('month')||'',
          cc=$card.data('city')||'',
          cg=($card.data('genre')||'').toString(),
@@ -49,62 +48,50 @@
          ($card.data('venue')||'')+' '+
          ($card.data('genre')||'')
        ).toLowerCase();
        if(txt.indexOf(q)===-1) return false;
      }

      return true;
    }

    // 🔁 Jedna pętla – filtruje wszystkie karty, także polecane
    var $allCards = $root.find('.kif-event-card');
    $allCards.each(function(){
      var ok = match($(this));
      $(this).toggleClass('kif-hidden', !ok);
    });

    // 👀 Ukryj sekcję POLECAMY, jeśli żadna karta nie pasuje
    var anyFeatVisible = $root
      .find('.kif-featured .kif-event-card')
      .filter(':not(.kif-hidden)')
      .length > 0;

    $root.find('.kif-featured').toggleClass('kif-hidden', !anyFeatVisible);
  }

  // -----------------------------
  // Otwieranie szczegółów wydarzenia
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

      var date = fmt(ev.date);
      var where = [ev.location, ev.city].filter(Boolean).join(', ');
      var priceText = ev.price ? (ev.price.toString().trim()+' PLN') : '';

      function esc(s){
        if (!s) return '';
        var txt = document.createElement('textarea');
        txt.innerHTML = s;
        var decoded = txt.value;
        return $('<div>').text(decoded).html();
@@ -186,56 +173,50 @@
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

    $root.on('change input', '.kif-filter-month,.kif-filter-city,.kif-filter-genre,.kif-filter-type,.kif-range-max', function(){
      updatePriceLabel($root);
      applyFilters($root);
    });

    // Wyszukiwanie tekstowe
    $root.on('input', '.kif-search-input', function(){
      applyFilters($root);
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
