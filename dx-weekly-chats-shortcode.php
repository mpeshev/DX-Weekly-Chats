<?php
/**
 * It's the listing calendar shortcode
 * 
 * I hate doing nasty PHP-in-JS concatenation, but AJAX request would also require
 * some JS math after all so please forgive me.
 * 
 * @param array $args shortcode args (some day, one day)
 */
function dx_weekly_calendar_shortcode( $args ) {
	// Print the header links with time zones
	dx_print_list_of_links();
	
	// Fetch all weekly chats, and abandon the pagination limit or suffer
	$weekly_chats = get_posts( array( 'post_type' => 'weekly-chat', 'posts_per_page' => 50 ) );

	if( empty( $weekly_chats ) )
		return;

	$events = array();

	// Prepare the JavaScript-less-unfriendly array to work with.
	foreach( $weekly_chats as $weekly_chat ) {
		$post_id = $weekly_chat->ID;

		$chat_custom = get_post_custom( $post_id );

		if( ! isset( $chat_custom['dx_weekly_chat_start_time'] )
				|| ! isset( $chat_custom['dx_weekly_chat_end_time'] )
				|| ! isset( $chat_custom['dx_weekly_chat_chat_day'] ) )
			break;

		$post_title = $weekly_chat->post_title;

		$start_time = $chat_custom['dx_weekly_chat_start_time'];
		$end_time = $chat_custom['dx_weekly_chat_end_time'];
		$chat_day = $chat_custom['dx_weekly_chat_chat_day'];

		$events[] = array(
				'post_title' => $post_title,
				'start_time' => $start_time,
				'end_time' => $end_time,
				'chat_day' => $chat_day
		);
	}
	
	$events_count = count( $events );
	$event_id = 1;

	// This seems fine, but I'm buffering just in case one needs filtering
	
	ob_start();
	?>
	<style type="text/css">
		.wc-nav { display: none; }
		ul.dx-weekly-time-zones { list-style-type: none; }
		ul.dx-weekly-time-zones { list-style-type: none; margin: 0px 15px; }
		.dx-weekly-time-zones li { float: left; padding: 0px 15px; }
	</style>
	<?php 
		$css = ob_get_clean();
		
		$css = apply_filters( 'dx_weekly_filter_calendar_css', $css );
		echo $css;
	?>
	<script type='text/javascript'>
		var dateNow = new Date();
	
		var year = dateNow.getFullYear();
		var month = dateNow.getMonth();
		var day = dateNow.getDate();
		var dayOfWeek = dateNow.getDay();
		
		var eventData = {
			events : [
		<?php 
			foreach( $events as $event ) {
				$day_number = dx_get_day_from_week_js_style( $event['chat_day'][0] );
				$start_time = $event['start_time'][0];
				$start_split = explode( ':' , $start_time );
				$start_hour = (int) $start_split[0];
				$start_min = (int) $start_split[1];
				
				$end_time = $event['end_time'][0];
				$end_split = explode( ':' , $end_time );
				$end_hour = (int) $end_split[0];
				$end_min = (int) $end_split[1];
				
				if( isset( $_GET['utc'] ) ) {
					$utc_diff = (int) $_GET['utc'];
					
					$start_hour = $start_hour + $utc_diff;
					$end_hour = $end_hour + $utc_diff;
				} 
				
		?>
			{'id':<?php echo $event_id; ?>, 'start': new Date(year, month, day - (dayOfWeek - <?php echo $day_number; ?>), <?php echo $start_hour ?>, <?php echo $start_min; ?>), 'end': new Date(year, month, day - (dayOfWeek - <?php echo $day_number; ?>), <?php echo $end_hour; ?>, <?php echo $end_min; ?>),'title':'<?php echo $event['post_title'] ?>'}<?php
				if( $event_id < $events_count ) echo "," . PHP_EOL; 
				$event_id++;
			}
		?>
			]
		};
		
		jQuery(document).ready(function($) {
			$('#dx-weekly-calendar').weekCalendar({
				timeslotsPerHour: 2,
				timeslotHeight: 30,
				hourLine: true,
				data: eventData,
				readonly: true,
				allowCalEventOverlap: true, // important for overlapping events
				overlapEventsSeparate: true,
				height: function($calendar) {
					return $(window).height() - $('h1').outerHeight(true);
				},
				eventRender : function(calEvent, $event) {
					if (calEvent.end.getTime() < new Date().getTime()) {
						$event.css('backgroundColor', '#aaa');
						$event.find('.time').css({'backgroundColor': '#999', 'border':'1px solid #888'});
					}
				},
			});
			function displayMessage(message) {
				$('#message').html(message).fadeIn();
			}
	
			$('<div id="message" class="ui-corner-all"></div>').prependTo($('body'));
		});
	
	</script>
		<div id="dx-weekly-calendar"></div>
	<?php 
}

/**
 * Magic. 
 * 
 * @param string $day_of_week text as in a day of week
 * @return int the number of day of week
 */
function dx_get_day_from_week_js_style( $day_of_week ) {
	switch( $day_of_week ) {
		case "Monday":
			return 1;
		case "Tuesday":
			return 2;
		case "Wednesday":
			return 3;
		case "Thursday":
			return 4;
		case "Friday":
			return 5;
		case "Saturday":
			return 6;
		case "Sunday":
			return 7;
		default:
			return 1;
	}
}

/**
 * Print list of links
 */
function dx_print_list_of_links() {
	$permalink = get_permalink();
	
	if( strpos( $permalink, '?' ) ) {
		$permalink .= '&utc=';
	} else {
		$permalink .= '?utc=';
	}
	
	$time_zones = array(
		'PST' => '-7',
		'CST' => '-5',
		'EST' => '-4',
		'UTC' => '0',
		'EEST' => '+3'		
	);

	$time_zones = apply_filters( 'dx_weekly_time_zone_filter', $time_zones );
	
?>
	<ul class="dx-weekly-time-zones">
		<?php foreach( $time_zones as $zone => $hours ) { ?>
			<li><a href="<?php echo $permalink . $hours ?>"><?php echo $zone; ?></a></li>
		<?php } ?>
	</ul>
<?php 
}