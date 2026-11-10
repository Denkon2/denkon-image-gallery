(function (blocks, element, blockEditor, data) {
  'use strict';
  var e = element.createElement, useSelect = data.useSelect, useBlockProps = blockEditor.useBlockProps;
  var layoutConfig = window.denkonImageGalleryLayouts || {};
  var features = window.denkonImageGalleryFeatures || {};
  var ru = document.documentElement.lang.indexOf('ru') === 0;
  var words = ru ? {
    gallery:'Галерея', select:'Выберите галерею', layout:'Переопределить макет', defaults:'Использовать настройки галереи', grid:'Сетка', columns:'Переопределить число колонок', columnsHelp:'0 использует настройки галереи.', ratio:'Переопределить пропорции', gap:'Переопределить расстояние', gapHelp:'0 использует настройки галереи.', perPage:'Изображений на странице', imageSize:'Размер изображения', allImages:'Все изображения', lightbox:'Lightbox', captions:'Подписи', ajaxPagination:'AJAX-пагинация', loadMore:'Кнопка «Загрузить ещё»', enabled:'Включено', disabled:'Выключено', applied:'Используются настройки галереи, пока вы не зададите переопределение выше.', prompt:'Выберите галерею.', pro:'PRO'
  } : {
    gallery:'Gallery', select:'Select a gallery', layout:'Layout override', defaults:'Use gallery default', grid:'Grid', columns:'Columns override', columnsHelp:'0 uses the gallery default.', ratio:'Image ratio override', gap:'Gap override', gapHelp:'0 uses the gallery default.', perPage:'Images per page', imageSize:'Image size', allImages:'All images', lightbox:'Lightbox', captions:'Captions', ajaxPagination:'AJAX pagination', loadMore:'“Load more” button', enabled:'Enabled', disabled:'Disabled', applied:'Gallery defaults apply unless you set an override above.', prompt:'Select a gallery.', pro:'PRO'
  };

  function DenkonSelect(props) {
    return e('label', {className:'dkg-field'}, e('span', {className:'dkg-field__label'}, props.label), e('select', {className:'dkg-field__select', value:props.value, onChange:function(event){props.onChange(event.target.value);}}, props.options.map(function(option){return e('option', {key:String(option.value), value:option.value}, option.label);}))); 
  }

  function DenkonRange(props) {
    return e('label', {className:'dkg-field dkg-field--range'}, e('span', {className:'dkg-field__label'}, props.label), e('span', {className:'dkg-range'}, e('input', {type:'range', min:props.min, max:props.max, value:props.value, onChange:function(event){props.onChange(Number(event.target.value));}}), e('output', null, props.value)), props.help ? e('span', {className:'dkg-field__help'}, props.help) : null);
  }

  function LayoutCard(props) {
    return e('button', {type:'button', className:'dkg-layout-card' + (props.selected ? ' is-selected' : ''), onClick:function(){props.onSelect(props.value);}}, e('span', {className:'dkg-layout-card__icon dkg-layout-card__icon--' + props.value}), e('strong', null, props.label));
  }

  function DenkonLayoutPicker(props) {
    var layouts = [{value:'imagebrowser', label:'ImageBrowser'}, {value:'grid', label:words.grid}, {value:'masonry', label:'Masonry'}];
    var proLayouts = [{value:'mosaic', label:'Mosaic'}, {value:'slideshow', label:'Slideshow'}, {value:'tile', label:'Tile'}];
    return e('div', {className:'dkg-layout-field'}, e('span', {className:'dkg-field__label'}, props.label), e('div', {className:'dkg-layout-picker'}, layouts.map(function(layout){return e(LayoutCard, {key:layout.value, value:layout.value, label:layout.label, selected:props.value === layout.value, onSelect:props.onChange});}), proLayouts.map(function(layout){return layoutConfig[layout.value] && layoutConfig[layout.value].available ? e(LayoutCard, {key:layout.value, value:layout.value, label:layout.label, selected:props.value === layout.value, onSelect:props.onChange}) : e('span', {key:layout.value, className:'dkg-layout-card is-pro', title:'Denkon Image Gallery Pro'}, e('span', {className:'dkg-layout-card__badge'}, words.pro), e('span', {className:'dkg-layout-card__icon dkg-layout-card__icon--' + layout.value}), e('strong', null, layout.label));})), e('button', {type:'button', className:'dkg-layout-reset', disabled:!props.value, onClick:function(){props.onChange('');}}, words.defaults));
  }

  blocks.registerBlockType('denkon/image-gallery', {
    edit: function (props) {
      var galleries = useSelect(function (select) { return select('core').getEntityRecords('postType', 'denkon_gallery', {per_page: -1, status: 'publish,draft'}); }, []);
      var choices = [{label: words.select, value: 0}].concat((galleries || []).map(function (item) { return {label: item.title.rendered || '(untitled)', value: item.id}; }));
      var blockProps = useBlockProps({className: 'dkg-block-editor'});
      var isSlideshow = props.attributes.layout === 'slideshow';
      var isCarousel = props.attributes.layout === 'imagebrowser' || isSlideshow;
      return e('div', blockProps,
        e('p', {className:'dkg-block-editor__lead'}, 'Denkon Image Gallery'),
        e(DenkonSelect, {label:words.gallery, value:props.attributes.id, options:choices, onChange:function(v){props.setAttributes({id:Number(v)});}}),
        e(DenkonLayoutPicker, {label:words.layout, value:props.attributes.layout, onChange:function(v){props.setAttributes({layout:v});}}),
        !isSlideshow && e(DenkonRange,{label:words.columns,help:words.columnsHelp,value:Number(props.attributes.columns || 0),min:0,max:12,onChange:function(v){props.setAttributes({columns:String(v)});}}),
        e(DenkonSelect,{label:words.ratio,value:props.attributes.ratio,options:[{label:words.defaults,value:''},{label:'1:1',value:'1 / 1'},{label:'4:3',value:'4 / 3'},{label:'3:2',value:'3 / 2'},{label:'16:9',value:'16 / 9'}],onChange:function(v){props.setAttributes({ratio:v});}}),
        !isSlideshow && e(DenkonRange,{label:words.gap,help:words.gapHelp,value:Number(props.attributes.gap || 0),min:0,max:48,onChange:function(v){props.setAttributes({gap:String(v)});}}),
        !isCarousel && e(DenkonSelect,{label:words.perPage,value:props.attributes.per_page,options:[{label:words.defaults,value:''},{label:words.allImages,value:'0'},{label:'6',value:'6'},{label:'9',value:'9'},{label:'12',value:'12'},{label:'18',value:'18'},{label:'24',value:'24'},{label:'36',value:'36'}],onChange:function(v){props.setAttributes({per_page:v});}}),
        features.ajaxPagination && !isCarousel && e(DenkonSelect,{label:words.ajaxPagination,value:props.attributes.ajax_pagination,options:[{label:words.defaults,value:''},{label:words.enabled,value:'true'},{label:words.disabled,value:'false'}],onChange:function(v){props.setAttributes({ajax_pagination:v});}}),
        features.ajaxPagination && !isCarousel && e(DenkonSelect,{label:words.loadMore,value:props.attributes.load_more,options:[{label:words.defaults,value:''},{label:words.enabled,value:'true'},{label:words.disabled,value:'false'}],onChange:function(v){props.setAttributes({load_more:v});}}),
        e(DenkonSelect,{label:words.imageSize,value:props.attributes.size,options:[{label:words.defaults,value:''},{label:'Thumbnail',value:'thumbnail'},{label:'Medium',value:'medium'},{label:'Medium large',value:'medium_large'},{label:'Large',value:'large'},{label:'Full',value:'full'}],onChange:function(v){props.setAttributes({size:v});}}),
        e(DenkonSelect,{label:words.lightbox,value:props.attributes.lightbox,options:[{label:words.defaults,value:''},{label:words.enabled,value:'true'},{label:words.disabled,value:'false'}],onChange:function(v){props.setAttributes({lightbox:v});}}),
        e(DenkonSelect,{label:words.captions,value:props.attributes.captions,options:[{label:words.defaults,value:''},{label:words.enabled,value:'true'},{label:words.disabled,value:'false'}],onChange:function(v){props.setAttributes({captions:v});}}),
        props.attributes.id ? e('p', null, words.applied) : e('p', null, words.prompt)
      );
    },
    save: function () { return null; }
  });
}(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.data));
