/* global f360i18n */
(function () {

  // Fallback strings if wp_localize_script didn't run
  var i18n = (typeof f360i18n !== 'undefined') ? f360i18n : {
    attributes : 'Attributes',
    attribute  : 'Attribute',
    mobile     : 'Mobile',
    desktop    : 'Desktop',
    biznis     : 'Business',
    privat     : 'Private',
    yes        : 'YES',
    no         : 'NO',
    close      : 'Close',
    website    : 'Website',
    linkedin   : 'LinkedIn',
    contact    : 'Contact',
  };


  var researchLabels = {
    hr: {yes:'Dostupno',no:'Nije dostupno',partial:'Djelomično',unknown:'Nije potvrđeno',not_applicable:'Nije primjenjivo',legend:'Legenda',attribute:'Funkcionalnost',mobile:'Građani App',desktop:'Građani Web',biznis:'Tvrtke App',privat:'Tvrtke Web',partialShort:'DJELOM.'},
    sr: {yes:'Dostupno',no:'Nije dostupno',partial:'Delimično',unknown:'Nije potvrđeno',not_applicable:'Nije primenljivo',legend:'Legenda',attribute:'Funkcionalnost',mobile:'Građani App',desktop:'Građani Web',biznis:'Privreda App',privat:'Privreda Web',partialShort:'DELIM.'},
    me: {yes:'Dostupno',no:'Nije dostupno',partial:'Djelimično',unknown:'Nije potvrđeno',not_applicable:'Nije primjenjivo',legend:'Legenda',attribute:'Funkcionalnost',mobile:'Građani App',desktop:'Građani Web',biznis:'Privreda App',privat:'Privreda Web',partialShort:'DJELIM.'},
    sl: {yes:'Na voljo',no:'Ni na voljo',partial:'Delno',unknown:'Ni potrjeno',not_applicable:'Ni relevantno',legend:'Legenda',attribute:'Funkcionalnost',mobile:'Osebno App',desktop:'Osebno Web',biznis:'Podjetja App',privat:'Podjetja Web',partialShort:'DELNO'}
  };
  function researchState(value) {
    return ['yes','no','partial','unknown','not_applicable'].indexOf(value) >= 0 ? value : 'unknown';
  }
  function escapeResearch(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});
  }
  function researchMark(state,labels) {
    return {yes:'DA',no:'NE',partial:labels.partialShort,unknown:'?',not_applicable:'—'}[state];
  }
  function researchCell(row,channel,labels) {
    var state = researchState(row[channel]);
    return '<td data-label="'+escapeResearch(labels[channel])+'" class="f360-state-'+state+'" title="'+escapeResearch(labels[state])+'" aria-label="'+escapeResearch(labels[state])+'">'+researchMark(state,labels)+'</td>';
  }
  function researchTable(attrs,labels) {
    var html = '<div class="f360-research-legend" aria-label="'+escapeResearch(labels.legend)+'">';
    ['yes','partial','no','unknown','not_applicable'].forEach(function(state){html += '<span class="f360-state-'+state+'"><b>'+researchMark(state,labels)+'</b> '+escapeResearch(labels[state])+'</span>';});
    html += '</div><table class="f360-modal__attrs-table"><thead><tr><th scope="col">'+escapeResearch(labels.attribute)+'</th>';
    ['mobile','desktop','biznis','privat'].forEach(function(channel){html += '<th scope="col">'+escapeResearch(labels[channel])+'</th>';});
    html += '</tr></thead><tbody>';
    attrs.forEach(function(row){
      html += '<tr><th scope="row">'+escapeResearch(row.title)+'</th>';
      ['mobile','desktop','biznis','privat'].forEach(function(channel){html += researchCell(row,channel,labels);});
      html += '</tr>';
    });
    return html+'</tbody></table>';
  }
  var researchOpener = null;

  function getModal() {
    return document.getElementById('f360-modal');
  }

  function buildAdvisoryDrawer(drawer, d, closeBtn) {
    var html = '';
    html += '<div class="f360-modal__hero">';
    if (d.thumb) {
      html += '<div class="f360-modal__thumb"><img src="' + d.thumb + '" alt="' + (d.title || '') + '" loading="lazy"></div>';
    }
    html += '<div class="f360-modal__header">';
    html += '<h2 class="f360-modal__title">' + (d.title || '') + '</h2>';
    if (d.jobTitle) html += '<p class="f360-modal__role">'    + d.jobTitle + '</p>';
    if (d.company)  html += '<p class="f360-modal__company">' + d.company  + '</p>';
    var linksHtml = '';
    if (d.website)  linksHtml += '<a href="' + d.website  + '" target="_blank" rel="noopener noreferrer" class="f360-modal__link">🌐 ' + i18n.website  + '</a>';
    if (d.linkedin) linksHtml += '<a href="' + d.linkedin + '" target="_blank" rel="noopener noreferrer" class="f360-modal__link">in ' + i18n.linkedin + '</a>';
    if (linksHtml)  html += '<div class="f360-modal__links">' + linksHtml + '</div>';
    html += '</div></div>';
    html += '<div class="f360-modal__content">';
    if (d.text)    html += '<div class="f360-modal__text">' + d.text.replace(/\n/g, '<br>') + '</div>';
    if (d.contact) html += '<div class="f360-modal__contact"><div class="f360-modal__contact-label">' + i18n.contact + '</div><div class="f360-modal__contact-text">' + d.contact + '</div></div>';
    html += '</div>';
    drawer.innerHTML = '';
    drawer.appendChild(closeBtn);
    drawer.insertAdjacentHTML('beforeend', html);
  }

  function buildDefaultDrawer(drawer, d, closeBtn) {
    var html = '';
    if (d.thumb) {
      html += '<div class="f360-modal__thumb"><img src="' + d.thumb + '" alt="' + (d.title || '') + '" loading="lazy"></div>';
    }
    html += '<div class="f360-modal__content">';
    html += '<div class="f360-modal__header"><h2 class="f360-modal__title">' + (d.title || '') + '</h2>';
    if (d.badgeText) html += '<div class="finelevate360-item-badge ' + (d.badgeClass || '') + '">' + d.badgeText + '</div>';
    html += '</div>';

    if (d.text)    html += '<div class="f360-modal__text">' + d.text.replace(/\n/g, '<br>') + '</div>';
    if (d.contact) html += '<div class="f360-modal__contact"><div class="f360-modal__contact-label">' + i18n.contact + '</div><div class="f360-modal__contact-text">' + d.contact + '</div></div>';

    var linksHtml = '';
    if (d.website)  linksHtml += '<a href="' + d.website  + '" target="_blank" rel="noopener noreferrer" class="f360-modal__link">🌐 ' + i18n.website  + '</a>';
    if (d.linkedin) linksHtml += '<a href="' + d.linkedin + '" target="_blank" rel="noopener noreferrer" class="f360-modal__link">in ' + i18n.linkedin + '</a>';
    if (linksHtml)  html += '<div class="f360-modal__links">' + linksHtml + '</div>';

    var labels = researchLabels[d.researchMarket];
    // Attributes table
    if (d.attrs) {
      try {
        var attrs = JSON.parse(d.attrs);
        if (Array.isArray(attrs) && attrs.length) {
          if (labels) {
            html += researchTable(attrs,labels);
          } else {
          html += '<table class="f360-modal__attrs-table">';
          html += '<thead><tr>';
          html += '<th>' + i18n.attribute + '</th>';
          html += '<th>' + i18n.mobile  + '</th>';
          html += '<th>' + i18n.desktop + '</th>';
          html += '<th>' + i18n.biznis  + '</th>';
          html += '<th>' + i18n.privat  + '</th>';
          html += '</tr></thead><tbody>';

          attrs.forEach(function (row) {
            var mob  = (row.mobile  === 'yes');
            var desk = (row.desktop === 'yes');
            var biz  = (row.biznis  === 'yes');
            var priv = (row.privat  === 'yes');
            html += '<tr>';
            html += '<td>' + row.title + '</td>';
            html += '<td data-label="' + i18n.mobile  + '" class="' + (mob  ? 'f360-attr-yes' : 'f360-attr-no') + '">' + (mob  ? i18n.yes : i18n.no) + '</td>';
            html += '<td data-label="' + i18n.desktop + '" class="' + (desk ? 'f360-attr-yes' : 'f360-attr-no') + '">' + (desk ? i18n.yes : i18n.no) + '</td>';
            html += '<td data-label="' + i18n.biznis  + '" class="' + (biz  ? 'f360-attr-yes' : 'f360-attr-no') + '">' + (biz  ? i18n.yes : i18n.no) + '</td>';
            html += '<td data-label="' + i18n.privat  + '" class="' + (priv ? 'f360-attr-yes' : 'f360-attr-no') + '">' + (priv ? i18n.yes : i18n.no) + '</td>';
            html += '</tr>';
          });

          html += '</tbody></table>';
          }
        }
      } catch (e) {}
    }

    html += '</div>';
    drawer.innerHTML = '';
    drawer.appendChild(closeBtn);
    drawer.insertAdjacentHTML('beforeend', html);
  }

  function openModal(card) {
    var modal = getModal();
    if (!modal) { console.warn('f360-modal: #f360-modal not found'); return; }
    var drawer = modal.querySelector('.f360-modal__drawer');
    if (!drawer) { console.warn('f360-modal: .f360-modal__drawer not found'); return; }

    var d = card.dataset;
    researchOpener = researchLabels[d.researchMarket] ? card : null;
    if (researchOpener) modal.setAttribute('aria-label', d.title || '');
    modal.className = 'f360-modal';
    if (d.modalClass) modal.classList.add(d.modalClass);
    if (researchOpener) { modal.classList.add('f360-modal--research'); drawer.scrollTop = 0; }

    var closeBtn = document.createElement('button');
    closeBtn.className = 'f360-modal__close';
    closeBtn.setAttribute('aria-label', i18n.close);
    closeBtn.innerHTML = '&#x2715;';
    if (researchOpener) closeBtn.type = 'button';
    closeBtn.addEventListener('click', function (event) { if (researchOpener) event.stopPropagation(); closeModal(modal); });

    if (d.modalClass === 'f360-modal--advisory') {
      buildAdvisoryDrawer(drawer, d, closeBtn);
    } else {
      buildDefaultDrawer(drawer, d, closeBtn);
    }

    modal.classList.add('is-open');
    document.body.classList.add('f360-modal-open');
    setTimeout(function () { if (researchLabels[d.researchMarket]) { if (modal.classList.contains('is-open') && modal.contains(closeBtn)) closeBtn.focus({preventScroll:true}); } else { closeBtn.focus(); } }, 400);
  }

  function closeModal(m) {
    if (!m) return;
    m.classList.remove('is-open');
    document.body.classList.remove('f360-modal-open');
    m.className = 'f360-modal';
    if (researchOpener) { researchOpener.focus({preventScroll:true}); researchOpener = null; m.removeAttribute('aria-label'); }
  }

  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-f360-open]');
    if (card) { e.preventDefault(); openModal(card); return; }
    var modal = getModal();
    if (modal && modal.classList.contains('is-open') && e.target === modal) {
      closeModal(modal);
    }
  });

  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-research-market][data-f360-open]')) { e.preventDefault(); openModal(e.target); }
    if (e.key === 'Escape') closeModal(getModal());
    if (e.key === 'Tab' && researchOpener) {
      var m = getModal();
      var focusable = Array.prototype.slice.call(m.querySelectorAll('button, a[href], summary, [tabindex="0"]')).filter(function(el){ return el.getClientRects().length; });
      var first = focusable[0], last = focusable[focusable.length-1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  window.f360OpenModal  = openModal;
  window.f360CloseModal = closeModal;
  window.f360BgClose    = function (e, m) { if (e.target === m) closeModal(m); };

})();
