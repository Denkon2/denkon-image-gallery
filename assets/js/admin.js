(function ($) {
  'use strict';
  function sync($editor) { $editor.find('[data-gallery-input]').val($editor.find('[data-gallery-list] li').map(function () { return $(this).data('image-id'); }).get().join(',')); }
  $(document).on('click', '[data-gallery-select]', function (event) {
    event.preventDefault();
    var $editor = $(this).closest('[data-gallery-editor]');
    var frame = wp.media({ title: denkonGalleryAdmin.title, button: {text: denkonGalleryAdmin.button}, multiple: true, library: {type: 'image'} });
    frame.on('select', function () { frame.state().get('selection').each(function (attachment) {
      var item = attachment.toJSON(), $list = $editor.find('[data-gallery-list]');
      if (!$list.find('[data-image-id="' + item.id + '"]').length) { $list.append('<li data-image-id="' + item.id + '"><img src="' + (item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url) + '" alt=""><button type="button" class="dkg-admin-gallery__remove" aria-label="Удалить">&times;</button></li>'); }
    }); sync($editor); });
    frame.open();
  });
  $(document).on('click', '.dkg-admin-gallery__remove', function () { var $editor = $(this).closest('[data-gallery-editor]'); $(this).closest('li').remove(); sync($editor); });
  function createEditorShell() {
    var content = document.getElementById('post-body-content');
    var wrap = document.querySelector('.wrap');
    var heading = wrap ? wrap.querySelector('h1.wp-heading-inline') : null;
    if (!content || !heading || document.querySelector('.dkg-editor-shell')) { return; }

    var shell = document.createElement('section');
    var header = document.createElement('div');
    var action = heading.nextElementSibling && heading.nextElementSibling.classList.contains('page-title-action') ? heading.nextElementSibling : null;
    var headerEnd = document.querySelector('.wp-header-end');
    shell.className = 'dkg-editor-shell';
    header.className = 'dkg-editor-shell__header';
    content.insertBefore(shell, content.firstChild);
    shell.appendChild(header);
    header.appendChild(heading);
    if (action) { header.appendChild(action); }
    if (headerEnd) { headerEnd.remove(); }
    Array.prototype.slice.call(content.childNodes).forEach(function (node) {
      if (node !== shell) { shell.appendChild(node); }
    });
  }
  $(document).on('change', '.dkg-layout-card input', function () {
    $(this).closest('.dkg-layout-picker').find('.dkg-layout-card').removeClass('is-selected');
    $(this).closest('.dkg-layout-card').addClass('is-selected');
    var carousel = this.value === 'imagebrowser' || this.value === 'slideshow';
    $('.dkg-per-page-control, .dkg-pagination-controls').prop('hidden', carousel);
    $('.dkg-columns-control, .dkg-gap-control').prop('hidden', this.value === 'slideshow');
  });
  $(function () {
    createEditorShell();
    var layout = $('.dkg-layout-card input:checked').val();
    var carousel = layout === 'imagebrowser' || layout === 'slideshow';
    $('.dkg-per-page-control, .dkg-pagination-controls').prop('hidden', carousel);
    $('.dkg-columns-control, .dkg-gap-control').prop('hidden', layout === 'slideshow');
    $('[data-gallery-list]').sortable({update: function () { sync($(this).closest('[data-gallery-editor]')); }});
  });
}(jQuery));
