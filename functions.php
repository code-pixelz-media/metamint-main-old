<?php
/**
 * Metamint Apps theme bootstrap.
 */

if (!defined('ABSPATH')) {
	exit;
}

// Global Theme Prefix for ACF Blocks
if (!defined('THEME_BLOCK_PREFIX')) {
	define('THEME_BLOCK_PREFIX', 'metamint-main');
}

define('METAMINT_VERSION', '1.0.0');
define('METAMINT_DIR', get_template_directory());
define('METAMINT_URI', get_template_directory_uri());

add_action('after_setup_theme', function () {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('align-wide');
	add_theme_support('wp-block-styles');
	add_theme_support('responsive-embeds');
	add_theme_support('editor-styles');
	add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
	add_theme_support('woocommerce');
	add_editor_style('assets/css/editor-style.css');

	register_nav_menus([
		'primary' => __('Primary Menu', 'metamint'),
		'footer' => __('Footer Menu', 'metamint'),
	]);
});

// Allow SVG Uploads
function niche_surfaces_mime_types($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'niche_surfaces_mime_types');

// Fix SVG Display in Media Library
function fix_svg()
{
	echo '<style type="text/css">
          .attachment-266x266, .thumbnail img {
               width: 100% !important;
               height: auto !important;
          }
          </style>';
}
add_action('admin_head', 'fix_svg');

add_action('wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'metamint-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;600&display=swap',
		[],
		null
	);
	wp_enqueue_style('metamint-main', METAMINT_URI . '/assets/css/main.css', ['metamint-fonts'], time());
	wp_enqueue_script('metamint-main', METAMINT_URI . '/assets/js/main.js', [], time(), true);
	wp_enqueue_style('metamint-mega-menu', METAMINT_URI . '/assets/css/mega-menu.css', [], time());
	wp_enqueue_script('metamint-mega-menu', METAMINT_URI . '/assets/js/mega-menu.js', [], time(), true);
});

add_action('enqueue_block_editor_assets', function () {
	wp_enqueue_style(
		'metamint-editor-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;600&display=swap',
		[],
		null
	);

	wp_enqueue_script(
		'metamint-editor-hide-header-footer',
		METAMINT_URI . '/assets/js/editor-hide-header-footer.js',
		['wp-blocks', 'wp-dom-ready', 'wp-data'],
		time(),
		true
	);

	// Ensure styles are also injected directly into editor assets
	$editor_css = '
		body.post-type-page:not(.is-editing-template) header.wp-block-template-part,
		body.post-type-page:not(.is-editing-template) footer.wp-block-template-part,
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-type="core/template-part"][aria-label*="Header"],
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-type="core/template-part"][aria-label*="Footer"],
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-title*="Header"],
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-title*="Footer"],
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-slug*="header"],
		body.post-type-page:not(.is-editing-template) .wp-block-template-part[data-slug*="footer"],
		body.post-type-page:not(.is-editing-template) [data-title="Header"],
		body.post-type-page:not(.is-editing-template) [data-title="Footer"],
		body.metamint-hide-header-footer header.wp-block-template-part,
		body.metamint-hide-header-footer footer.wp-block-template-part,
		body.metamint-hide-header-footer .wp-block-template-part[data-type="core/template-part"][aria-label*="Header"],
		body.metamint-hide-header-footer .wp-block-template-part[data-type="core/template-part"][aria-label*="Footer"],
		body.metamint-hide-header-footer .wp-block-template-part[data-title*="Header"],
		body.metamint-hide-header-footer .wp-block-template-part[data-title*="Footer"],
		body.metamint-hide-header-footer .wp-block-template-part[data-slug*="header"],
		body.metamint-hide-header-footer .wp-block-template-part[data-slug*="footer"],
		body.metamint-hide-header-footer [data-title="Header"],
		body.metamint-hide-header-footer [data-title="Footer"] {
			display: none !important;
		}
		body.post-type-page:not(.is-editing-template) .is-root-container,
		body.metamint-hide-header-footer .is-root-container {
			padding-top: 40px !important;
			padding-bottom: 40px !important;
		}
	';
	wp_add_inline_style('metamint-editor-fonts', $editor_css);
});


/**
 * Fix: REST API nonce/cookie auth in Local by Flywheel dev environment.
 * Ensures logged-in users pass REST authentication without failing nonce checks due to cookie policies.
 */
add_filter('rest_authentication_errors', function ($result) {
	if (!empty($result)) {
		return $result;
	}
	if (!is_user_logged_in()) {
		return $result;
	}
	return true;
});

/**
 * Fix: WordPress core bug where requesting taxonomies for a post type with no taxonomies
 * (such as 'page') triggers a 403 rest_cannot_view error in WP_REST_Taxonomies_Controller.
 */
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
	if ($request->get_route() === '/wp/v2/taxonomies' && $request->get_param('context') === 'edit') {
		$type = $request->get_param('type');
		if (!empty($type)) {
			$taxonomies = get_object_taxonomies($type, 'objects');
			if (empty($taxonomies)) {
				return new WP_REST_Response((object) []);
			}
		}
	}
	return $result;
}, 10, 3);

require_once METAMINT_DIR . '/acf-config.php';
require_once METAMINT_DIR . '/inc/acf-functions.php';
require_once get_theme_file_path('/inc/block-assets.php');
require_once get_theme_file_path('/inc/column-link.php');


/**
 * Small SCF-safe getter: uses SCF/ACF when active, otherwise returns the fallback.
 */
function mm_field(string $key, $fallback = '', $post_id = false)
{
	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);
		if ($value !== null && $value !== '' && $value !== []) {
			return $value;
		}
	}
	return $fallback;
}

