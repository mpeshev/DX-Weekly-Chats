<?php 
/**
 * Plugin Name: DX Weekly Chats
 * Description: Weekly chats schedule for WordPress Dev IRC meetings
 * Author: nofearinc
 * Author URI: http://devwp.eu/
 * Version: 1.1
 * License: GPL2

 Copyright 2013 mpeshev (email : mpeshev AT devrix DOT com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

include_once 'dx-weekly-chats-shortcode.php';

add_action( 'init', 'dx_setup_weekly_chats' );

function dx_setup_weekly_chats() {
	add_shortcode( 'dx_weekly_calendar', 'dx_weekly_calendar_shortcode' );
	dx_register_chats_post_type();
}

function dx_register_chats_post_type() {
	register_post_type( 'weekly-chat', array(
		'labels' => array(
			'name' => __("Weekly Chats", 'dxwc'),
			'singular_name' => __("Weekly Chat", 'dxwc'),
			'add_new' => _x("Add New", 'pluginbase', 'dxwc' ),
			'add_new_item' => __("Add New Weekly Chat", 'dxwc' ),
			'edit_item' => __("Edit Weekly Chat", 'dxwc' ),
			'new_item' => __("New Weekly Chat", 'dxwc' ),
			'view_item' => __("View Weekly Chat", 'dxwc' ),
			'search_items' => __("Search Weekly Chats", 'dxwc' ),
			'not_found' =>  __("No Weekly Chats found", 'dxwc' ),
			'not_found_in_trash' => __("No Weekly Chats found in Trash", 'dxwc' ),
		),
		'description' => __("Weekly Chats for the demo", 'dxwc'),
		'public' => true,
		'publicly_queryable' => true,
		'query_var' => true,
		'rewrite' => true,
		'exclude_from_search' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'supports' => array(
			'title',
			'editor',
			'thumbnail',
			'custom-fields',
			'page-attributes',
		),
		'taxonomies' => array( 'post_tag' )
	));
}

add_action( 'add_meta_boxes', 'dx_add_weekly_chats_metabox' );

function dx_add_weekly_chats_metabox() {
	add_meta_box(
		'dx_weekly_chat_box',
		__( "DX Weekly Chat Meta", 'dxwc' ),
		'dx_weekly_chat_meta_callback',
		'weekly-chat',
		'side',
		'high'
	);
}

function dx_weekly_chat_meta_callback( $post ) {
	?>
	<div class="dx-weekly-chats-meta">
		<?php 
			wp_nonce_field( 'weekly_chats_meta', 'weekly_nonce' );
			
			$post_custom = get_post_custom( $post->ID );
			
			// One day we should use the date picker for hours
			$start_time = ! empty( $post_custom['dx_weekly_chat_start_time'] ) ? $post_custom['dx_weekly_chat_start_time'][0] : "11:00";
			$end_time = ! empty( $post_custom['dx_weekly_chat_end_time'] ) ? $post_custom['dx_weekly_chat_end_time'][0] : "12:00";
			$chat_day = ! empty( $post_custom['dx_weekly_chat_chat_day'] ) ? $post_custom['dx_weekly_chat_chat_day'][0] : "Monday";
		?>
		<p>
			<label for="start-time">Start Time UTC (hh:mm)</label>
			<input id="start-time" type="text" name="start_time" value="<?php echo $start_time ?>" />
		</p>
		<p>
			<label for="end-time">End Time UTC (hh:mm)</label>
			<input id="end-time" type="text" name="end_time" value="<?php echo $end_time ?>" />
		</p>
		<p>
			<label for="chat-day">Day of Week</label>
			<select id="chat-day" name="chat_day">
				<?php
					$week_days = array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" );
					foreach( $week_days as $week_day ) {
				?>
					<option value="<?php echo $week_day ?>" <?php selected( $chat_day, $week_day ) ?> ><?php echo $week_day ?></option>
				<?php } ?>
			</select>
		</p>
	</div>
	<?php 
}

add_action( 'save_post', 'dx_weekly_chat_save_controller' );

function dx_weekly_chat_save_controller( $post_id ) {
	// Bail for revisions and missing data
	if ( wp_is_post_revision( $post_id ) )
		return;
	
	if( ! isset( $_POST['weekly_nonce'] ) || ! wp_verify_nonce( $_POST['weekly_nonce'], 'weekly_chats_meta' ) )
		return;
	
	if( ! isset( $_POST['start_time'] ) || ! isset( $_POST['end_time'] ) || ! isset( $_POST['chat_day'] ) )
		return;
	
	$start_time = esc_html( $_POST['start_time'] );
	$end_time = esc_html( $_POST['end_time'] );
	$chat_day = esc_html( $_POST['chat_day'] );
	
	update_post_meta( $post_id, 'dx_weekly_chat_start_time', $start_time );
	update_post_meta( $post_id, 'dx_weekly_chat_end_time', $end_time );
	update_post_meta( $post_id, 'dx_weekly_chat_chat_day', $chat_day );
}

add_action( 'wp_enqueue_scripts', 'dx_add_frontend_weekly_chats_styles' );

function dx_add_frontend_weekly_chats_styles() {
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-button' );
	wp_enqueue_script( 'jquery-ui-droppable' );
	wp_enqueue_script( 'jquery-ui-widget' );
	wp_enqueue_script( 'jquery-ui-resizable' );
	wp_enqueue_script( 'jquery-weekly-calendar', plugins_url( '/js/jquery.weekcalendar.js', __FILE__ ), array( 'jquery-ui-widget' ) );
	wp_enqueue_style( 'jquery-weekly-calendar', plugins_url( '/css/jquery.weekcalendar.css', __FILE__ ) );
	wp_enqueue_style( 'jquery-weekly-calendar-default-skin', plugins_url( '/skins/default.css', __FILE__ ) );
	wp_enqueue_style( 'jquery-weekly-calendar-gcalendar', plugins_url( '/skins/gcalendar.css', __FILE__ ) );
}
