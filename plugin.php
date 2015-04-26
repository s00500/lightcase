<?php
/**
 * Plugin Name: Lightcase Lightbox
 * Plugin URI: http://github.com/sugar/lightcase
 * Description: Use this plugin to implement the lightcase lightbox
 * Version: 1.0
 * Author: Chris McCoy
 * Author URI: http://github.com/chrismccoy

 * @copyright 2015
 * @author Chris McCoy
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

		/**
 		* Hook into hooks for Register styles, scripts, and shortcode
 		*
 		* @since 1.0
 		*/

		function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
			add_filter( 'post_gallery', array( $this, 'gallery'), 10, 2 );
			add_filter( 'media_send_to_editor', array( $this, 'media_filter'), 20, 3);
			add_action( 'embed_oembed_html', array( $this, 'embed_html' ), 10, 4);
			add_action( 'init', array( $this, 'embeds' ));
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
         	* add lightcase data attributes to images inserted into post
         	*
         	* @since 1.0
         	*/

		function media_filter($html, $attachment_id) {

    			$attachment = get_post($attachment_id);

			$types = array('image/jpeg', 'image/gif', 'image/png');

			if(in_array($attachment->post_mime_type, $types) ) {
				$lightcase_attr = sprintf('class="thumbnail" data-rel="lightcase:gallery-%s:slideshow" title="%s"', $attachment->post_parent, $attachment->post_excerpt);
    				$html = '<a href="'. wp_get_attachment_url($attachment_id) .'" '. $lightcase_attr .'><img src="'. wp_get_attachment_thumb_url($attachment_id) .'"></a>';
			}

			return $html;
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

        	/**
         	* modified gallery output for lightcase lightbox
         	*
         	* @since 1.0
         	*/

		function gallery( $content, $attr ) {
    			global $instance, $post;

    			$instance++;

    			if ( isset( $attr['orderby'] ) ) {
        			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
        			if ( ! $attr['orderby'] )
            				unset( $attr['orderby'] );
    			}

    			extract( shortcode_atts( array(
        			'order'      =>  'ASC',
        			'orderby'    =>  'menu_order ID',
        			'id'         =>  $post->ID,
        			'itemtag'    =>  'figure',
        			'icontag'    =>  'div',
        			'captiontag' =>  'figcaption',
        			'columns'    =>   3,
        			'size'       =>   'thumbnail',
        			'include'    =>   '',
        			'exclude'    =>   ''
    			), $attr ) );

    			$id = intval( $id );

    			if ( 'RAND' == $order ) {
        			$orderby = 'none';
    			}

    			if ( $include ) {
        
        			$include = preg_replace( '/[^0-9,]+/', '', $include );
        
        			$_attachments = get_posts( array(
            				'include'        => $include,
            				'post_status'    => 'inherit',
            				'post_type'      => 'attachment',
            				'post_mime_type' => 'image',
            				'order'          => $order,
            				'orderby'        => $orderby
        			) );

        			$attachments = array();
        
        			foreach ( $_attachments as $key => $val ) {
            				$attachments[$val->ID] = $_attachments[$key];
        			}

    				} elseif ( $exclude ) {
        
        				$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
        
        				$attachments = get_children( array(
            					'post_parent'    => $id,
            					'exclude'        => $exclude,
            					'post_status'    => 'inherit',
            					'post_type'      => 'attachment',
            					'post_mime_type' => 'image',
            					'order'          => $order,
            					'orderby'        => $orderby
        				) );

    				} else {

        				$attachments = get_children( array(
            					'post_parent'    => $id,
            					'post_status'    => 'inherit',
            					'post_type'      => 'attachment',
            					'post_mime_type' => 'image',
            					'order'          => $order,
            					'orderby'        => $orderby
        				) );

    				}

    				if ( empty( $attachments ) ) {
        				return;
    				}

    				if ( is_feed() ) {
        				$output = "\n";
        				foreach ( $attachments as $att_id => $attachment )
            					$output .= wp_get_attachment_link( $att_id, $size, true ) . "\n";
        				return $output;
    				}

    				$output = "\n" . '<div class="lightcase_gallery">' . "\n";

    				foreach ( $attachments as $id => $attachment ) {
					$lightcase_attr = sprintf('class="thumbnail" data-rel="lightcase:gallery-%s:slideshow" title="%s"', $post->ID, $attachment->post_excerpt);
        				$output .= '<a href="'. wp_get_attachment_url($id) .'" '. $lightcase_attr. '><img src="'. wp_get_attachment_thumb_url($id) .'"></a>' . "\n";
    				}

    				$output .= "</div>" . "\n";

    			return $output;
		}
   	}
}

