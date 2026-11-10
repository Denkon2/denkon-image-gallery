(function () {
  'use strict';
  var active, opener;
  function makeLightbox() {
    var root = document.createElement('div'); root.className = 'dkg-lightbox'; root.setAttribute('role', 'dialog'); root.setAttribute('aria-modal', 'true'); root.setAttribute('aria-hidden', 'true'); root.setAttribute('aria-label', 'Просмотр изображения');
    root.innerHTML = '<button class="dkg-lightbox__button dkg-lightbox__close" type="button" aria-label="Закрыть">×</button><button class="dkg-lightbox__button dkg-lightbox__previous" type="button" aria-label="Предыдущее">‹</button><img class="dkg-lightbox__image" alt=""><button class="dkg-lightbox__button dkg-lightbox__next" type="button" aria-label="Следующее">›</button><p class="dkg-lightbox__caption"></p>';
    document.body.appendChild(root); return root;
  }
  function close() {
    var box = active;
    if (!box) return;
    active = null;
    box.classList.remove('is-open'); box.setAttribute('aria-hidden', 'true'); document.body.style.removeProperty('overflow');
    window.setTimeout(function () { if (box.parentNode) box.parentNode.removeChild(box); }, 260);
    if (opener) opener.focus();
  }
  function open(link) {
    var gallery = link.closest('[data-dkg-gallery]'), links = Array.prototype.slice.call(gallery.querySelectorAll('[data-dkg-lightbox]')), index = links.indexOf(link), box = active || makeLightbox();
    function show(position, direction) {
      var nextIndex = (position + links.length) % links.length, current = links[nextIndex], image = box.querySelector('.dkg-lightbox__image');
      function replace() { index = nextIndex; image.src = current.href; image.alt = current.querySelector('img').alt || ''; box.querySelector('.dkg-lightbox__caption').textContent = current.dataset.dkgCaption || ''; box.classList.remove('is-changing'); }
      if (typeof direction === 'undefined') { replace(); return; }
      box.style.setProperty('--dkg-slide-offset', direction > 0 ? '-18px' : '18px'); box.classList.add('is-changing'); window.setTimeout(replace, 180);
    }
    box.querySelector('.dkg-lightbox__close').onclick = close; box.querySelector('.dkg-lightbox__previous').onclick = function(){show(index - 1, -1);}; box.querySelector('.dkg-lightbox__next').onclick = function(){show(index + 1, 1);}; box.onclick = function(e){if(e.target === box) close();};
    active = box; opener = link; show(index); box.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; window.requestAnimationFrame(function(){ box.classList.add('is-open'); }); box.querySelector('.dkg-lightbox__close').focus();
  }
  function thumbnailWindow(browser, requestedStart) {
    var thumbs = Array.prototype.slice.call(browser.querySelectorAll('[data-dkg-browser-thumb]')),
      viewport = browser.querySelector('.dkg-imagebrowser__thumb-viewport'),
      track = browser.querySelector('.dkg-imagebrowser__thumbs'),
      visible = Math.max(1, Math.min(thumbs.length, Number(browser.dataset.dkgThumbCount) || 1)),
      gap = parseFloat(window.getComputedStyle(track).gap) || 0,
      maxStart = Math.max(0, thumbs.length - visible),
      start = Math.max(0, Math.min(maxStart, requestedStart));
    if (!viewport || !track || !thumbs.length) return;
    var width = Math.max(1, (viewport.clientWidth - gap * (visible - 1)) / visible);
    thumbs.forEach(function (thumb) { thumb.style.flexBasis = width + 'px'; });
    track.style.transform = 'translateX(-' + (start * (width + gap)) + 'px)';
    browser.dataset.dkgThumbStart = String(start);
    var previous = browser.querySelector('[data-dkg-thumb-previous]'), next = browser.querySelector('[data-dkg-thumb-next]');
    if (previous) previous.disabled = start === 0;
    if (next) next.disabled = start === maxStart;
  }
  function setBrowserItem(browser, index) {
    var thumbs = Array.prototype.slice.call(browser.querySelectorAll('[data-dkg-browser-thumb]'));
    if (!thumbs.length) return;
    index = (index + thumbs.length) % thumbs.length;
    var thumb = thumbs[index], stages = Array.prototype.slice.call(browser.querySelectorAll('[data-dkg-browser-stage]')), caption = browser.querySelector('[data-dkg-browser-caption]');
    /* Do not swap image URLs here: optimisation plugins often wrap them in <picture>.
       The complete, server-rendered slide is simply made active instead. */
    stages.forEach(function (stage, stageIndex) { stage.classList.toggle('is-active', stageIndex === index); stage.setAttribute('aria-hidden', stageIndex === index ? 'false' : 'true'); });
    if (caption) caption.textContent = thumb.dataset.dkgCaption || '';
    thumbs.forEach(function (item, itemIndex) { item.classList.toggle('is-active', itemIndex === index); item.setAttribute('aria-current', itemIndex === index ? 'true' : 'false'); });
    var visible = Math.max(1, Math.min(thumbs.length, Number(browser.dataset.dkgThumbCount) || 1)), start = Number(browser.dataset.dkgThumbStart) || 0;
    if (index < start) start = index;
    if (index >= start + visible) start = index - visible + 1;
    thumbnailWindow(browser, start);
  }
  document.addEventListener('click', function (event) {
    var thumb = event.target.closest('[data-dkg-browser-thumb]');
    if (thumb) { event.preventDefault(); var browser = thumb.closest('[data-dkg-imagebrowser]'); setBrowserItem(browser, Array.prototype.slice.call(browser.querySelectorAll('[data-dkg-browser-thumb]')).indexOf(thumb)); return; }
    var browserButton = event.target.closest('[data-dkg-browser-previous], [data-dkg-browser-next]');
    if (browserButton) { var browserRoot = browserButton.closest('[data-dkg-imagebrowser]'), activeThumb = browserRoot.querySelector('.dkg-imagebrowser__thumb.is-active'), allThumbs = Array.prototype.slice.call(browserRoot.querySelectorAll('[data-dkg-browser-thumb]')); setBrowserItem(browserRoot, allThumbs.indexOf(activeThumb) + (browserButton.hasAttribute('data-dkg-browser-next') ? 1 : -1)); return; }
    var thumbButton = event.target.closest('[data-dkg-thumb-previous], [data-dkg-thumb-next]');
    if (thumbButton) { var thumbBrowser = thumbButton.closest('[data-dkg-imagebrowser]'), currentStart = Number(thumbBrowser.dataset.dkgThumbStart) || 0; thumbnailWindow(thumbBrowser, currentStart + (thumbButton.hasAttribute('data-dkg-thumb-next') ? 1 : -1)); return; }
    var stage = event.target.closest('[data-dkg-browser-stage]');
    if (stage) { event.preventDefault(); if (stage.getAttribute('aria-disabled') !== 'true') open(stage.closest('[data-dkg-imagebrowser]').querySelector('.dkg-imagebrowser__thumb.is-active')); }
  });
  window.addEventListener('resize', function () { document.querySelectorAll('[data-dkg-imagebrowser]').forEach(function (browser) { thumbnailWindow(browser, Number(browser.dataset.dkgThumbStart) || 0); }); });
  document.querySelectorAll('[data-dkg-imagebrowser]').forEach(function (browser) { thumbnailWindow(browser, 0); });
  document.addEventListener('click', function (event) { if (event.defaultPrevented) return; var link = event.target.closest('[data-dkg-lightbox]'); if (link) { event.preventDefault(); open(link); } });
  function setGalleryUrl(root, page) {
    if (!window.history || !window.history.replaceState) return;
    var query = JSON.parse(root.dataset.dkgQuery || '{}');
    if (!query.id) return;
    var url = new URL(window.location.href), key = 'dkg_page_' + query.id;
    if (page > 1) url.searchParams.set(key, page); else url.searchParams.delete(key);
    window.history.replaceState({}, '', url.toString());
  }
  function replacePagination(root, nextRoot) {
    var current = root.querySelector('.dkg-pagination'), next = nextRoot.querySelector('.dkg-pagination');
    if (current && next) current.parentNode.replaceChild(next, current);
    else if (current) current.parentNode.removeChild(current);
    else if (next) root.appendChild(next);
  }
  function setGalleryLoading(root, loading) {
    var settings = window.denkonImageGallery || {}, status = root.querySelector('.dkg-ajax-status');
    root.classList.toggle('is-loading', loading);
    if (loading) root.setAttribute('aria-busy', 'true'); else root.removeAttribute('aria-busy');
    if (status) status.textContent = loading ? (settings.loading || 'Loading…') : '';
  }
  function requestGalleryPage(root, page, append) {
    if (root.classList.contains('is-loading')) return;
    var settings = window.denkonImageGallery || {}, query;
    try { query = JSON.parse(root.dataset.dkgQuery || '{}'); } catch (error) { return; }
    if (!settings.ajaxUrl || !settings.nonce || !query.id) return;
    setGalleryLoading(root, true);
    var body = new URLSearchParams();
    body.append('action', 'denkon_gallery_page'); body.append('nonce', settings.nonce); body.append('page', page);
    Object.keys(query).forEach(function (key) { body.append('attributes[' + key + ']', query[key]); });
    fetch(settings.ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body:body.toString()})
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (!response || !response.success || !response.data || !response.data.html) throw new Error('gallery-response');
        var template = document.createElement('template'); template.innerHTML = response.data.html.trim();
        var nextRoot = template.content.querySelector('.dkg-gallery-render');
        if (!nextRoot) throw new Error('gallery-markup');
        if (append) {
          var gallery = root.querySelector('[data-dkg-gallery]'), nextGallery = nextRoot.querySelector('[data-dkg-gallery]');
          if (!gallery || !nextGallery) throw new Error('gallery-items');
          Array.prototype.slice.call(nextGallery.children).forEach(function (item) { gallery.appendChild(item); });
          replacePagination(root, nextRoot); root.dataset.dkgPage = String(page); setGalleryUrl(root, page);
          setGalleryLoading(root, false); return;
        }
        root.parentNode.replaceChild(nextRoot, root); setGalleryUrl(nextRoot, page);
      })
      .catch(function () { setGalleryLoading(root, false); window.alert((window.denkonImageGallery || {}).error || 'Could not load images.'); });
  }
  document.addEventListener('click', function (event) {
    var target = event.target.closest('[data-dkg-page], [data-dkg-load-more]');
    if (!target) return;
    var root = target.closest('[data-dkg-ajax-gallery]');
    if (!root) return;
    event.preventDefault();
    requestGalleryPage(root, Number(target.dataset.dkgPage) || 1, target.hasAttribute('data-dkg-load-more'));
  });
  document.addEventListener('keydown', function (event) { if (!active) return; if (event.key === 'Escape') close(); if (event.key === 'ArrowLeft') active.querySelector('.dkg-lightbox__previous').click(); if (event.key === 'ArrowRight') active.querySelector('.dkg-lightbox__next').click(); });
}());
