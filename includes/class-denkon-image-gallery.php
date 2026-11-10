<?php
/**
 * Main plugin class.
 *
 * @package DenkonImageGallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Denkon_Image_Gallery {
	const POST_TYPE = 'denkon_gallery';
	const META_IMAGES = '_denkon_gallery_images';
	const META_SETTINGS = '_denkon_gallery_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_gallery_type' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_gallery' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'gallery_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_gallery_column' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'render_gallery_library_intro' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'wp_ajax_denkon_gallery_page', array( $this, 'ajax_gallery_page' ) );
		add_action( 'wp_ajax_nopriv_denkon_gallery_page', array( $this, 'ajax_gallery_page' ) );
		add_shortcode( 'denkon_gallery', array( $this, 'render_shortcode' ) );
	}

	public function register_gallery_type() {
		$labels = array(
			'name'               => $this->t( 'Galleries', 'Галереи' ),
			'singular_name'      => $this->t( 'Gallery', 'Галерея' ),
			'add_new'            => $this->t( 'Add New', 'Добавить' ),
			'add_new_item'       => $this->t( 'Create gallery', 'Создать галерею' ),
			'edit_item'          => $this->t( 'Edit gallery', 'Редактировать галерею' ),
			'new_item'           => $this->t( 'New gallery', 'Новая галерея' ),
			'view_item'          => $this->t( 'View gallery', 'Просмотреть галерею' ),
			'search_items'       => $this->t( 'Search galleries', 'Найти галереи' ),
			'not_found'          => $this->t( 'No galleries found.', 'Галереи не найдены.' ),
			'menu_name'          => $this->t( 'Galleries', 'Галереи' ),
		);

		register_post_type( self::POST_TYPE, array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
		) );
	}

	public function register_block() {
		$block_type = register_block_type( DENKON_IMAGE_GALLERY_DIR . 'blocks/gallery' );
		if ( $block_type && ! empty( $block_type->editor_script_handles ) ) {
			$layouts = wp_json_encode( $this->get_layouts() );
			$features = wp_json_encode( array( 'ajaxPagination' => defined( 'DENKON_IMAGE_GALLERY_PRO_VERSION' ) ) );
			foreach ( $block_type->editor_script_handles as $handle ) {
				wp_add_inline_script( $handle, 'window.denkonImageGalleryLayouts = ' . $layouts . ';', 'before' );
				wp_add_inline_script( $handle, 'window.denkonImageGalleryFeatures = ' . $features . ';', 'before' );
			}
		}
	}

	public function add_meta_box() {
		add_meta_box( 'denkon-gallery-studio', $this->t( 'Denkon Gallery Studio', 'Denkon Gallery Studio' ), array( $this, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public function render_meta_box( $post ) {
		$image_ids = $this->get_image_ids( $post->ID );
		$settings  = $this->get_settings( $post->ID );
		$layouts   = $this->get_layouts();
		wp_nonce_field( 'denkon_gallery_save', 'denkon_gallery_nonce' );
		?>
		<div class="dkg-studio" data-gallery-editor>
			<div class="dkg-studio__hero"><div><span class="dkg-studio__eyebrow">DENKON IMAGE GALLERY</span><h2><?php echo esc_html( $this->t( 'Build a gallery worth showing', 'Создайте галерею, которую хочется показать' ) ); ?></h2><p><?php echo esc_html( $this->t( 'Arrange images and define the default display. Block settings can override these values per page.', 'Расположите изображения и задайте настройки по умолчанию. Блок Gutenberg сможет переопределить их для конкретной страницы.' ) ); ?></p></div><span class="dashicons dashicons-format-gallery"></span></div>
			<div class="dkg-studio__section">
				<div class="dkg-studio__section-head"><div><h3><?php echo esc_html( $this->t( 'Images', 'Изображения' ) ); ?></h3><p><?php echo esc_html( $this->t( 'Drag to set the visual order.', 'Перетаскивайте, чтобы задать порядок.' ) ); ?></p></div><button type="button" class="button button-primary" data-gallery-select><?php echo esc_html( $this->t( 'Add images', 'Добавить изображения' ) ); ?></button></div>
			<input type="hidden" name="denkon_gallery_images" value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>" data-gallery-input>
			<ul class="dkg-admin-gallery__list" data-gallery-list>
				<?php foreach ( $image_ids as $image_id ) : ?>
					<?php $thumbnail = wp_get_attachment_image_url( $image_id, 'thumbnail' ); ?>
					<?php if ( $thumbnail ) : ?><li data-image-id="<?php echo esc_attr( $image_id ); ?>"><img src="<?php echo esc_url( $thumbnail ); ?>" alt=""><button type="button" class="dkg-admin-gallery__remove" aria-label="<?php esc_attr_e( 'Удалить', 'denkon-image-gallery' ); ?>">&times;</button></li><?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
			<div class="dkg-studio__section dkg-studio__settings"><div class="dkg-studio__section-head"><div><h3><?php echo esc_html( $this->t( 'Gallery defaults', 'Настройки галереи' ) ); ?></h3><p><?php echo esc_html( $this->t( 'Used by shortcode and as the starting point for the Gutenberg block.', 'Используются в шорткоде и как исходные значения для блока Gutenberg.' ) ); ?></p></div></div>
				<div class="dkg-settings-grid">
					<div class="dkg-layout-control"><span><?php echo esc_html( $this->t( 'Choose layout', 'Выберите макет' ) ); ?></span><div class="dkg-layout-picker">
						<?php foreach ( $layouts as $layout_key => $layout_config ) : ?>
							<?php if ( ! empty( $layout_config['available'] ) ) : ?>
								<label class="dkg-layout-card<?php echo $settings['layout'] === $layout_key ? ' is-selected' : ''; ?>"><input type="radio" name="denkon_gallery_layout" value="<?php echo esc_attr( $layout_key ); ?>" <?php checked( $settings['layout'], $layout_key ); ?>><span class="dkg-layout-card__icon dkg-layout-card__icon--<?php echo esc_attr( $layout_key ); ?>"></span><strong><?php echo esc_html( $layout_config['label'] ); ?></strong></label>
							<?php else : ?>
								<span class="dkg-layout-card is-pro" title="<?php echo esc_attr( $this->t( 'Available in Denkon Image Gallery Pro', 'Доступно в Denkon Image Gallery Pro' ) ); ?>"><span class="dkg-layout-card__badge">PRO</span><span class="dkg-layout-card__icon dkg-layout-card__icon--<?php echo esc_attr( $layout_key ); ?>"></span><strong><?php echo esc_html( $layout_config['label'] ); ?></strong></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div></div>
					<label class="dkg-columns-control"><span><?php echo esc_html( $this->t( 'Columns', 'Колонки' ) ); ?></span><select name="denkon_gallery_columns"><?php for ( $column = 1; $column <= 12; $column++ ) : ?><option value="<?php echo esc_attr( $column ); ?>" <?php selected( $settings['columns'], $column ); ?>><?php echo esc_html( $column ); ?></option><?php endfor; ?></select></label>
					<label><span><?php echo esc_html( $this->t( 'Image ratio', 'Пропорции карточки' ) ); ?></span><select name="denkon_gallery_ratio"><?php foreach ( array( '1 / 1', '4 / 3', '3 / 2', '16 / 9' ) as $ratio ) : ?><option value="<?php echo esc_attr( $ratio ); ?>" <?php selected( $settings['ratio'], $ratio ); ?>><?php echo esc_html( str_replace( ' ', '', $ratio ) ); ?></option><?php endforeach; ?></select></label>
					<label class="dkg-gap-control"><span><?php echo esc_html( $this->t( 'Spacing', 'Расстояние между карточками' ) ); ?></span><select name="denkon_gallery_gap"><?php foreach ( array( 0, 8, 12, 16, 24, 32 ) as $gap ) : ?><option value="<?php echo esc_attr( $gap ); ?>" <?php selected( $settings['gap'], $gap ); ?>><?php echo esc_html( $gap . ' px' ); ?></option><?php endforeach; ?></select></label>
					<label class="dkg-per-page-control"><span><?php echo esc_html( $this->t( 'Images per page', 'Изображений на странице' ) ); ?></span><select name="denkon_gallery_per_page"><?php foreach ( array( 0, 6, 9, 12, 18, 24, 36 ) as $per_page ) : ?><option value="<?php echo esc_attr( $per_page ); ?>" <?php selected( $settings['per_page'], $per_page ); ?>><?php echo esc_html( $per_page ? $per_page : $this->t( 'All images', 'Все изображения' ) ); ?></option><?php endforeach; ?></select></label>
					<?php if ( defined( 'DENKON_IMAGE_GALLERY_PRO_VERSION' ) ) : ?>
						<div class="dkg-settings-grid__toggles dkg-settings-grid__toggles--pagination dkg-pagination-controls"><label><input type="checkbox" name="denkon_gallery_ajax_pagination" value="1" <?php checked( $settings['ajax_pagination'] ); ?>><span><?php echo esc_html( $this->t( 'Use AJAX pagination', 'Использовать AJAX-пагинацию' ) ); ?></span></label><label><input type="checkbox" name="denkon_gallery_load_more" value="1" <?php checked( $settings['load_more'] ); ?>><span><?php echo esc_html( $this->t( 'Use “Load more” button', 'Использовать кнопку «Загрузить ещё»' ) ); ?></span></label></div>
					<?php endif; ?>
					<label><span><?php echo esc_html( $this->t( 'WordPress image size', 'Размер изображения WordPress' ) ); ?></span><select name="denkon_gallery_size"><?php foreach ( $this->allowed_image_sizes() as $size ) : ?><option value="<?php echo esc_attr( $size ); ?>" <?php selected( $settings['size'], $size ); ?>><?php echo esc_html( $size ); ?></option><?php endforeach; ?></select></label>
					<div class="dkg-dimensions"><span><?php echo esc_html( $this->t( 'Card dimensions', 'Размеры карточки' ) ); ?></span><div><label><input type="number" name="denkon_gallery_thumbnail_width" min="80" max="2400" value="<?php echo esc_attr( $settings['thumbnail_width'] ); ?>"><em><?php echo esc_html( $this->t( 'width', 'ширина' ) ); ?></em></label><b>×</b><label><input type="number" name="denkon_gallery_thumbnail_height" min="80" max="2400" value="<?php echo esc_attr( $settings['thumbnail_height'] ); ?>"><em><?php echo esc_html( $this->t( 'height', 'высота' ) ); ?></em></label></div></div>
					<div class="dkg-settings-grid__toggles"><label><input type="checkbox" name="denkon_gallery_lightbox" value="1" <?php checked( $settings['lightbox'] ); ?>><span><?php echo esc_html( $this->t( 'Enable lightbox', 'Включить lightbox' ) ); ?></span></label><label><input type="checkbox" name="denkon_gallery_captions" value="1" <?php checked( $settings['captions'] ); ?>><span><?php echo esc_html( $this->t( 'Show captions', 'Показывать подписи' ) ); ?></span></label></div>
					<div class="dkg-settings-grid__toggles"><label><input type="checkbox" name="denkon_gallery_crop" value="1" <?php checked( $settings['crop'] ); ?>><span><?php echo esc_html( $this->t( 'Crop images to card ratio', 'Обрезать изображения по пропорции карточки' ) ); ?></span></label></div>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_gallery( $post_id ) {
		if ( ! isset( $_POST['denkon_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['denkon_gallery_nonce'] ) ), 'denkon_gallery_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw_ids = isset( $_POST['denkon_gallery_images'] ) ? sanitize_text_field( wp_unslash( $_POST['denkon_gallery_images'] ) ) : '';
		$image_ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) ) );
		$image_ids = array_values( array_filter( $image_ids, function( $id ) { return 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ); } ) );
		update_post_meta( $post_id, self::META_IMAGES, $image_ids );
		update_post_meta( $post_id, self::META_SETTINGS, $this->sanitize_settings( $_POST ) );
	}

	public function enqueue_admin_assets( $hook ) {
		global $post_type;
		if ( self::POST_TYPE !== $post_type || ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) { return; }
		wp_enqueue_style( 'denkon-image-gallery-admin', DENKON_IMAGE_GALLERY_URL . 'assets/css/admin.css', array(), DENKON_IMAGE_GALLERY_VERSION );
		if ( 'edit.php' === $hook ) { return; }
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'denkon-image-gallery-admin', DENKON_IMAGE_GALLERY_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), DENKON_IMAGE_GALLERY_VERSION, true );
		wp_localize_script( 'denkon-image-gallery-admin', 'denkonGalleryAdmin', array( 'title' => $this->t( 'Select images', 'Выбор изображений' ), 'button' => $this->t( 'Add to gallery', 'Добавить в галерею' ), 'remove' => $this->t( 'Remove image', 'Удалить изображение' ) ) );
	}

	public function gallery_columns() {
		return array(
			'cb' => '<input type="checkbox">',
			'dkg_preview' => $this->t( 'Preview', 'Превью' ),
			'title' => $this->t( 'Gallery', 'Галерея' ),
			'dkg_images' => $this->t( 'Images', 'Изображения' ),
			'dkg_layout' => $this->t( 'Display', 'Отображение' ),
			'dkg_shortcode' => $this->t( 'Shortcode', 'Шорткод' ),
			'date' => $this->t( 'Date', 'Дата' ),
		);
	}

	public function render_gallery_column( $column, $post_id ) {
		$image_ids = $this->get_image_ids( $post_id );
		$settings = $this->get_settings( $post_id );
		if ( 'dkg_preview' === $column ) {
			if ( empty( $image_ids ) ) { echo '<span class="dkg-list-preview dkg-list-preview--empty"><span class="dashicons dashicons-format-gallery"></span></span>'; return; }
			echo '<span class="dkg-list-preview">' . wp_get_attachment_image( $image_ids[0], 'thumbnail', false, array( 'loading' => 'lazy' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'dkg_images' === $column ) {
			echo '<strong>' . esc_html( count( $image_ids ) ) . '</strong> <span class="dkg-list-muted">' . esc_html( $this->t( 'items', 'изобр.' ) ) . '</span>';
		} elseif ( 'dkg_layout' === $column ) {
			echo '<span class="dkg-list-pill">' . esc_html( ucfirst( $settings['layout'] ) ) . '</span><br><span class="dkg-list-muted">' . esc_html( sprintf( $this->t( '%d columns · %s', '%d колонки · %s' ), $settings['columns'], str_replace( ' ', '', $settings['ratio'] ) ) ) . '</span>';
		} elseif ( 'dkg_shortcode' === $column ) {
			echo '<code class="dkg-list-shortcode">[denkon_gallery id=&quot;' . esc_attr( $post_id ) . '&quot;]</code>';
		}
	}

	public function render_gallery_library_intro() {
		global $pagenow, $post_type;
		if ( 'edit.php' !== $pagenow || self::POST_TYPE !== $post_type ) { return; }
		$count = wp_count_posts( self::POST_TYPE )->publish;
		$url = admin_url( 'post-new.php?post_type=' . self::POST_TYPE );
		?>
		<div class="dkg-library-hero"><div><span class="dkg-library-hero__eyebrow">DENKON IMAGE GALLERY</span><h2><?php echo esc_html( $this->t( 'Gallery library', 'Библиотека галерей' ) ); ?></h2><p><?php echo esc_html( $this->t( 'Create polished visual stories with fast, responsive galleries.', 'Создавайте эффектные визуальные истории с быстрыми адаптивными галереями.' ) ); ?></p></div><div class="dkg-library-hero__actions"><span><strong><?php echo esc_html( $count ); ?></strong> <?php echo esc_html( $this->t( 'published', 'опубликовано' ) ); ?></span><a class="button" href="<?php echo esc_url( $url ); ?>"><span class="dashicons dashicons-plus-alt2"></span><?php echo esc_html( $this->t( 'New gallery', 'Новая галерея' ) ); ?></a></div></div>
		<?php
	}

	public function add_admin_body_class( $classes ) {
		global $post_type;
		return self::POST_TYPE === $post_type ? $classes . ' dkg-admin-screen' : $classes;
	}

	public function register_frontend_assets() {
		wp_register_style( 'denkon-image-gallery', DENKON_IMAGE_GALLERY_URL . 'assets/css/gallery.css', array(), DENKON_IMAGE_GALLERY_VERSION );
		wp_register_script( 'denkon-image-gallery', DENKON_IMAGE_GALLERY_URL . 'assets/js/gallery.js', array(), DENKON_IMAGE_GALLERY_VERSION, true );
	}

	public function render_shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'id' => 0, 'layout' => '', 'columns' => 0, 'size' => '', 'lightbox' => '', 'captions' => '', 'ratio' => '', 'gap' => '', 'per_page' => '' ), $attributes, 'denkon_gallery' );
		$gallery_id = absint( $attributes['id'] );
		$page_key = 'dkg_page_' . $gallery_id;
		$page_value = filter_input( INPUT_GET, $page_key, FILTER_VALIDATE_INT );
		return $this->render_gallery( $attributes, $page_value ? max( 1, absint( $page_value ) ) : 1 );
	}

	public function render_block( $attributes ) {
		$pagination_overrides = array(
			'ajax_pagination' => isset( $attributes['ajax_pagination'] ) ? $attributes['ajax_pagination'] : '',
			'load_more'       => isset( $attributes['load_more'] ) ? $attributes['load_more'] : '',
		);
		$attributes = shortcode_atts( array( 'id' => 0, 'layout' => '', 'columns' => 0, 'size' => '', 'lightbox' => '', 'captions' => '', 'ratio' => '', 'gap' => '', 'per_page' => '' ), $attributes );
		return $this->render_gallery( $attributes, 1, $pagination_overrides );
	}

	/**
	 * Return a requested gallery page for public AJAX pagination.
	 *
	 * @return void
	 */
	public function ajax_gallery_page() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'denkon_gallery_pagination' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'denkon-image-gallery' ) ), 403 );
		}

		$raw_attributes = isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array();
		$attributes = array(
			'id'        => isset( $raw_attributes['id'] ) ? absint( $raw_attributes['id'] ) : 0,
			'layout'    => isset( $raw_attributes['layout'] ) ? sanitize_key( $raw_attributes['layout'] ) : '',
			'columns'   => isset( $raw_attributes['columns'] ) ? absint( $raw_attributes['columns'] ) : 0,
			'size'      => isset( $raw_attributes['size'] ) ? sanitize_key( $raw_attributes['size'] ) : '',
			'lightbox'  => isset( $raw_attributes['lightbox'] ) ? sanitize_text_field( $raw_attributes['lightbox'] ) : '',
			'captions'  => isset( $raw_attributes['captions'] ) ? sanitize_text_field( $raw_attributes['captions'] ) : '',
			'ratio'     => isset( $raw_attributes['ratio'] ) ? sanitize_text_field( $raw_attributes['ratio'] ) : '',
			'gap'       => isset( $raw_attributes['gap'] ) ? absint( $raw_attributes['gap'] ) : 0,
			'per_page'  => isset( $raw_attributes['per_page'] ) ? absint( $raw_attributes['per_page'] ) : 0,
		);
		$pagination_overrides = array(
			'ajax_pagination' => true,
			'load_more'       => ! empty( $raw_attributes['load_more'] ) && filter_var( $raw_attributes['load_more'], FILTER_VALIDATE_BOOLEAN ),
		);
		$page = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$html = $this->render_gallery( $attributes, $page, $pagination_overrides );
		if ( ! $html ) {
			wp_send_json_error( array( 'message' => __( 'Gallery not found.', 'denkon-image-gallery' ) ), 404 );
		}
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render one gallery page. Pagination overrides are internal block/AJAX values.
	 *
	 * @param array $attributes Gallery presentation attributes.
	 * @param int   $requested_page Page to render.
	 * @param array $pagination_overrides Block-specific pagination values.
	 * @return string
	 */
	private function render_gallery( $attributes, $requested_page = 1, $pagination_overrides = array() ) {
		$gallery_id = absint( $attributes['id'] );
		if ( self::POST_TYPE !== get_post_type( $gallery_id ) ) { return ''; }
		$image_ids = $this->get_image_ids( $gallery_id );
		if ( ! $image_ids ) { return ''; }
		$settings = $this->get_settings( $gallery_id );
		$columns = absint( $attributes['columns'] ) ? min( 12, max( 1, absint( $attributes['columns'] ) ) ) : $settings['columns'];
		$requested_layout = sanitize_key( $attributes['layout'] );
		$layout = $this->is_layout_available( $requested_layout ) ? $requested_layout : $settings['layout'];
		$layout = $this->is_layout_available( $layout ) ? $layout : 'grid';
		$size = in_array( $attributes['size'], $this->allowed_image_sizes(), true ) ? $attributes['size'] : $settings['size'];
		$ratio = in_array( $attributes['ratio'], array( '1 / 1', '4 / 3', '3 / 2', '16 / 9' ), true ) ? $attributes['ratio'] : $settings['ratio'];
		$gap = '' !== $attributes['gap'] ? min( 48, max( 0, absint( $attributes['gap'] ) ) ) : $settings['gap'];
		$per_page = '' !== $attributes['per_page'] ? min( 100, absint( $attributes['per_page'] ) ) : $settings['per_page'];
		// Carousel layouts need every gallery image in one component, not page-sized fragments.
		if ( in_array( $layout, array( 'imagebrowser', 'slideshow' ), true ) ) {
			$per_page = 0;
		}
		$captions = '' === $attributes['captions'] ? $settings['captions'] : filter_var( $attributes['captions'], FILTER_VALIDATE_BOOLEAN );
		$lightbox = '' === $attributes['lightbox'] ? $settings['lightbox'] : filter_var( $attributes['lightbox'], FILTER_VALIDATE_BOOLEAN );
		$page_key = 'dkg_page_' . $gallery_id;
		$current_page = max( 1, absint( $requested_page ) );
		$total_pages = $per_page ? (int) ceil( count( $image_ids ) / $per_page ) : 1;
		$current_page = min( $current_page, max( 1, $total_pages ) );
		if ( $per_page ) { $image_ids = array_slice( $image_ids, ( $current_page - 1 ) * $per_page, $per_page ); }
		$load_more_requested = array_key_exists( 'load_more', $pagination_overrides ) && '' !== $pagination_overrides['load_more'] ? filter_var( $pagination_overrides['load_more'], FILTER_VALIDATE_BOOLEAN ) : ! empty( $settings['load_more'] );
		$ajax_requested = array_key_exists( 'ajax_pagination', $pagination_overrides ) && '' !== $pagination_overrides['ajax_pagination'] ? filter_var( $pagination_overrides['ajax_pagination'], FILTER_VALIDATE_BOOLEAN ) : ! empty( $settings['ajax_pagination'] );
		$ajax_pagination = ( $ajax_requested || $load_more_requested ) && $total_pages > 1;
		$load_more = $ajax_pagination && $load_more_requested;
		$ajax_query = array( 'id' => $gallery_id, 'layout' => $layout, 'columns' => $columns, 'size' => $size, 'lightbox' => $lightbox ? 'true' : 'false', 'captions' => $captions ? 'true' : 'false', 'ratio' => $ratio, 'gap' => $gap, 'per_page' => $per_page, 'ajax_pagination' => $ajax_pagination ? 'true' : 'false', 'load_more' => $load_more ? 'true' : 'false' );
		$this->enqueue_frontend_assets();

		ob_start(); ?>
		<div class="dkg-gallery-render"<?php if ( $ajax_pagination ) : ?> data-dkg-ajax-gallery data-dkg-query="<?php echo esc_attr( wp_json_encode( $ajax_query ) ); ?>" data-dkg-page="<?php echo esc_attr( $current_page ); ?>" data-dkg-total-pages="<?php echo esc_attr( $total_pages ); ?>"<?php endif; ?>>
		<?php if ( 'imagebrowser' === $layout ) : ?>
			<?php echo $this->render_imagebrowser( $image_ids, $size, $lightbox, $captions, $gap, $settings['crop'] ? $settings['thumbnail_width'] . ' / ' . $settings['thumbnail_height'] : $ratio, $columns ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php elseif ( in_array( $layout, array( 'grid', 'masonry' ), true ) ) : ?>
		<div class="dkg-gallery dkg-gallery--<?php echo esc_attr( $layout ); ?>" style="--dkg-columns: <?php echo esc_attr( $columns ); ?>; --dkg-ratio: <?php echo esc_attr( $settings['crop'] ? $settings['thumbnail_width'] . ' / ' . $settings['thumbnail_height'] : $ratio ); ?>; --dkg-gap: <?php echo esc_attr( $gap ); ?>px" data-dkg-gallery>
			<?php foreach ( $image_ids as $index => $image_id ) :
				$full = wp_get_attachment_image_url( $image_id, 'full' );
				if ( ! $full ) { continue; }
				$caption = wp_get_attachment_caption( $image_id ); ?>
				<figure class="dkg-gallery__item">
					<?php if ( $lightbox ) : ?><a class="dkg-gallery__link" href="<?php echo esc_url( $full ); ?>" data-dkg-lightbox data-dkg-index="<?php echo esc_attr( $index ); ?>" data-dkg-caption="<?php echo esc_attr( $caption ); ?>"><?php endif; ?>
					<?php echo wp_get_attachment_image( $image_id, $size, false, array( 'class' => 'dkg-gallery__image', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $lightbox ) : ?></a><?php endif; ?>
					<?php if ( $captions && $caption ) : ?><figcaption class="dkg-gallery__caption"><?php echo esc_html( $caption ); ?></figcaption><?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
			<?php echo apply_filters( 'denkon_image_gallery_render_layout', '', $layout, $image_ids, array( 'size' => $size, 'lightbox' => $lightbox, 'captions' => $captions, 'gap' => $gap, 'ratio' => $settings['crop'] ? $settings['thumbnail_width'] . ' / ' . $settings['thumbnail_height'] : $ratio, 'columns' => $columns ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php if ( $total_pages > 1 && $load_more && $current_page < $total_pages ) : ?>
			<div class="dkg-pagination dkg-pagination--load-more"><button class="dkg-pagination__link dkg-pagination__load-more" type="button" data-dkg-load-more data-dkg-page="<?php echo esc_attr( $current_page + 1 ); ?>"><?php echo esc_html( $this->t( 'Load more', 'Загрузить ещё' ) ); ?></button></div>
		<?php elseif ( $total_pages > 1 ) : ?>
			<nav class="dkg-pagination" aria-label="<?php echo esc_attr( $this->t( 'Gallery pages', 'Страницы галереи' ) ); ?>">
				<?php for ( $page = 1; $page <= $total_pages; $page++ ) : ?>
					<?php $url = 1 === $page ? remove_query_arg( $page_key ) : add_query_arg( $page_key, $page ); ?>
					<a class="dkg-pagination__link<?php echo $page === $current_page ? ' is-current' : ''; ?>" href="<?php echo esc_url( $url ); ?>" data-dkg-page="<?php echo esc_attr( $page ); ?>"<?php echo $page === $current_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $page ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
		<?php if ( $ajax_pagination ) : ?><p class="dkg-ajax-status" role="status" aria-live="polite"></p><?php endif; ?>
		</div>
		<?php return ob_get_clean();
	}
	/**
	 * Render the Lite ImageBrowser layout: one featured image and a selectable filmstrip.
	 *
	 * @param array  $image_ids Attachment IDs for the current gallery page.
	 * @param string $size      Registered WordPress image size.
	 * @param bool   $lightbox  Whether the full screen viewer is available.
	 * @param bool   $captions  Whether captions are displayed below the featured image.
	 * @param int    $gap       Spacing in pixels.
	 * @param string $ratio     Featured image frame ratio.
	 * @param int    $columns   Number of visible thumbnails.
	 * @return string
	 */
	private function render_imagebrowser( $image_ids, $size, $lightbox, $captions, $gap, $ratio, $columns ) {
		$items = array();
		foreach ( $image_ids as $image_id ) {
			$full = wp_get_attachment_image_url( $image_id, 'full' );
			$image = wp_get_attachment_image_url( $image_id, $size );
			if ( ! $full || ! $image ) {
				continue;
			}

			$items[] = array(
				'id'      => $image_id,
				'full'    => $full,
				'image'   => $image,
				'alt'     => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'caption' => wp_get_attachment_caption( $image_id ),
			);
		}
		if ( empty( $items ) ) {
			return '';
		}

		$first = $items[0];
		ob_start();
		?>
		<div class="dkg-imagebrowser" style="--dkg-gap: <?php echo esc_attr( $gap ); ?>px; --dkg-ratio: <?php echo esc_attr( $ratio ); ?>" data-dkg-gallery data-dkg-imagebrowser data-dkg-thumb-count="<?php echo esc_attr( $columns ); ?>">
			<div class="dkg-imagebrowser__stage">
				<?php foreach ( $items as $index => $item ) : ?>
					<a class="dkg-imagebrowser__stage-link<?php echo 0 === $index ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['full'] ); ?>" data-dkg-browser-stage<?php echo $lightbox ? '' : ' aria-disabled="true"'; ?> aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
						<?php echo wp_get_attachment_image( $item['id'], $size, false, array( 'class' => 'dkg-imagebrowser__stage-image', 'loading' => 0 === $index ? 'eager' : 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endforeach; ?>
				<?php if ( count( $items ) > 1 ) : ?>
					<button class="dkg-imagebrowser__nav dkg-imagebrowser__nav--previous" type="button" data-dkg-browser-previous aria-label="<?php echo esc_attr( $this->t( 'Previous image', 'Предыдущее изображение' ) ); ?>">‹</button>
					<button class="dkg-imagebrowser__nav dkg-imagebrowser__nav--next" type="button" data-dkg-browser-next aria-label="<?php echo esc_attr( $this->t( 'Next image', 'Следующее изображение' ) ); ?>">›</button>
				<?php endif; ?>
			</div>
			<?php if ( $captions ) : ?><p class="dkg-imagebrowser__caption" data-dkg-browser-caption><?php echo esc_html( $first['caption'] ); ?></p><?php endif; ?>
			<div class="dkg-imagebrowser__thumbbar">
				<button class="dkg-imagebrowser__thumb-nav dkg-imagebrowser__thumb-nav--previous" type="button" data-dkg-thumb-previous aria-label="<?php echo esc_attr( $this->t( 'Previous thumbnails', 'Предыдущие миниатюры' ) ); ?>">‹</button>
				<div class="dkg-imagebrowser__thumb-viewport">
					<div class="dkg-imagebrowser__thumbs" role="list" aria-label="<?php echo esc_attr( $this->t( 'Gallery images', 'Изображения галереи' ) ); ?>">
						<?php foreach ( $items as $index => $item ) : ?>
							<a class="dkg-imagebrowser__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['full'] ); ?>" data-dkg-browser-thumb<?php echo $lightbox ? ' data-dkg-lightbox' : ''; ?> data-dkg-caption="<?php echo esc_attr( $item['caption'] ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>">
								<?php echo wp_get_attachment_image( $item['id'], 'thumbnail', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
				<button class="dkg-imagebrowser__thumb-nav dkg-imagebrowser__thumb-nav--next" type="button" data-dkg-thumb-next aria-label="<?php echo esc_attr( $this->t( 'Next thumbnails', 'Следующие миниатюры' ) ); ?>">›</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function enqueue_frontend_assets() {
		wp_enqueue_style( 'denkon-image-gallery' );
		wp_enqueue_script( 'denkon-image-gallery' );
		wp_localize_script( 'denkon-image-gallery', 'denkonImageGallery', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'denkon_gallery_pagination' ),
			'loading' => $this->t( 'Loading…', 'Загрузка…' ),
			'error'   => $this->t( 'Could not load images. Please try again.', 'Не удалось загрузить изображения. Попробуйте ещё раз.' ),
		) );
	}
	private function get_image_ids( $post_id ) { $ids = get_post_meta( $post_id, self::META_IMAGES, true ); return is_array( $ids ) ? array_map( 'absint', $ids ) : array(); }
	private function get_settings( $post_id ) { return wp_parse_args( (array) get_post_meta( $post_id, self::META_SETTINGS, true ), $this->default_settings() ); }
	private function get_layouts() {
		$layouts = array(
			'imagebrowser' => array( 'label' => $this->t( 'ImageBrowser', 'ImageBrowser' ), 'available' => true ),
			'grid'         => array( 'label' => $this->t( 'Grid', 'Сетка' ), 'available' => true ),
			'masonry'      => array( 'label' => 'Masonry', 'available' => true ),
			'mosaic'       => array( 'label' => 'Mosaic', 'available' => false ),
			'slideshow'    => array( 'label' => 'Slideshow', 'available' => false ),
			'tile'         => array( 'label' => 'Tile', 'available' => false ),
		);

		return apply_filters( 'denkon_image_gallery_layouts', $layouts );
	}
	private function is_layout_available( $layout ) { $layouts = $this->get_layouts(); return isset( $layouts[ $layout ] ) && ! empty( $layouts[ $layout ]['available'] ); }
	private function default_settings() { return array( 'layout' => 'grid', 'columns' => 3, 'ratio' => '3 / 2', 'gap' => 16, 'per_page' => 0, 'size' => 'large', 'thumbnail_width' => 600, 'thumbnail_height' => 400, 'crop' => true, 'lightbox' => true, 'captions' => false, 'ajax_pagination' => false, 'load_more' => false ); }
	private function allowed_image_sizes() { return array_values( array_unique( array_merge( array( 'full' ), get_intermediate_image_sizes() ) ) ); }
	private function sanitize_settings( $input ) {
		$defaults = $this->default_settings();
		$settings = array();
		$settings['layout'] = isset( $input['denkon_gallery_layout'] ) && $this->is_layout_available( sanitize_key( $input['denkon_gallery_layout'] ) ) ? sanitize_key( $input['denkon_gallery_layout'] ) : $defaults['layout'];
		$settings['columns'] = isset( $input['denkon_gallery_columns'] ) ? min( 12, max( 1, absint( $input['denkon_gallery_columns'] ) ) ) : $defaults['columns'];
		$settings['ratio'] = isset( $input['denkon_gallery_ratio'] ) && in_array( $input['denkon_gallery_ratio'], array( '1 / 1', '4 / 3', '3 / 2', '16 / 9' ), true ) ? $input['denkon_gallery_ratio'] : $defaults['ratio'];
		$settings['gap'] = isset( $input['denkon_gallery_gap'] ) ? min( 48, max( 0, absint( $input['denkon_gallery_gap'] ) ) ) : $defaults['gap'];
		$settings['per_page'] = isset( $input['denkon_gallery_per_page'] ) && in_array( absint( $input['denkon_gallery_per_page'] ), array( 0, 6, 9, 12, 18, 24, 36 ), true ) ? absint( $input['denkon_gallery_per_page'] ) : $defaults['per_page'];
		$settings['size'] = isset( $input['denkon_gallery_size'] ) && in_array( $input['denkon_gallery_size'], $this->allowed_image_sizes(), true ) ? $input['denkon_gallery_size'] : $defaults['size'];
		$settings['thumbnail_width'] = isset( $input['denkon_gallery_thumbnail_width'] ) ? min( 2400, max( 80, absint( $input['denkon_gallery_thumbnail_width'] ) ) ) : $defaults['thumbnail_width'];
		$settings['thumbnail_height'] = isset( $input['denkon_gallery_thumbnail_height'] ) ? min( 2400, max( 80, absint( $input['denkon_gallery_thumbnail_height'] ) ) ) : $defaults['thumbnail_height'];
		$settings['crop'] = ! empty( $input['denkon_gallery_crop'] );
		$settings['lightbox'] = ! empty( $input['denkon_gallery_lightbox'] );
		$settings['captions'] = ! empty( $input['denkon_gallery_captions'] );
		$settings['load_more'] = defined( 'DENKON_IMAGE_GALLERY_PRO_VERSION' ) && ! empty( $input['denkon_gallery_load_more'] );
		$settings['ajax_pagination'] = defined( 'DENKON_IMAGE_GALLERY_PRO_VERSION' ) && ( ! empty( $input['denkon_gallery_ajax_pagination'] ) || $settings['load_more'] );
		return $settings;
	}
	private function t( $english, $russian ) { $locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(); return 0 === strpos( $locale, 'ru' ) ? $russian : $english; }
}
