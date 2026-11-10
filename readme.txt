=== Denkon Image Gallery ===
Contributors: denkon77
Tags: image gallery, gallery block, lightbox, masonry, gutenberg
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.27
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create fast, responsive image galleries from the native WordPress Media Library.

== Description ==

Denkon Image Gallery lets you create and display polished image galleries without duplicate uploads, custom database tables, external services, or third-party front-end libraries.

Gallery images remain standard WordPress media attachments. Gallery configuration is stored in post meta and can be displayed using the included Gutenberg block or shortcode.

Features included in the free version:

* Create unlimited galleries from the WordPress Media Library.
* Drag and drop image ordering in the gallery editor.
* Grid, Masonry, and ImageBrowser layouts.
* Responsive columns, card ratio, crop, spacing, and WordPress image size settings.
* ImageBrowser carousel with thumbnail navigation and keyboard-accessible lightbox.
* Pagination for Grid and Masonry galleries.
* Captions sourced from WordPress attachment captions.
* Dynamic Gutenberg block with per-page display overrides.
* Shortcode support: `[denkon_gallery id="123"]`.
* English interface by default and Russian interface for Russian WordPress admin locales.

The plugin does not connect to external servers, collect analytics, create user accounts, or transmit personal data. All included code and assets are original Denkon assets and are distributed under GPLv2 or later.

== Development ==

Public source repository: https://github.com/Denkon2/denkon-image-gallery

All source code used by the plugin is included in this distribution and the public repository. The Gutenberg block source is maintained directly in `blocks/gallery/index.js`; front-end and admin scripts are maintained directly in `assets/js/`. No bundler, transpiler, minifier, or external build step is required to recreate the distributed files.

== Installation ==

1. Upload the `denkon-image-gallery` folder to the `/wp-content/plugins/` directory, or install the ZIP file from **Plugins > Add New > Upload Plugin**.
2. Activate **Denkon Image Gallery** through the Plugins screen in WordPress.
3. Open **Galleries > Create gallery**, add images, set the default display options, and save.
4. Add the **Denkon Image Gallery** block to a post or page, or insert `[denkon_gallery id="123"]` with the gallery ID.

== Frequently Asked Questions ==

= Does the plugin create a separate image library or duplicate uploaded images? =

No. It uses WordPress attachments from the native Media Library and stores only the ordered attachment IDs in gallery post meta.

= Can I override a gallery setting on one page only? =

Yes. The Gutenberg block and shortcode attributes override the saved gallery defaults only where they are explicitly set.

= Why is “Images per page” unavailable for ImageBrowser? =

ImageBrowser is a carousel. It needs every gallery item available for next/previous navigation, so pagination is intentionally disabled for that layout.

= What happens when the plugin is deleted? =

Gallery posts and their settings are retained by default. This prevents content referenced by blocks and shortcodes from disappearing unexpectedly. You can remove gallery posts manually from the Galleries screen when they are no longer needed.

== Shortcode ==

`[denkon_gallery id="123"]`

Available optional attributes: `layout`, `columns` (1-12), `ratio`, `gap`, `size`, `per_page`, `lightbox`, and `captions`.

== Changelog ==

= 0.2.27 =

* Added the public source repository and updated plugin metadata.

= 0.2.21 =

* Fixed selected values in Gutenberg select controls.

= 0.2.20 =

* Added extension hooks for independently distributed gallery layouts.
* Added support for the Denkon Image Gallery Pro Mosaic layout.

= 0.2.19 =

* Removed obsolete manual translation loading for WordPress.org language packs.
* Hardened public gallery pagination input handling.

= 0.2.18 =

* Added the visual layout picker to the Gutenberg block.
* Added ImageBrowser, Grid, and Masonry layout selection with visible Pro layout previews.
* Added image card dimensions and crop settings.
* Added ImageBrowser thumbnail carousel navigation.
* Added compatibility with image-optimization plugins that use `picture`, WebP, or AVIF markup.

= 0.2.0 =

* Initial public release.
