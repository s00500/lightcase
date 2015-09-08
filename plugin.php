<?php
/**
* Plugin Name: Lightcase Lightbox (LBs Fork)
* Plugin URI: https://github.com/s00500/lightcase/
* Description: Use this plugin to implement the lightcase lightbox
* Version: 1.0
* Author: Chris McCoy,(Fork by Lukas Bachscwell)
* Author URI: http://github.com/chrismccoy, lbsfilm.at

* @copyright 2015
* @author Chris McCoy, (Fork by Lukas Bachscwell)
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* @package Lightcase_Lightbox
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* Initiate Lightcase Lightbox Class on plugins_loaded
*
* @since 1.0
*/

if ( !function_exists( 'lightcase_lightbox' ) ) {
	
	function lightcase_lightbox() {
		$lightcase_lightbox = new Lightcase_Lightbox();
	}
	
	add_action( 'plugins_loaded', 'lightcase_lightbox' );
}

/**
* Lightcase Lightbox Class for scripts, styles, and shortcode
*
* @since 1.0
*/

if( !class_exists( 'Lightcase_Lightbox' ) ) {
	
	class Lightcase_Lightbox {
		
		public $gallGroup = "default";
		/**
		* Hook into hooks for Register styles, scripts, and shortcode
		*
		* @since 1.0
		*/
		
		function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
			
			add_filter( 'post_gallery', array( $this, 'get_group'), 10, 2 );
			add_filter('the_content', array( $this, 'autoexpand_rel_wlightcase'), 99);
			
			add_action( 'embed_oembed_html', array( $this, 'embed_html' ), 10, 4);
			add_action( 'init', array( $this, 'embeds' ));
			
		}
		
		
		/*LB Add*/
		
		function get_group($html, $attr) {
			$gallGroup = $attr['group'];
			add_filter('wp_get_attachment_link',array($this,'lightcase_gallery_links'),10,1);
			return '';
		}
		
		function lightcase_gallery_links($html){
			if(!isset($gallGroup) || $gallGroup == -1){return $html;}
			return str_replace('<a','<a data-rel="lightcase:gallery-['.$gallGroup.']:slideshow"', $html);
		}
		
		function autoexpand_rel_wlightcase($content) {
			global $post;
			$id = isset($post->ID) ? $post->ID : -1;
			$content = $this->do_regexp($content, $id);
			return $content;
		}
		
		function do_regexp($content, $id){
			$id = esc_attr($id);
			$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:bmp|gif|jpg|jpeg|png)(\?\S{0,}){0,1}['\"][^\>]*)>/i";
			$replacement =  '$1 data-rel="lightcase:gallery-['.$id.']:slideshow" title="'.$id.'">';
			return preg_replace($pattern, $replacement, $content);
		}
		
		
		
		
		/**
		* enqueue lightcase lightbox javascript
		*
		* @since 1.0
		*/
		function scripts() {
			wp_enqueue_script( 'lightcase_js', plugins_url( 'js/lightcase.min.js', __FILE__ ), array( 'jquery' ), '1.0', false );
			
		}
		
		/**
		* enqueue lightcase lightbox styles
		*
		* @since 1.0
		*/
		
		function styles() {
			wp_enqueue_style( 'lightcase_css', plugins_url( 'css/lightcase.css', __FILE__ ), false, '1.0', 'screen' );
			
			if ( @file_exists( get_stylesheet_directory() . '/lightcase_custom.css' ) )
			$css_file = get_stylesheet_directory_uri() . '/lightcase_custom.css';
			elseif ( @file_exists( get_template_directory() . '/lightcase_custom.css' ) )
			$css_file = get_template_directory_uri() . '/lightcase_custom.css';
			else
			$css_file = plugins_url( 'css/custom.css', __FILE__ );
			
			wp_enqueue_style( 'lightcase_custom_css', $css_file, false, '1.0', 'screen' );
			
		}
		
		
		/**
		* register oembed for images, and remove imgur.com default embed so lightbox can use imgur.com images
		*
		* @since 1.0
		*/
		
		function embeds() {
			wp_embed_register_handler( 'detect_lightbox', '#^http://.+\.(jpe?g|gif|png)$#i', array( $this, 'wp_embed_register_handler') , 10, 3);
			wp_oembed_remove_provider( '#https?://(.+\.)?imgur\.com/.*#i' );
		}
		
		/**
		* convert image urls to oembed with lightcase markup
		*
		* @since 1.0
		*/
		
		function wp_embed_register_handler( $matches, $attr, $url, $rawattr ) {
			global $post;
			
			if (preg_match('#^http://.+\.(jpe?g|gif|png)$#i', $url)) {
				$embed = sprintf('<a href="%s" class="thumbnail" data-rel="lightcase:gallery-%s:slideshow"><img src="%s"></a>', $matches[0], $post->ID, $matches[0]);
			}
			
			$embed = apply_filters( 'oembed_detect_lightbox', $embed, $matches, $attr, $url, $rawattr );
			
			return apply_filters( 'oembed_result', $embed, $url);
		}
	}
}
