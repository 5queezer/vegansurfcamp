<?php
class wpdevart_bc_BookingCalendar {
	
	private $res;
	private $global_settings;
	private $res_dates = array();
	private $theme_option;
	private $calendar_data;
	private $form_data;
	private $extra_field;
	private $id;
	private $selected;
	private $ajax;
	private $use_mo = true;
	private $booking_id;
	private $calendar_title = "";
	private $currency = "$";
	private $for_tr = array();
	private $week_days = array(
		"Sunday",
		"Monday",
		"Tuesday",
		"Wednesday",
		"Thursday",
		"Friday",
		"Saturday"
	);
	private $abbr_week_days = array(
		"Sun",
		"Mon",
		"Tue",
		"Wed",
		"Thu",
		"Fri",
		"Sat"
	);
	private $short_week_days = array(
		"Su",
		"Mo",
		"Tu",
		"We",
		"Th",
		"Fr",
		"Sa"
	);
	public $year, $month, $day, $month_days_count, $month_start, $month_name, $prev_month, $next_month,$bookings = array();
	public static $list_of_animations=array('bounce','flash','pulse','rubberBand','shake','swing','tada','wobble','bounceIn','bounceInDown','bounceInLeft','bounceInRight','bounceInUp','fadeIn','fadeInDown','fadeInDownBig','fadeInLeft','fadeInLeftBig','fadeInRight','fadeInRightBig','fadeInUp','fadeInUpBig','flip','flipInX','flipInY','lightSpeedIn','rotateIn','rotateInDownLeft','rotateInDownRight','rotateInUpLeft','rotateInUpRight','rollIn','zoomIn','zoomInDown','zoomInLeft','zoomInRight','zoomInUp');
	
	public function __construct($date, $res, $id, $theme_option, $calendar_data, $form_option, $extra_field, $selected = array(),$ajax = false,$widget=false,$text_for=array(),$calendar_title = "") {
		$this->global_settings = get_option("wpdevartec_settings") === false ? array() :  json_decode(get_option("wpdevartec_settings"), true);
        $this->theme_option = $theme_option;
        $this->calendar_title = $calendar_title;
        $this->res = $res;
        $this->calendar_data = $calendar_data;
        $this->form_data = $form_option;
        $this->extra_field = $extra_field;
        $this->id = $id;
        $this->ajax = $ajax;
        $this->selected = $selected;
        $currency_list = wpdevart_bc_get_currency();
		$this->for_tr = $text_for;
		if($widget == true) {
			$this->booking_id = wpdevart_bc_calendar::$booking_count + 1000;
		} else {
			$this->booking_id = wpdevart_bc_calendar::$booking_count;
		}
		if(isset($this->theme_option['currency']) && isset($currency_list[esc_html($this->theme_option['currency'])])) {
			$this->currency = $currency_list[esc_html($this->theme_option['currency'])]['simbol'];
		}
		if(isset($this->theme_option['use_mo']) && $this->theme_option['use_mo'] == "on") {
			$this->use_mo = true;
		}
		
		if($date == "")
			$date = date("Y-m-d");
		$date_array = explode( '-', $date );
		$year      = $date_array[0];
		$month     = $date_array[1];
		$day       = $date_array[2];
		if (isset( $_REQUEST['year'] ) && $_REQUEST['year'] != '') {
			$year = $_REQUEST['year'];
		}
		if (isset( $_REQUEST['month'] ) && $_REQUEST['month'] != '') {
			$month = $_REQUEST['month'];
		}
		if (isset( $_REQUEST['day'] ) && $_REQUEST['day'] != '') {
			$day = $_REQUEST['day'];
		}
		$this->month = (int) $month;
		$this->year  = (int) $year;
		$this->day   = (int) $day;
		$this->month_days_count =  $this->month == 2 ? ($this->year % 4 ? 28 : ($this->year % 100 ? 29 : ($this->year %400 ? 28 : 29))) : (($this->month - 1) % 7 % 2 ? 30 : 31);

		$this->month_start = date('N', strtotime($this->year."-".$this->month."-01"));
		$this->month_name =__(date('F', strtotime($this->year."-".$this->month."-".$this->day)), 'booking-calendar' );
		if(!is_null($this->res)){
			if($this->res["check_in"]) {
				$check_in = $this->res["check_in"];
				$check_out = $this->res["check_out"];
			} else {
				$single_day = $this->res["single_day"];
			}
			if(isset($check_in)){
				$date_diff = abs($this->get_date_diff($check_in,$check_out));
				for($i=0; $i <= $date_diff; $i++) {
					$this->res_dates[] = date( 'Y-m-d', strtotime($check_in. " +" . $i . " day" ));
				}
			} elseif(isset($single_day)) {
				$this->res_dates[] = $single_day;
			}	
		}		
	}

    /*############  Reservations function ################*/	
	
	public function booking_calendar($reservation = "") {
		$prev_year      = $this->calculate_date( $this->year . '-' . $this->month, '-1', 'year' );
		$prev_year_info = $prev_year['year'] . '-' . $prev_year['month'];
		
		$prev      = $this->calculate_date( $this->year . '-' . $this->month, '-1', 'month' );
		$prev_date_info = $prev['year'] . '-' . $prev['month'];
		
		$prev_date = '';
		$this->prev_month = $this->get_month_name($prev['year'] . '-' . $prev['month'],0);
		$prev_html = '<span><</span><span class="wpda-month-name"> ' . __($this->prev_month, 'booking-calendar') . ' ' . $prev_date . '</span>';
		
		$next      = $this->calculate_date( $this->year . '-' . $this->month . '-1', '+ 1', 'month' );
		$next_date = '';
		$next_date_info = $next['year'] . '-' . $next['month'] . '-' . $next['day'];
		$this->next_month = $this->get_month_name($next['year'] . '-' . $next['month'],0);
		
		$next_year      = $this->calculate_date( $this->year . '-' . $this->month . '-1', '+ 1', 'year' );
		$next_year_info = $next_year['year'] . '-' . $next_year['month'] . '-' . $next_year['day'];
		
		$next_html = '<span class="wpda-month-name">' . $next_date . ' ' . __( $this->next_month, 'booking-calendar' ) . ' </span><span>></span>';
		
		$booking_calendar = '';
		$booking_calendar .= '<div class="wpda-booking-calendar-head '.$reservation.'">';
		// previous month link
		$booking_calendar .= '<div class="wpda-previous"><a href="?date=' . $prev_date_info . '" rel="nofollow, noindex" class="wpdevart_link">' . $prev_html . '</a></div>'; 
		//current date info
		$booking_calendar .= '<div class="current-date-info"><a href="?date=' . $prev_year_info . '" rel="nofollow, noindex" class="wpdevart_link prev_year_info"><</a><span class="wpda-current-year">' . $this->year . '</span><a href="?date=' . $next_year_info . '" rel="nofollow, noindex" class="wpdevart_link next_year_info">></a>&nbsp;<span class="wpda-current-month">' . __( $this->month_name, 'booking-calendar' ) . '</span></div>';
        // next month link
		$booking_calendar .= '<div class="wpda-next"><a href="?date=' . $next_date_info . '" rel="nofollow, noindex" class="wpdevart_link">' . $next_html . '</a></div>';
		$booking_calendar .= '</div>';
        // booking calendar container
		if( $reservation == "") {
			$booking_calendar .= '<div class="wpdevart-calendar-container div-for-clear">';
		} else {
			$booking_calendar .= '<table class="wpdevart-calendar-container" data-id="' . $this->id . '">';
		}
		if (isset($this->theme_option['week_days']) && $this->theme_option['week_days'] == 0) {
			$week_days = $this->week_days;
		} else if (isset($this->theme_option['week_days']) && $this->theme_option['week_days'] == 1) {
			$week_days = $this->abbr_week_days;
		} else {
			$week_days = $this->short_week_days;
		}
		$day_start = (isset($this->theme_option["day_start"])? $this->theme_option["day_start"] : 0);
		for ($i = 0; $i < count( $week_days ); $i ++) {
			$di      = ( $i + $day_start ) % 7;
			$week_day = $week_days[ $di ];
			if ($i == 0) {
				$cell_class = 'week-day-name week-start';
			} else {
				$cell_class = 'week-day-name';
			}
			$booking_calendar .= $this->booking_calendar_cell( __( $week_day, 'booking-calendar' ), $cell_class );
		}
        /* previous month cells */
		$empty_cells = 0;
		$count_in_row = 7;

        /* week start days */
		$week_start_days = $this->month_start - $day_start;
		if ($week_start_days < 0) {
			$week_start_days = $week_start_days + $count_in_row;
		}
		$r = 0;
		for ($i = $week_start_days; $i > 0; $i--) {
			if ( $i == 0 ) {
				$cell_class = 'past-month-day week-start';
			}
			else {
				$cell_class = 'past-month-day';
			}
			$day_count = ($i==1) ? "day" : "days";
			$day = date("j",strtotime("".($this->year . '-' . ($this->month) . '-1')." -".$i." ".$day_count.""));
			if($this->month == 1) {
				$month = 13;
			} else {
				$month = $this->month;
			}
			if($month == 13) {
				$date = ($this->year - 1) . '-' . ($month-1) . '-' . $day;
			} else {
				$date = $this->year . '-' . ($month-1) . '-' . $day;
			}
			if($r == 0  && $reservation == "reservation"){
				$booking_calendar .= "<tr>";
			}
			if( $reservation == "reservation") {
				$booking_calendar .= $this->reserv_calendar_cell(__( $this->prev_month, 'booking-calendar' ) . " " . $day, $cell_class,$date);
			} else {
				$booking_calendar .= $this->booking_calendar_cell(__( $this->prev_month, 'booking-calendar' ) . " " . $day, $cell_class,$date);
			}
			
			if(($r%7 == 0 && $r != 0) && $reservation == "reservation"){
				$booking_calendar .= "</tr><tr>";
			}
			$r++;
			$empty_cells ++;
		}

		/* days */
		$row_count    = $empty_cells;
		$weeknumadjust = $count_in_row - ($this->month_start - $day_start);

		for ($j = 1; $j <= $this->month_days_count; $j ++) {

			$date = $this->year . '-' . $this->month . '-' . $j;
			$row_count ++;
			if($r == 0 && $j == 1 && $reservation == "reservation"){
				$booking_calendar .= "<tr>";
			}
			if( $reservation == "reservation") {
				$booking_calendar .= $this->reserv_calendar_cell($j, 'current-month-day', $date);
			} else {
				$booking_calendar .= $this->booking_calendar_cell($j, 'current-month-day', $date);
			}
			if((($r + $j)%7 == 0 ) && $reservation == "reservation"){
				$booking_calendar .= "</tr><tr>";
			}
			if ($row_count % $count_in_row == 0) {
				$row_count = 0;
			}
		}

		/* next month cells */
		$cells_left_count = $count_in_row - $row_count;
		if ($cells_left_count != $count_in_row) {
			for ($k = 1; $k <= $cells_left_count; $k ++) {
				$day_count = ($k==1) ? "day" : "days";
				$day = date("j",strtotime("".($this->year . '-' . ($this->month) . '-'.$this->month_days_count.'')." +".$k." ".$day_count.""));
				if($this->month == 12) {
					$month = 0;
				} else {
					$month = $this->month;
				}
				if($month == 0) {
					$date = ($this->year + 1) . '-' . ($month+1) . '-' . $day;
				} else {
					$date = $this->year . '-' . ($month+1) . '-' . $day;
				}
				
				if( $reservation == "reservation") {
					$booking_calendar .= $this->reserv_calendar_cell(__( $this->next_month, 'booking-calendar' ) . " " . $k, 'next-month-day',$date);
				} else {
					$booking_calendar .= $this->booking_calendar_cell(__( $this->next_month, 'booking-calendar' ) . " " . $k, 'next-month-day',$date);
				}
				if(($k%7 == 0) && $reservation == "reservation" && $k != $count_in_row){
					$booking_calendar .= "</tr><tr>";
				} elseif(($k%7 == 0) && $reservation == "reservation" && $k == $count_in_row) {
					$booking_calendar .= "</tr>";
				}
				$empty_cells ++;
			}
		}
		if( $reservation == "") {
			$style = (!is_null($this->res)) ? "style='display:block'" : "";	
			$booking_calendar .= '</div><div class="wpdevart-hours-container" ' . $style . ' ><div class="wpdevart-hours-overlay"><div class="wpdevart-load-image"><i class="fa fa-spinner fa-spin"></i></div></div><div class="wpdevart-hours">';
			if(!is_null($this->res)){
				$booking_calendar .= $this->booking_calendar_hours($this->res["single_day"]);
			}
			$booking_calendar .= '</div></div>';
		} else {
			$booking_calendar .= '</table>';
		}
		if($reservation != "reservation") {
			if (isset($this->theme_option['legend_enable']) && $this->theme_option['legend_enable'] == "on") {
				$booking_calendar .= '<div class="wpdevart-booking-legends div-for-clear">';
					if (isset($this->theme_option['legend_available_enable']) && $this->theme_option['legend_available_enable'] == "on") {
						$booking_calendar .= '<div class="wpdevart-legends-available"><div class="legend-text"><span class="legend-div"></span>-'.((!isset($this->theme_option['use_mo'])) ? esc_html($this->theme_option['legend_available']) : $this->for_tr["for_available"]).'</div>';
						$booking_calendar .= '</div>';
					}
					if (isset($this->theme_option['legend_booked_enable']) && $this->theme_option['legend_booked_enable'] == "on") {
						$booking_calendar .= '<div class="wpdevart-legends-pending"><div class="legend-text"><span class="legend-div"></span>-'.((!isset($this->theme_option['use_mo'])) ? esc_html($this->theme_option['legend_booked']) : $this->for_tr["for_booked"]).'</div>';
						$booking_calendar .= '</div>';
					}
					if (isset($this->theme_option['legend_unavailable_enable']) && $this->theme_option['legend_unavailable_enable'] == "on") {
						$booking_calendar .= '<div class="wpdevart-legends-unavailable"><div class="legend-text"><span class="legend-div"></span>-'.((!isset($this->theme_option['use_mo'])) ? esc_html($this->theme_option['legend_unavailable']) : $this->for_tr["for_unavailable"]).'</div>';
						$booking_calendar .= '</div>';
					}
				$booking_calendar .= '</div>';
			}	
		} else {
			$booking_calendar .= '<div class="wpdevart-booking-legends div-for-clear">';
			$booking_calendar .= '<div class="wpdevart-legends-approved"><div class="legend-text"><span class="legend-div"></span>-Approved</div>';
			$booking_calendar .= '</div>';
			$booking_calendar .= '<div class="wpdevart-legends-pending"><div class="legend-text"><span class="legend-div"></span>-Pending</div>';
			$booking_calendar .= '</div>';
			$booking_calendar .= '<div class="wpdevart-legends-canceled"><div class="legend-text"><span class="legend-div"></span>-Canceled</div>';
			$booking_calendar .= '</div>';
			$booking_calendar .= '<div class="wpdevart-legends-rejected"><div class="legend-text"><span class="legend-div"></span>-Rejected</div>';
			$booking_calendar .= '</div>';
			$booking_calendar .= '</div>';
		}

		if(isset($this->theme_option['cal_animation_type']) && $this->theme_option['cal_animation_type']!='none' && !is_admin()){
			$booking_calendar.='<script>	
			jQuery(document).ready(function(){
				calendar_animat("'.self::get_animations_type_array($this->theme_option['cal_animation_type']).'","booking_calendar_main_container_'.$this->booking_id.'");
				jQuery(window).scroll(function(){
					calendar_animat("'.self::get_animations_type_array($this->theme_option['cal_animation_type']).'","booking_calendar_main_container_'.$this->booking_id.'");
				});
			});</script>';
		}
		return $booking_calendar;
	}

    /*############  hours booking function ################*/	
	
	public function booking_calendar_hours($day) {
		$hours = "<div class='wpdevart-hours'>";
		$unique_id = $this->id."_".$day;
		$day_info = json_decode($this->get_date_data( $unique_id ),true);
		if (isset($day_info["hours"]) && count($day_info["hours"])) {
			/*for selected multihour here*/
			$start = 0;
			$count = 0;	
			$i = 0;		
			$pos = 0;			
			foreach($day_info["hours"] as $key => $hour) {
				if($key == $this->res["start_hour"]) {
					$start = 1;
				} 
				if($start == 1) {
					$count += 1;
				}
				if($key == $this->res["end_hour"]) {
					$start = 0;
				}
			}
			foreach($day_info["hours"] as $key => $hour) {
				$i++;
				$hour_price = "";
				$hour_info = 'data-date="' . $key . '" data-dateformat="' . $key . '" data-currency="' . $this->currency . '"';
				if (isset($hour["price"]) && $hour["price"] != "" && isset($hour["status"]) && $hour["status"] != "unavailable") {
					$hour_price.= ' data-price="' . $hour["price"] . '"';
				}
				
				$hour_price.= ' data-currency="' . $this->currency . '"';
				if (isset($hour["status"]) && $hour["status"] == "available") {
					$hour_info .= ' data-available="' . $hour["available"] . '"';
				}else if(isset($hour["status"]) && $hour["status"] == "unavailable"){
					$hour_info .= ' data-available="0"';
				}
				$class_list = ' wpdevart-hour-' . $hour['status'];
				/*for edit selected $this->res*/
				if(isset($this->res["start_hour"]) && $key == $this->res["start_hour"]){
					$pos = $i;
				}
				if($this->res["end_hour"] == "") {
					if($key == $this->res["start_hour"])
						$class_list .= " hour_selected";
				}
				else{
					if($pos && $i < ($pos + $count))
						$class_list .= " hour_selected";
				}
				
				$hours .= "<div ".$hour_info." class='wpdevart-hour-item ".$class_list."'>
				  <div class='wpdevart-hour'><span>".$key."</span></div>";
				if (isset($hour["status"]) && $hour["status"] == "available") {
					if (!(isset($this->theme_option["hide_count_available"]) && $this->theme_option["hide_count_available"] == "on")) {
						$available = $hour["available"];
					}else {
						$available = "";
					}
					$hours .= '<div class="day-availability">' . $available . ' <span class="hour-av">'.$this->for_tr["for_available"].'</span></div>';
					
				} elseif (isset($hour["status"]) && $hour["status"] == "booked") {
					$hours .= '<div class="day-availability">' .$this->for_tr["for_booked"]. '</div>';
				} elseif (isset($hour["status"]) && $hour["status"] == "unavailable") {
					$hours .= '<div class="day-availability">' .$this->for_tr["for_unavailable"]. '</div>';
				}
				if(isset($hour["info_users"]) && $hour["info_users"] != "") {  
				    $hours .= "<div class='wpdevart-hour-info'>".$hour["info_users"]."</div>";
				} 
				if(((isset($hour["price"]) && $hour["price"] != "") || (isset($hour["marked_price"]) && $hour["marked_price"] != "")) && (isset($hour["status"]) && $hour["status"] != "unavailable")) {
				    $hours .= "<div class='wpdevart-hour-price'>";
					if(isset($hour["price"]) && $hour["price"] != "") {
						$hours .= "<span ".$hour_price." class='hour-price new-price'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? $this->currency : "").$hour["price"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? $this->currency : "")." </span>";
					}
					if(isset($hour["marked_price"]) && $hour["marked_price"] != "") {
						$hours .= "<span class='hour-marked-price old-price'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? $this->currency : "").$hour["marked_price"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? $this->currency : "")."</span>";
					}
					$hours .= "</div>";
				}
				$hours .= "</div>";
			}
			
		}else {
			$hours .= $this->for_tr["for_no_hour"];
		}
		$hours .= "</div>";
		return $hours;
	}

	public function booking_calendar_day_hours($day) {
		$hours = "";
		$unique_id = $this->id."_".$day;
		$day_info = json_decode($this->get_date_data( $unique_id ),true);
		if (isset($day_info["hours"]) && count($day_info["hours"])) {
			foreach($day_info["hours"] as $key => $hour) {
				$hour_price = "";
				if (isset($hour["price"]) && $hour["price"] != "" && isset($hour["status"]) && $hour["status"] != "unavailable") {
					$hour_price.= ' data-price="' . $hour["price"] . '"';
				}
				
				$hour_price.= ' data-currency="' . $this->currency . '"';
				$class_list = ' wpdevart-hour-' . $hour['status'];
				$hours .= "<div class='wpdevart-day-hour-item ".$class_list."'>
				  <div class='wpdevart-hour'><span>".$key."</span></div>";
				if (isset($hour["status"]) && $hour["status"] == "available") {
					if (!(isset($this->theme_option["hide_count_available"]) && $this->theme_option["hide_count_available"] == "on")) {
						$available = $hour["available"];
					}else {
						$available = "";
					}
					$hours .= '<div class="day-availability">' . $available . ' <span class="hour-av">'.$this->for_tr["for_available"].'</span></div>';
					
				} elseif (isset($hour["status"]) && $hour["status"] == "booked") {
					$hours .= '<div class="day-availability">' .$this->for_tr["for_booked"]. '</div>';
				} elseif (isset($hour["status"]) && $hour["status"] == "unavailable") {
					$hours .= '<div class="day-availability">' .$this->for_tr["for_unavailable"] . '</div>';
				}
				if(((isset($hour["price"]) && $hour["price"] != "") || (isset($hour["marked_price"]) && $hour["marked_price"] != "")) && (isset($hour["status"]) && $hour["status"] != "unavailable")) {
				    $hours .= "<div class='wpdevart-hour-price'>";
					if(isset($hour["price"]) && $hour["price"] != "") {
						$hours .= "<span ".$hour_price." class='hour-price new-price'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? $this->currency : "").$hour["price"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? $this->currency : "")." </span>";
					}
					if(isset($hour["marked_price"]) && $hour["marked_price"] != "") {
						$hours .= "<span class='hour-marked-price old-price'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? $this->currency : "").$hour["marked_price"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? $this->currency : "")."</span>";
					}
					$hours .= "</div>";
				}
				if(isset($hour["info_users"]) && $hour["info_users"] != "") {  
				    $hours .= "<div class='wpdevart-hour-info'>".$hour["info_users"]."</div>";
				}
				$hours .= "</div>";
			}
			
		}else {
			$hours .= $this->for_tr["for_no_hour"];
		}
		return $hours;
	}

    /*############  Cell function ################*/
	
	private function booking_calendar_cell( $day, $class, $date = '' ) {
		$class_list = '';
		$data_info = '';
		$data_available = '';
		$day_info = '';
		$hours = array();
		$hours_enabled = false;
		$av_count = 0;
		if($this->theme_option['date_format'] == "d/m/Y"){
			$date_format = date('m/d/Y',strtotime($date));
		} elseif($this->theme_option['date_format'] == 'Y M j'){
			$date_format = __(date('F',strtotime($date)),"booking-calendar");
			$date_format .= date(' j, Y',strtotime($date));
		} else{
			if($this->theme_option['date_format'] == 'F j, Y'){
				$date_format = __(date('F',strtotime($date)),"booking-calendar");
				$date_format .= date(' j, Y',strtotime($date));
			} else{
				$date_format = date($this->theme_option['date_format'],strtotime($date));
			}
		}
		if($date != "") {
			$date = date("Y-m-d",strtotime($date));
		}
		if (strpos( $class, 'week-day-name') === false ) {
			$class_list .= ' wpdevart-day';
		}
		
		$data_info = 'data-date="' . $date . '" data-dateformat="' . $date_format . '" data-currency="' . $this->currency . '"';
		foreach($this->calendar_data as $day_data) {
			if($day_data['day'] == $date) {
				$day_info = json_decode($day_data['data'], true);
			}
		}
		$week_day = date('w', strtotime( $date ));

		if (isset($day_info["status"]) && $day_info["status"] == "available") {
			if(!(!isset($this->theme_option['hours_enabled']) && isset($day_info['hours']) && trim($day_info['hours'] != ""))){
			$data_available = ' data-available="' . $day_info["available"] . '"';
			}
		}
		if(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days'])){
			$data_available = ' data-available="0"';
		}
		if(isset($day_info['status']) && $day_info['status'] != ''){
			if(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days'])){
				$day_info['status']='unavailable';
			}
			
			if($day_info['status'] == 'available') {
				if(!(!isset($this->theme_option['hours_enabled']) && isset($day_info['hours']) && trim($day_info['hours'] != ""))){
					$class_list .= ' wpdevart-available';
				}
			} else {
				$class_list .= ' wpdevart-' . $day_info['status'];
			}
		}
		
		if ($day != '') {
			$date_diff = $this->get_date_diff($date,date( 'Y-m-d' ));
			if (strpos( $class, 'week-day-name') === false ) {
				if ($date_diff<0 && ($date != '' || strpos( $class, 'past-month-day') !== false )) {
					$class_list .= ' past-day';
				}
				if ($date == date( 'Y-m-d' )) {
					$class_list .= ' current-day';
				}
				if (in_array($this->get_day( $date ), array('Saturday', 'Sunday'))) {
					$class_list .= ' weekend';
				}
				$day_start = (isset($this->theme_option["day_start"])? $this->theme_option["day_start"] : 0);
				if ($this->get_day( $date, 0 ) == $day_start) {
					$class_list .= ' week-start';
				}
				if (isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days'])) {
					$class_list .= ' wpdevart-unavailable'; // day with bookings
				} else if (strpos( $class, 'week-day-name' ) === false) {
					$class_list .= ' available-day'; // no bookings
				}
				if (isset($day_info["hours_enabled"]) && $day_info["hours_enabled"] == "on") {
					$hours_enabled = true;
					$class_list .= ' hour-enable'; // hour enable
					if (isset($day_info["hours"]) && $day_info["hours"] != "") {
						foreach($day_info["hours"] as $hour) {
							if($hour["status"] == "available") {
								$av_count += $hour["available"];
							}
						}
					}
				}
				if ((isset($this->selected["date"]) && $this->selected["date"] == $date && $this->selected["date"] != "") || (count($this->res_dates) && in_array( $date, $this->res_dates))) {
					$class_list .= ' selected';
				}
			}
			$bookings = '<div ' . $data_info . ' ' . $data_available . ' class="' . $class . $class_list . '">';
			$bookings.= '<div class="wpda-day-header div-for-clear"><div class="wpda-day-number">' . $day . '</div>';
			if (isset($day_info["info_admin"]) && $day_info["info_admin"] != "" && is_admin() && !$this->ajax) {
				$bookings .= '<div class="day-user-info-container">a<div class="day-user-info">' . esc_html($day_info["info_admin"]) . '</div></div>';
			}
			if (isset($day_info["info_users"]) && $day_info["info_users"] != "") {
				$bookings .= '<div class="day-user-info-container">i<div class="day-user-info animated fadeInDownShort">' . esc_html($day_info["info_users"]) . '</div></div>';
			}
			
			$bookings.= '</div>';
			if(strpos( $class, 'week-day-name') === false){
				if (isset($day_info["status"]) && $day_info["status"] == "available") {
					if (!(isset($this->theme_option["hide_count_available"]) && $this->theme_option["hide_count_available"] == "on")) {
						$available = $day_info["available"];
						if($hours_enabled){
							$available = $av_count;
						}
					} else {
						$available = "";
					}
					if(!(!isset($this->theme_option['hours_enabled']) && isset($day_info['hours']) && trim($day_info['hours'] != ""))){
						$bookings .= '<div class="day-availability">' . $available . ' <span class="day-av">'.$this->for_tr["for_available"].'</span></div>';
					}
					
				} elseif (isset($day_info["status"]) && $day_info["status"] == "booked") {
					$bookings .= '<div class="day-availability">' .$this->for_tr["for_booked"]. '</div>';
				} elseif ((isset($day_info["status"]) && $day_info["status"] == "unavailable") || (isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					$bookings .= '<div class="day-availability">' .$this->for_tr["for_unavailable"] . '</div>';
				}
				if (isset($day_info["price"]) && $day_info["price"] != "" && !(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					if(!$hours_enabled){
						$bookings .= '<div class="day-price"><span class="new-price" data-price="' . $day_info["price"] . '" data-currency="' . $this->currency . '">' .   ((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($this->currency) : '') . esc_html($day_info["price"]) . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($this->currency) : '') . '</span>';
						if (isset($day_info["marked_price"]) && $day_info["marked_price"] != "") {
							$bookings .= '<span class="old-price">' . ((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($this->currency) : '') . esc_html($day_info["marked_price"]) . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($this->currency) : '') . '</span>';
						}
						$bookings .= '</div>';
					}
				}
				if((isset($this->theme_option['hours_enabled']) && $this->theme_option['hours_enabled'] == "on") && (isset($this->theme_option['show_hours_info']) && $this->theme_option['show_hours_info'] == "on")){
					$bookings .= '<div class="wpdevart-day-hours animated fadeInUpShort">';
					$bookings .= $this->booking_calendar_day_hours(date("Y-m-d",strtotime($date)));
					
					$bookings .= '</div>';
				}
			}
			$bookings .= '</div>';

			return $bookings;
		}
    }

    /*############  calendar cells function ################*/
	
	private function reserv_calendar_cell( $day, $class, $date = '' ) {
		$countries = self::get_countries();
		$date = date("Y-m-d",strtotime($date));	
		$class = "";	
		$link_content = "";	
		$hide_price = (isset($this->theme_option['hide_price']) && $this->theme_option['hide_price'] == "on") ? true : false;
		$reservations = $this->get_reservation_row_calid($this->id,$date);
		if ($day != '') {
			$bookings = '<td class="' . $class . '">';
			$bookings.= '<div class="wpda-day-header div-for-clear"><div class="wpda-day-number">' . $day . '</div></div>';
				if($reservations) {
					foreach($reservations as $reservation) {
						$hour_html = "";
						$unique_id = $reservation["calendar_id"]."_".$reservation["single_day"];
						$day_hours = $this->get_date_data( $unique_id );
						$day_hours = json_decode($day_hours, true);
						$form_data = $this->get_form_data($reservation["form"]);
						$extras_data = $this->get_extra_data($reservation);
						if($reservation["check_in"] == $date) {
							$class = "start";
						} elseif($reservation["check_out"] == $date) {
							$class = "end";
						}
						$bookings .= '<div class="reservation-month reservation-month-'.$reservation["id"].' '.$reservation["status"].' '.$class.'">';
						if(($reservation["check_in"] == $date && $reservation["email"] == "") || ($reservation["single_day"] == $date && $reservation["email"] == "")) {
							$link_content = $reservation["id"];
						} elseif(($reservation["check_in"] == $date && $reservation["email"] != "") || ($reservation["single_day"] == $date && $reservation["email"] != "")) {
							$link_content = $reservation["email"];
						}elseif($reservation["check_in"] != $date) {
							$link_content = "";
						}
						if(isset($reservation["start_hour"]) && $reservation["start_hour"] != ""){
							$hour_html = $reservation["start_hour"];
						}
						if(isset($reservation["end_hour"]) && $reservation["end_hour"] != ""){
							$hour_html = $hour_html." - ".$reservation["end_hour"];
						}
						if($hour_html != ""){
							$hour_html = '<span class="form_info"><span class="form_label">'.__('Hour','booking-calendar').'</span> <span class="form_value">'.$hour_html.'</span></span>';
						}
						
						$content = '<div class="month-view-content"><div class="reserv-info-container">
									<h5>Details<span class="month_view_id">#'.$reservation["id"].'</span></h5>
									'.$hour_html.'<span class="form_info"><span class="form_label">'.__('Item Count','booking-calendar').'</span> <span class="form_value">'.$reservation["count_item"].'</span></span>';
							if(!$hide_price)	{	
									$content .= '<span class="form_info"><span class="form_label">'.__('Price','booking-calendar').'</span> <span class="form_value">'.((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reservation["currency"]) : '') . esc_html($reservation["price"]) . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reservation["currency"]) : '').'</span></span>
									<span class="form_info"><span class="form_label">'.__('Total Price','booking-calendar').'</span> <span class="form_value">'.((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reservation["currency"]) : '') . esc_html($reservation["total_price"]) . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reservation["currency"]) : '').'</span></span>';
							}
							$content .= '</div><div class="reserv-info-items div-for-clear">';
						/*Hours info*/
						
						if(isset($day_hours["hours"]) && count($day_hours["hours"])){
							$content .= "<div class='reserv-info-container hours_info'>
								<h5>".__('Hours','booking-calendar')."</h5>";
								$start = 0;
								$count = 0;
								foreach($day_hours["hours"] as $key => $hour) {
									if($key == $reservation["start_hour"]) {
										$start = 1;
									} 
									if($start == 1 && (!($reservation["end_hour"] == "" && $count == 1))) {
										$content .= "<span class='form_info'><span class='form_label'>".$key."</span> <span class='form_value'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? $reservation["currency"] : '').$hour["price"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? $reservation["currency"] : '')."<span class='hour-info'>".$hour["info_users"]."</span></span></span>";
									    $count += 1;
									}
									if($key == $reservation["end_hour"]){ 
										$start = 0;
									}
								}
							$content .= "</div>";
						} 		
						if(count($form_data)) {
							$content .= "<div class='reserv-info-container'>";
							$content .= "<h5>".__('Contact Information','booking-calendar')."</h5>";
							foreach($form_data as $form_fild_data) {
								if($form_fild_data['type'] == 'countries' && trim($form_fild_data['value']) != "") {
									$content .= "<span class='form_info'><span class='form_label'>". $form_fild_data["label"] ."</span> <span class='form_value'>". $countries[$form_fild_data["value"]] ."</span></span>";
								} else {
									$value = $form_fild_data["value"];
									if(strpos($form_fild_data["value"], "|wpdev|") !== false){
										$value = explode("|wpdev|",$form_fild_data["value"]);
										$value = implode(", ",$value);
									}
									$content .= "<span class='form_info'><span class='form_label'>". $form_fild_data["label"] ."</span> <span class='form_value'>". $value ."</span></span>";
								}
							}
							$content .= "</div>";
						}
						if(count($extras_data)) {
							$content .= "<div class='reserv-info-container'>";
							$content .= "<h5>".__('Extra Information','booking-calendar')."</h5>";
							foreach($extras_data as $extra_data) {
								$content .= "<h6>".$extra_data["group_label"]."</h6>";
								$content .= "<span class='form_info'><span class='form_label'>". $extra_data["label"] ."</span>"; 
								$content .= "<span class='form_value'>";
								if($extra_data["price_type"] == "percent") {
									$content .= "<span class='price-percent'>".$extra_data["operation"].$extra_data["price_percent"]."%</span>";
									if(isset($extra_data["price"])) {
										$content .= "<span class='price'>".$extra_data["operation"] .((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reservation["currency"]) : '') . $extra_data["price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reservation["currency"]) : '') ."</span>";
									}
								}else {
									if(isset($extra_data["price"])) {
										$content .= "<span class='price'>".$extra_data["operation"] .((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reservation["currency"]) : '') . $extra_data["price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reservation["currency"]) : '') ."</span>";
									}
								}
								$content .= "</span></span>";
							}
							$content .= "<h6>".__('Price change','booking-calendar')."</h6>";
							$content .= "<span class='form_info'><span class='form_label'></span><span class='form_value'>".(($reservation["extras_price"]<0)? "" : "+").((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reservation["currency"]) : '') . $reservation["extras_price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reservation["currency"]) : '')."</span>"; 
							$content .= "</div>";
						}		
						$content .= '</div></div>';
						$bookings .= '<a href="" onclick="wpdevart_set_value(\'cur_id\',\''.$reservation["id"].'\');wpdevart_set_value(\'task\',\'display_reservations\'); wpdevart_form_submit(event, \'reservations_form\')" class="month-view-link">'.$link_content.'</a>';
						$bookings .= $content.'</div>';
					}
				}
			$bookings .= '</td>';
			return $bookings;
		}
    }

	
	public function booking_form($class) {
		
		$input_atribute = '';
		$form_html = '';		
		$forms = '';		
		$form_html .= '<div class="wpdevart-booking-form-container '.$class.'" id="wpdevart_booking_form_'.$this->booking_id.'">';
		if (!isset($this->theme_option["auto_fill"])) {
			$input_atribute = "autocomplete='off'";
		}
		$form_html .= '<div class="wpdevart-booking-form"><form method="post" class="div-for-clear" enctype="multipart/form-data"><div class="wpdevart-check-section">';
		if (isset($this->theme_option["enable_checkinout"]) && $this->theme_option["enable_checkinout"] == "on" && $this->theme_option["type_days_selection"] == "multiple_days" && !(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on")) {
			$form_html .= '<div class="wpdevart-fild-item-container ">
				  '.$this->form_field_text(array('name'=>'form_checkin'.$this->booking_id,'class'=>'wpdevart_form_checkin','label'=>$this->for_tr["for_check_in"], 'readonly' => 'true',"value"=>((isset($this->res["check_in"]) && $this->res["check_in"] != "")? date($this->theme_option['date_format'],strtotime($this->res["check_in"])) : "") )).'</div>
				  <div class="wpdevart-fild-item-container ">'.$this->form_field_text(array('name'=>'form_checkout'.$this->booking_id,'class'=>'wpdevart_form_checkout','label'=>$this->for_tr["for_check_out"], 'readonly' => 'true',"value"=>((isset($this->res["check_out"]) && $this->res["check_out"] != "")? date($this->theme_option['date_format'],strtotime($this->res["check_out"])) : "") )).'</div>';
		} elseif (!isset($this->theme_option["enable_checkinout"]) && $this->theme_option["type_days_selection"] == "multiple_days" && !(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on")) {
			$form_html .= '<input type="hidden" id="wpdevart_form_checkin'.$this->booking_id.'" name="wpdevart_form_checkin'.$this->booking_id.'" '.(isset($this->res["check_in"])? "value='".$this->res["check_in"]."'" : "").'><label class="wpdevart_form_checkin wpdevart_none">'.$this->for_tr["for_check_in"].'</label><input type="hidden" id="wpdevart_form_checkout'.$this->booking_id.'" name="wpdevart_form_checkout'.$this->booking_id.'" '.(isset($this->res["check_out"])? "value='".$this->res["check_out"]."'" : "").'><label class="wpdevart_form_checkout wpdevart_none">'.$this->for_tr["for_check_out"].'</label>';
		}  elseif ($this->theme_option["type_days_selection"] == "single_day" || (isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on")) {
			$form_html .= '<input type="hidden" id="wpdevart_single_day'.$this->booking_id.'" name="wpdevart_single_day'.$this->booking_id.'" '.(isset($this->res["single_day"])? "value='".$this->res["single_day"]."'" : "").'>';
			if(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on" && (isset($this->theme_option["type_hours_selection"]) && $this->theme_option["type_hours_selection"] == "multiple_hours")) {
				$form_html .= '<input type="hidden" id="wpdevart_start_hour'.$this->booking_id.'" name="wpdevart_start_hour'.$this->booking_id.'" '.(isset($this->res["start_hour"])? "value='".$this->res["start_hour"]."'" : "").'><label class="wpdevart_form_checkin wpdevart_none">'.$this->for_tr["for_start_hour"].'</label><input type="hidden" id="wpdevart_end_hour'.$this->booking_id.'" name="wpdevart_end_hour'.$this->booking_id.'" '.(isset($this->res["end_hour"])? "value='".$this->res["end_hour"]."'" : "").'><label class="wpdevart_form_checkout wpdevart_none">'.$this->for_tr["for_end_hour"].'</label>';
			} elseif(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on" && (isset($this->theme_option["type_hours_selection"]) && $this->theme_option["type_hours_selection"] == "single_hour")) {
				$form_html .= '<input type="hidden" id="wpdevart_form_hour'.$this->booking_id.'" name="wpdevart_form_hour'.$this->booking_id.'" '.(isset($this->res["start_hour"])? "value='".$this->res["start_hour"]."'" : "").'>';
			}
		}
		if (isset($this->theme_option["enable_number_items"]) && $this->theme_option["enable_number_items"] == "on") {
			$count_avab = array();
			$count = 0;
			if(!is_null($this->res)){
				if(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on"){
					$count_avab = $this->get_hours_count($this->res["single_day"],$this->res["start_hour"],$this->res["end_hour"]);
				} else {
					$count_avab = $this->get_interval_dates(($this->res["check_in"] == ""? $this->res["single_day"] : $this->res["check_in"]),$this->res["check_out"]);
				}
				$count = $count_avab["min"];
			}
			$form_html .= $this->form_field_select(array('options'=>'','name'=>'count_item'.$this->booking_id,'class'=>'wpdevart_count_item','label'=>$this->for_tr["for_item_count"],"onchange"=>"change_count(this,".$this->booking_id.",'".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before")? "before" : "after")."','".($this->currency)."')", "value"=>(isset($this->res["count_item"])? $this->res["count_item"] : 0),"count" =>$count));
		}
		if(isset($this->extra_field)) {
			$extra_fields = json_decode( $this->extra_field->data, true );
			$extra_title = $this->extra_field->title;
			$form_html .= '<div class="wpdevart-extras">';
			if (isset($this->theme_option["enable_extras_title"]) && $this->theme_option["enable_extras_title"] == "on") {
				$form_html .= '<h4 class="form_title">'.esc_html($extra_title).'</h4>';
			}
			if(!is_null($this->res)){
				$extras = json_decode( $this->res["extras"], true );
			}
			foreach($extra_fields as $key=>$extra_field) {
				$form_html .= $this->extra_field($extra_field,((isset($extras) && isset($extras[$key]) && isset($extras[$key]["name"]))?  $extras[$key]["name"] : ""));
			}
			$form_html .= '</div>';	
		}
		$form_html .= '</div>';
		/*FORM SECTION*/
		if(isset($this->form_data)) {
			$form_data = json_decode( $this->form_data->data, true );
			$form_title = $this->form_data->title;
			$form_html .= '<div class="wpdevart-form-section"><div class="wpdevart-reserv-info"><h4 class="form_title">'.$this->for_tr["for_reservation"].'</h4>';
			if(isset($this->res)){
				$form_html .= '<div id="check-info-'.$this->booking_id.'" class="check-info " data-content="'.$this->for_tr["for_select_days"].'">';
				if(isset($this->res["check_in"]) && $this->res["check_in"] != ""){
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_check_in"].'</span><span class="reserv_info_cell_value">'.date($this->theme_option["date_format"], strtotime($this->res["check_in"])).'</span></div><div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_check_out"].'</span><span class="reserv_info_cell_value">'.date($this->theme_option["date_format"], strtotime($this->res["check_out"])).'</span></div>';
				} elseif(isset($this->res["single_day"])) {
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_date"].'</span><span class="reserv_info_cell_value">'.date($this->theme_option["date_format"], strtotime($this->res["single_day"])).'</span></div>';
				}
				if(isset($this->res["start_hour"]) && $this->res["start_hour"] != "" && isset($this->res["end_hour"]) && $this->res["end_hour"] != ""){
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_start_hour"].'</span><span class="reserv_info_cell_value">'.$this->res["start_hour"].'</span></div><div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_end_hour"].'</span><span class="reserv_info_cell_value">'.$this->res["end_hour"].'</span></div>';
				} elseif(isset($this->res["start_hour"]) && $this->res["start_hour"] != "") {
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_hour"].'</span><span class="reserv_info_cell_value">'.$this->res["start_hour"].'</span></div>';
				}
				if(isset($this->res["count_item"]) && isset($this->theme_option['enable_number_items'])){
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_item_count"].'</span><span class="reserv_info_cell_value count_item">'.$this->res["count_item"].'</span></div>';
				}
				if(isset($this->res["price"])){
					if(isset($this->theme_option["hours_enabled"]) && $this->theme_option["hours_enabled"] == "on"){
						$price = $this->get_hours_count($this->res["single_day"],$this->res["start_hour"],$this->res["end_hour"]);
					} else {
						$price = $this->get_interval_dates(($this->res["check_in"] == ""? $this->res["single_day"] : $this->res["check_in"]),$this->res["check_out"]);
					}
					$price = $price["price"];
					$form_html .= '<div class="reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_price"].'</span><span class="reserv_info_cell_value price" data-price="'.$price.'"><span>'.$this->res["price"].'</span>'.$this->currency.'</span></div>';
				}
				if(isset($this->res["extras"]) && $this->res["extras"] != ""){
					$extras_data = json_decode($this->res["extras"], true);
					$i = 0;
					foreach($extras_data as $key=>$extras){
						$price_percent = $extras["price_percent"];
						if($extras["price_type"] == "percent") {
							$price_percent = ($this->res["price"] * $extras["price_percent"])/100;
						}
						$form_html .= '<div class="wpdevart-extra-info wpdevart-extra-'.$i.' reserv_info_row wpdevart_'.$key.'" data-id="' .$key. '"><span class="reserv_info_cell">'.$extra_fields[$key]["label"].'</span><span class="reserv_info_cell_value"><span class="option_label">'.$extras["label"].'</span><span class="extra_percent" style="'.($extras["price_type"] == "price"? "display:none;" : "").'">'.($extras["price_type"] == "percent"? $extras["price_percent"].'%': "").'</span><span class="extra_price" data-extraprice="'.($extras["price_percent"]/$this->res["count_item"]).'" data-extraop="'.$extras["operation"].'" style="'.($price_percent != 0 ? "display:inline-block;" : "display:none;").'"><span class="extra_price_value">'. ($price_percent != 0 ? $extras["operation"].$price_percent.$this->currency : "").'</span></span><input type="hidden" class="extra_price_value" value="'.($price_percent != 0 ? $extras["operation"].$price_percent : "").'"></span></div>';
						$i++;
					}
				}
				if(isset($this->res["total_price"])){
					if(isset($this->res["sale_percent"]) && !empty($this->res["sale_percent"])){
						if(isset($this->res["sale_type"]) && $this->res["sale_type"] == "percent"){
							$sale_percent = $this->res["sale_percent"] == 100 ? $this->res["sale_percent"] : ($this->res["total_price"] * 100) / (100 - $this->res["sale_percent"]);
							$form_html .= '<div class="wpdevart-total-price reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_total"].'</span><span class="reserv_info_cell_value total_price"><span class="start_total_price"><span>'.$sale_percent.'</span>'.$this->currency.'</span><span class="sale_total_price"><span class="sale_percent">-' . $this->res["sale_percent"] . '%</span><span><span>' . $this->res["total_price"] . '</span>'.$this->currency.'</span></span></span></div>';
						} else {
							$form_html .= '<div class="wpdevart-total-price reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_total"].'</span><span class="reserv_info_cell_value total_price"><span class="start_total_price"><span>'.($this->res["total_price"] + $this->res["sale_percent"]).'</span>'.$this->currency.'</span><span class="sale_total_price"><span class="sale_percent">-' . $this->res["sale_percent"] .$this->currency.'</span><span><span>' . $this->res["total_price"] . '</span>'.$this->currency.'</span></span></span></div>';
						}
						
					} else {
						$form_html .= '<div class="wpdevart-total-price reserv_info_row"><span class="reserv_info_cell">'.$this->for_tr["for_total"].'</span><span class="reserv_info_cell_value total_price"><span class="start_total_price"><span>'.$this->res["total_price"].'</span>'.$this->currency.'</span><span class="sale_total_price"></span></span></div>';
					}
				}
				$form_html .= '</div>';
			} else {
				$form_html .= '<div id="check-info-'.$this->booking_id.'" class="check-info " data-content="'.$this->for_tr["for_select_days"].'">'.$this->for_tr["for_select_days"].'</div>';
			}
			$form_html .= '</div>';
			if (isset($this->theme_option["enable_form_title"]) && $this->theme_option["enable_form_title"] == "on") {
				$form_html .= '<h4 class="form_title">'.esc_html($form_title).'</h4>';
			}
			if(!is_null($this->res)){
				$forms = json_decode( $this->res["form"], true );
			}
			foreach($form_data as $form_field) {
				if(isset($form_field['type'])) {
					if($forms && isset($forms["wpdevart_".$form_field['name']])){
						$form_field['value'] = $forms["wpdevart_".$form_field['name']];
					}
					$func_name = "form_field_" . $form_field['type'];
					if(method_exists($this,$func_name)) {
						$form_html .= $this->$func_name($form_field,$input_atribute);
					}
				}
			}
			if (isset($this->theme_option["enable_terms_cond"]) && $this->theme_option["enable_terms_cond"] == "on") {		  
				$form_html .= $this->form_field_checkbox(array('required'=>'on','name'=>'terms_cond'.$this->booking_id,'label'=>$this->for_tr["for_termscond"]),"","",$this->theme_option["terms_cond_link"]);
			}
			if(is_null($this->res)){
				$form_html .= '<button type="submit" class="wpdevart-submit"  id="wpdevart-submit'.$this->booking_id.'" name="wpdevart-submit'.$this->booking_id.'">'.$this->for_tr["for_submit_button"].'<i class="fa fa-spinner fa-spin"></i></button></div>';
			}
		}
		$form_html .= '<input type="hidden" class="wpdevart_extra_price_value" id="wpdevart_extra_price_value'.$this->booking_id.'" name="wpdevart_extra_price_value'.$this->booking_id.'" value="'.((!is_null($this->res) && $this->res["extras_price"] != "") ? $this->res["extras_price"] : "").'">';
		$form_html .= '<input type="hidden" class="wpdevart_total_price_value" id="wpdevart_total_price_value'.$this->booking_id.'" name="wpdevart_total_price_value'.$this->booking_id.'" value="'.((!is_null($this->res) && $this->res["total_price"] != "") ? $this->res["total_price"] : "").'">';
		$form_html .= '<input type="hidden" class="wpdevart_price_value" id="wpdevart_price_value'.$this->booking_id.'" name="wpdevart_price_value'.$this->booking_id.'" value="'.((!is_null($this->res) && $this->res["price"] != "") ? $this->res["price"] : "").'">';
		$form_html .= '<input type="hidden" class="wpdevart_sale_type" id="wpdevart_sale_type'.$this->booking_id.'" name="wpdevart_sale_type'.$this->booking_id.'" value="'.((!is_null($this->res) && $this->res["sale_type"] != "") ? $this->res["sale_type"] : "").'">';
		$form_html .= '<input type="hidden" name="id" value="'.$this->booking_id.'">';
		$form_html .= '<input type="hidden" name="task" value="save">';
		$payment_infos = array("billing","shipping");
		foreach($payment_infos as $payment_info){
			if(!is_null($this->res) && isset($this->res["address_".$payment_info]) && $this->res["address_".$payment_info] != "" && $this->res["address_".$payment_info] != "[]") {
				$address = $this->get_form_data_rows($this->theme_option[$payment_info."_address_form"]);
				$form_data = json_decode( $address->data, true );
				$form_title = $address->title;
				if (isset($this->theme_option["enable_form_title"]) && $this->theme_option["enable_form_title"] == "on") {
					$form_html .= '<h4 class="form_title">'.esc_html($form_title).'</h4>';
				}
				if(!is_null($this->res)){
					$forms = json_decode( $this->res["address_".$payment_info], true );
				}
				foreach($form_data as $form_field) {
					if(isset($form_field['type'])) {
						if($forms && isset($forms["wpdevart_".$payment_info."_info_".$form_field['name']])){
							$form_field['value'] = $forms["wpdevart_".$payment_info."_info_".$form_field['name']];
						}
						
						$func_name = "form_field_" . $form_field['type'];
						if(method_exists($this,$func_name)) {
							$form_html .= $this->$func_name($form_field,$input_atribute,$payment_info."_info_");
						}
					}
				}
			}
		}
		if(!is_null($this->res)){
			$form_html .= '<input type="hidden" name="reserv_id" value="'.$this->res["id"].'">';
			$form_html .= '<input type="hidden" name="reserv_status" value="'.$this->res["status"].'">';
			$form_html .= '<input type="hidden" name="task" value="update_reservations">';
			$form_html .= '<button type="submit" class="wpdevart-submit wpdevart-submit-update"  id="wpdevart-submit'.$this->booking_id.'" name="wpdevart-update'.$this->booking_id.'">'.$this->for_tr["for_submit_button"].'<i class="fa fa-spinner fa-spin"></i></button></div>';
		}
		$form_html .= '</form></div></div>';
		return $form_html;
	}
	
	private function form_field_text($form_field,$input_atribute='',$payment_info = ""){
		$input_class = array();
		$readonly = '';
		$required = '';
		if(isset($form_field['required'])) {
			$required .= '<span class="wpdevart-required">*</span>';
			$input_class[] = 'wpdevart-required';
		}		
		if(isset($form_field['isemail']) && $form_field['isemail'] == "on" ) {
			$input_class[] = 'wpdevart-email';
		}			
		if(isset($form_field['class']) && $form_field['class'] != "" ) {
			$input_class[] = $form_field['class'];
		}		
		if(isset($form_field['readonly']) && $form_field['readonly'] == "true" ) {
			$readonly = "readonly";
		}	
		if(count($input_class)) {
			$input_class = implode(" ",$input_class);
			$class = "class='".$input_class."'";
		} else {
			$class = "";
		}
		$field_html = '<div class="wpdevart-fild-item-container">
							<label for="wpdevart_'.$form_field['name'].'" '.$class.'>'.esc_html($form_field['label']).$required. '</label>';
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'">
				  <input type="text" id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'" '.$input_atribute.' '.$class.' ' .$readonly. ' '.(isset($form_field['value'])? "value='".$form_field['value']."'" : "").'>
			    </div>
		     </div>';
		return $field_html;
	}
	
	private function form_field_textarea($form_field,$input_atribute='',$payment_info = ""){
		$input_class = '';
		$field_html = '<div class="wpdevart-fild-item-container">
							<label for="wpdevart_'.$form_field['name'].'">'.esc_html($form_field['label']).'</label>';
		if(isset($form_field['required'])) {
			$field_html .= '<span class="wpdevart-required">*</span>';
			$input_class = 'class="wpdevart-required"';
		}		
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'">
				  <textarea id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'" '.$input_class.'>'.(isset($form_field['value'])? $form_field['value'] : "").'</textarea>
			    </div>
		     </div>';
		return $field_html;
	}
	
	private function form_field_select($form_field,$input_atribute='',$payment_info = ""){
		$select_options = array();
		$multi_options = array();
		if((isset($form_field["count"]) && $form_field["count"] != 0) || (!is_null($this->res) && isset($form_field["count"]))){
			if(is_null($this->res)){
				for($i=1; $i<=$form_field["count"];$i++) {
					$select_options[] = $i;
				}
			} else {
				$count = $form_field["count"];
				if($this->res['status'] == 'approved'){
					$count = $this->res["count_item"] + $form_field["count"];
				}
				for($i=1; $i<= $count; $i++) { 
					$select_options[] = $i;
				}
			}
		} else {
			$select_options = explode(PHP_EOL, $form_field['options']);
		}
		
		$input_class = '';
		$field_html = '';
		if(count($select_options)){
			$field_html .= '<div class="wpdevart-fild-item-container">
								<label for="wpdevart_'.$form_field['name'].'">'.esc_html($form_field['label']).'</label>';
			if(isset($form_field['required'])) {
				$field_html .= '<span class="wpdevart-required">*</span>';
				$input_class = 'wpdevart-required ';
			}	
			if(isset($form_field['class']) && $form_field['class'] != "" ) {
				$input_class .= $form_field['class'];
			}			
			$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'"><select id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'"';
			if(isset($form_field['multi'])) {
				$field_html .= 'multiple="multiple"';
				if(isset($form_field["value"]) && $form_field["value"]){
					$multi_options = explode("|wpdev|",$form_field["value"]);
				}
			}
			if(isset($form_field['onchange'])) {
				$field_html .= 'onchange="'.$form_field['onchange'].'"';
			}
			$field_html .= ' class="'.$input_class.'">';
			foreach($select_options as $select_option) {
				if(trim($select_option) != '') {
					$field_html .= '<option value="'.esc_html($select_option).'" '.(((isset($form_field["value"]) && $form_field["value"] == $select_option) || in_array($select_option,$multi_options))? "selected='selected'" : "").'>'.esc_html($select_option).'</option>';
				}
			}		  
			$field_html .= '</select>
					</div>
				 </div>';
		}
		else {
			$field_html .= 'No options';
		}		
		return $field_html;
	}
	
	private function form_field_countries($form_field,$input_atribute='',$payment_info = ""){
		$select_options = self::get_countries();
		$input_class = '';
		$field_html = '<div class="wpdevart-fild-item-container">
							<label for="wpdevart_'.$form_field['name'].'">'.esc_html($form_field['label']).'</label>';
		if(isset($form_field['required'])) {
			$field_html .= '<span class="wpdevart-required">*</span>';
			$input_class = 'wpdevart-required ';
		}	
		if(isset($form_field['class']) && $form_field['class'] != "" ) {
			$input_class .= $form_field['class'];
		}			
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'"><select id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'"';
		$field_html .= ' class="'.$input_class.'">';
		foreach($select_options as $code => $select_option) {
			$field_html .= '<option value="'.esc_html($code).'" '.((isset($form_field["value"]) && $form_field["value"] == $code)? "selected='selected'" : "").'>'.esc_html($select_option).'</option>';
		}		  
		$field_html .= '</select>
				</div>
			 </div>';		
		return $field_html;
	}
	
	private function form_field_recapthcha($form_field,$input_atribute='',$payment_info = ""){
		$site_key = isset($this->global_settings["recaptcha_public_key"]) ? $this->global_settings["recaptcha_public_key"] : "";
		$input_class = '';
		$field_html = '<div class="wpdevart-fild-item-container">
							<label for="wpdevart_'.$form_field['name'].'">'.esc_html($form_field['label']).'</label>';
		if(isset($form_field['required'])) {
			$field_html .= '<span class="wpdevart-required">*</span>';
			$input_class = 'wpdevart-required ';
		}	
		if(isset($form_field['class']) && $form_field['class'] != "" ) {
			$input_class .= $form_field['class'];
		}			
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'">';
		$field_html .= '<div class="g-recaptcha" data-sitekey="' . $site_key . '"></div>
				</div>
			 </div>';		
		return $field_html;
	}
	
	private function form_field_upload($form_field,$input_atribute='',$payment_info = ""){
		$input_class = array("wpdevart-file");
		$required = '';
		if(isset($form_field['required'])) {
			$required = '<span class="wpdevart-required">*</span>';
			$input_class[] = 'wpdevart-required';
		}				
		if(isset($form_field['class']) && $form_field['class'] != "" ) {
			$input_class[] = $form_field['class'];
		}	
		if(count($input_class)) {
			$input_class = implode(" ",$input_class);
			$class = "class='".$input_class."'";
		} else {
			$class = "";
		}
		$type = $form_field['extensions'] ? $form_field['extensions'] : "";
		$max_size = $form_field['max_size'] ? $form_field['max_size'] : "";
		$field_html = '<div class="wpdevart-fild-item-container">
							<label for="wpdevart_'.$form_field['name'].'" '.$class.'>'.esc_html($form_field['label']).$required. '</label>';
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'">
				  <input type="file" id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'" '.$input_atribute.' '.$class.' '.(isset($form_field['value'])? "value='".$form_field['value']."'" : "").' data-type="'. $type . '" data-size="'. $max_size . '">
			    </div>
		     </div>';
		return $field_html;
	}
	
	private function extra_field($extra_field,$value = ""){
		$select_options = $extra_field['items'];
		$input_class = '';
		$field_html = '';
		if(count($select_options)){
			$field_html .= '<div class="wpdevart-fild-item-container">
								<label for="wpdevart_'.$extra_field['name'].'">'.esc_html($extra_field['label']).'</label>';
			if(isset($extra_field['required'])) {
				$input_class = "wpdevart-required";
			}		
			if(isset($extra_field['independent']) && $extra_field['independent'] == "on") {
				$input_class .= " wpdevart-independent";
			}	
			if(isset($extra_field['independent_counts']) && $extra_field['independent_counts'] == "on") {
				$input_class .= " wpdevart-independent_counts";
			}
			$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$extra_field['name'].'"><select onchange="change_extra(this,\''.((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before")? "before" : "after").'\',\''.($this->currency).'\')" class="wpdevart_extras '.$input_class.'" id="wpdevart_'.$extra_field['name'].'" name="wpdevart_'.$extra_field['name'].'">';
			foreach($select_options as $select_option) {
				$field_html .= '<option value="'.$select_option["name"].'" data-operation="'.$select_option["operation"].'" data-type="'.$select_option["price_type"].'" data-price="'.$select_option["price_percent"].'" data-label="'.$select_option["label"].'" '.(($value != "" && $value == $select_option["name"])? "selected='selected'" : "").'>'.$select_option["label"].' '.(($select_option["price_percent"])? '('.$select_option["operation"].(((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before" && $select_option["price_type"] == "price") ? $this->currency : '') . $select_option["price_percent"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after" && $select_option["price_type"] == "price") || !isset($this->theme_option['currency_pos'])) ? $this->currency : '')).(($select_option["price_type"] == "price")? "" : "%").')' : '').'</option>';
			}		  
			$field_html .= '</select>
					</div>
				 </div>';
		}
		else {
			$field_html .= __('No options','booking-calendar');
		}		
		return $field_html;
	}
	
	private function form_field_checkbox($form_field,$input_atribute='',$payment_info="", $link=''){
		$input_class = '';
		$field_html = '';
		$field_html .= '<div class="wpdevart-fild-item-container">';
		if($link != "") {
			$field_html .= '<label for="wpdevart_'.$form_field['name'].'"><a href="'.esc_url($link).'" target="_blank">'.$form_field['label'].'</a></label>';
		} else {
			$field_html .= '<label for="wpdevart_'.$form_field['name'].'">'.esc_html($form_field['label']);
		}
		if(isset($form_field['required'])) {
			$field_html .= '<span class="wpdevart-required">*</span>';
			$input_class = 'class="wpdevart-required"';
		}		
		$field_html .= '</label>';
		$field_html .= '<div class="wpdevart-elem-container div-for-clear" id="wpdevart_wrap_'.$form_field['name'].'">
				  <input type="checkbox" id="wpdevart_'.$form_field['name'].'" name="wpdevart_'.$payment_info.$form_field['name'].'" '.$input_class.'  '.((isset($form_field["value"]) && $form_field["value"] == "on")? "checked='checked'" : "").'>
			    </div>
		     </div>';
		return $field_html;
	}
	
			
	private function get_day($date, $type = 1) {
		$date      = date('l', strtotime( $date ));
		return $date;
	}
	
	private function get_date_diff($date1, $date2) {
		$start = strtotime($date1);
		$end = strtotime($date2);
		$datediff = $start - $end;
		return floor($datediff/(60*60*24));
	}

	private function search_in_array($needle, $haystack) {
		$array_iterator = new RecursiveArrayIterator( $haystack );
		$iterator       = new RecursiveIteratorIterator( $array_iterator );
		while ($iterator->valid()) {
			if (( $iterator->current() == $needle )) {
				return $array_iterator->key();
			}
			$iterator->next();
		}
		return false;
	}
	
	
	private function calculate_date( $start_date, $action, $type ) {
		$date    = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( $start_date ) ) . " " . $action . " " . $type ));
		$date    = explode('-', $date);
		$new_date = array(
			'year'  => $date[0],
			'month' => $date[1],
			'day'   => $date[2]
		);
		return $new_date;
	}

	private function get_month_name( $date, $type = 1 ) {
		$name       = date('M', strtotime( $date ));
		return $name ;
	}
	
	
	public function save_reserv($data,$submit,$type = ""){
		global $wpdb;
		$item_count = 0;
		$save = false;
		$reserv_info = array();
		$send_mail = array();
		$emails = "";
		$email_array = array();
		$billing_form = array();
		$shipping_form = array();
		$resstatus = wpdevart_bc_Library::getData($data, 'reserv_status', 'text', '');
		$resid = wpdevart_bc_Library::getData($data, 'reserv_id', 'text', 0);
		if(isset($this->theme_option['enable_instant_approval']) && $this->theme_option['enable_instant_approval'] == "on") {
			$status = "approved";
		} else {
			$status = "pending";
		}
		if(isset($resid) && $resid != ""){
			$status = $resstatus;
		}
		$form = array();
		$extras = array();
		$extra_data = array();
		
		
		foreach($data as $key=>$item) {
			if(strrpos($key,"form_field") !== false) {
				$form[$key] = sanitize_text_field($item);		
			}
			if(strrpos($key,"extra_field") !== false) {
				$extras[$key] = sanitize_text_field($item);		
			}
		}
		foreach($data as $key=>$item) {
			if(strrpos($key,"billing_info_form_field") !== false) {
				$billing_form[$key] = sanitize_text_field($item);		
			}
			if(strrpos($key,"shipping_info_form_field") !== false) {
				$shipping_form[$key] = sanitize_text_field($item);		
			}
		}
		
		
		/*FILE UPLOAD*/
		$files = array();
		if(count($_FILES)){
			foreach($_FILES as $key => $file){
				//$target_file = WPDEVART_UPLOADS . basename($file["name"]);
				//$uploadOk = 1;
                $filename = basename($file["name"]);
				
				
				if (file_exists(WPDEVART_UPLOADS . $filename))
				{
					$filename = $file["name"];
					$file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
					$file_ext = substr($filename, strripos($filename, '.')); // get file name
					//$target_file = WPDEVART_UPLOADS . $file_basename . time() . $file_ext;
					$filename = $file_basename . time() . $file_ext;
				}
				
				if (move_uploaded_file($file["tmp_name"], WPDEVART_UPLOADS . $filename)) {
					$form[$key] = WPDEVART_UPLOADS_URL . $filename;
					$files[] = WPDEVART_UPLOADS . $filename;
				}
			}
		}
		

		$billing_form = json_encode($billing_form);
		$shipping_form = json_encode($shipping_form);
		$currency = (isset($this->currency) ? $this->currency : '');
		$check_in = wpdevart_bc_Library::getData($data, 'wpdevart_form_checkin'.$submit, 'text', '');
		$check_out = wpdevart_bc_Library::getData($data, 'wpdevart_form_checkout'.$submit, 'text', '');
		if($check_in)
			$check_in = date("Y-m-d", strtotime($check_in));
		if($check_out)
			$check_out = date("Y-m-d",strtotime($check_out));
		
		$single_day = wpdevart_bc_Library::getData($data, 'wpdevart_single_day'.$submit, 'text', '');
		if($single_day)
			$single_day = date("Y-m-d",strtotime($single_day));
		/*Start hour or hour*/
		$start_hour = (isset($data['wpdevart_start_hour'.$submit]) ? sanitize_text_field(stripslashes( $data['wpdevart_start_hour'.$submit])) : (isset($data['wpdevart_form_hour'.$submit]) ? sanitize_text_field(stripslashes( $data['wpdevart_form_hour'.$submit])) : ""));
		$end_hour = wpdevart_bc_Library::getData($data, 'wpdevart_end_hour'.$submit, 'text', '');
		$count_item = wpdevart_bc_Library::getData($data, 'wpdevart_count_item'.$submit, 'text', 1);
		$total_price = wpdevart_bc_Library::getData($data, 'wpdevart_total_price_value'.$submit, 'text', '');
		$sale_percent_value = wpdevart_bc_Library::getData($data, 'sale_percent_value'.$submit, 'text', '');
		$sale_type = (isset($data['wpdevart_sale_type'.$submit]) ? esc_html(stripslashes( $data['wpdevart_sale_type'.$submit])) : '');
		$price = wpdevart_bc_Library::getData($data, 'wpdevart_price_value'.$submit, 'text', '');
		$extras_price = wpdevart_bc_Library::getData($data, 'wpdevart_extra_price_value'.$submit, 'text', '');
		
		
		
		$form_datas = json_decode($this->form_data->data,true);
		foreach($form_datas as $key => $form_data) {
			if(isset($form_data["isemail"]) && $form_data["isemail"]) {
				if(isset($form["wpdevart_".$key]) && $form["wpdevart_".$key] != "") {
					$email_array[] = $form["wpdevart_".$key];
				}
			}
		}
        if(count($email_array)) {
			$emails = implode(",",$email_array);
		}
		
		/*day count*/
		if($check_in) {
			$date_diff = abs($this->get_date_diff($check_in,$check_out));
			for($i=0; $i <= $date_diff; $i++) {
				$week_day = date('w', strtotime($check_in. " +" . $i . " day" ));
				if(!(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					$item_count++;
				}
			}
			if(isset($this->theme_option['price_for_night']) && $this->theme_option['price_for_night'] == "on") {
				$item_count -= 1;
			}
		} else {
			$item_count = 1;
		}
		if(isset($single_day) && $single_day != "") {
			$unique_id = $this->id."_".$single_day;
			$day_hours = $this->get_date_data( $unique_id );
			$day_hours = json_decode($day_hours, true);
		}
		$hour_count = 0;		
		if(isset($day_hours["hours"]) && count($day_hours["hours"])){
			$start = 0;
			foreach($day_hours["hours"] as $key => $hour) {
				if($key == $start_hour) {
					$start = 1;
				} 
				if($start == 1 && (!($end_hour == "" && $hour_count == 1))) { 
					$hour_count += 1;
				}
				if($key == $end_hour){ 
					$start = 0;
				}
			}
		}
		if($hour_count) {
			$item_count = $hour_count;
		}
		
		
		if(isset($this->extra_field)) {
			$extra_fields = json_decode( $this->extra_field->data, true );
			foreach($extras as $key => $extra) {
				$ex_key = str_replace("wpdevart_", "", $key);
				if(isset($extra_fields[$ex_key]['items'][$extra])) {
					if($extra_fields[$ex_key]['items'][$extra]["price_type"] == "price") {
						
						if(!isset($extra_fields[$ex_key]['independent']) && !isset($extra_fields[$ex_key]['independent_counts'])) {
							$extra_fields[$ex_key]['items'][$extra]['price_percent'] = $extra_fields[$ex_key]['items'][$extra]['price_percent'] * $item_count * $count_item;
						}  
						else if(isset($extra_fields[$ex_key]['independent']) && isset($extra_fields[$ex_key]['independent_counts'])) {
							$extra_fields[$ex_key]['items'][$extra]['price_percent'] = $extra_fields[$ex_key]['items'][$extra]['price_percent'];
						} 
						else if(isset($extra_fields[$ex_key]['independent'])) {
							$extra_fields[$ex_key]['items'][$extra]['price_percent'] = $extra_fields[$ex_key]['items'][$extra]['price_percent'] * $count_item;
						} 
						else if(isset($extra_fields[$ex_key]['independent_counts'])){
							$extra_fields[$ex_key]['items'][$extra]['price_percent'] = $extra_fields[$ex_key]['items'][$extra]['price_percent'] * $item_count;
						}
						
					}
					$extra_data["".$ex_key.""] = $extra_fields[$ex_key]['items'][$extra];
				}
			}
		}
		$form = json_encode($form);
		$extra_data = json_encode($extra_data);
		
		if(isset($resid) && $resid != 0){	
			$old_reserv = $wpdb->get_row($wpdb->prepare('SELECT calendar_id, single_day, check_in, check_out, start_hour, 	end_hour, count_item, status FROM ' . $wpdb->prefix . 'wpdevart_reservations WHERE id="%d"', $resid),ARRAY_A);	
			if((isset($this->theme_option['type_days_selection']) && $this->theme_option['type_days_selection'] == "multiple_days") && (isset($this->theme_option['hours_enabled']) && $this->theme_option['hours_enabled'] == "")){
				$single_day = "";
			} elseif(isset($this->theme_option['type_days_selection']) && $this->theme_option['type_days_selection'] == "single_day") {
				$check_in = "";
				$check_out = "";
			}	
			if(isset($this->theme_option['type_hours_selection']) && $this->theme_option['type_hours_selection'] == "single_hour") {
				$end_hour = "";
			}			
			$save_in_db = $wpdb->update($wpdb->prefix . 'wpdevart_reservations', array(    
			    'single_day' => $single_day,                       
				'check_in' => $check_in,         
				'check_out' => $check_out,         
				'start_hour' => $start_hour,         
				'end_hour' => $end_hour,         
				'count_item' => $count_item,         
				'price' => $price,         
				'total_price' => $total_price,         
				'extras' => $extra_data,         
				'extras_price' => $extras_price,         
				'form' => $form,         
				'address_billing' => $billing_form,         
				'address_shipping' => $shipping_form,         
				'email' => $emails,        
				'is_new' => 0,                  
				'sale_percent' => $sale_percent_value,     
			    'sale_type' => $sale_type    
			  ), array('id' => $resid));
		} else {
			$save_in_db = $wpdb->insert($wpdb->prefix . 'wpdevart_reservations', array(
			'calendar_id' => $this->id,                       
			'single_day' => $single_day,                       
			'check_in' => $check_in,         
			'check_out' => $check_out,         
			'start_hour' => $start_hour,         
			'end_hour' => $end_hour,         
			'currency' => $currency,         
			'count_item' => $count_item,         
			'price' => $price,         
			'total_price' => $total_price,         
			'extras' => $extra_data,         
			'extras_price' => $extras_price,         
			'form' => $form,         
			'address_billing' => '',         
			'address_shipping' => '',         
			'email' => $emails,         
			'status' => $status,         
			'payment_method' => '',         
			'payment_status' => '',         
			'date_created' => date('Y-m-d H:i',time()),        
			'is_new' => 1,        
			'sale_percent' => $sale_percent_value,     
			'sale_type' => $sale_type     
		  ), array(
			'%d', /*calendar_id*/
			'%s', /*single_day*/
			'%s', /*check_in*/
			'%s', /*check_out*/
			'%s', /*start_hour*/
			'%s', /*end_hour*/
			'%s', /*currency*/
			'%d', /*count_item*/
			'%s', /*price*/
			'%s', /*total_price*/
			'%s', /*extras*/
			'%s', /*extras_price*/
			'%s', /*form*/
			'%s', /*address_billing*/
			'%s', /*address_shipping*/
			'%s', /*email*/
			'%s', /*status*/
			'%s', /*payment_method*/
			'%s', /*payment_status*/
			'%s', /*date_created*/
			'%d', /*is_new*/
			'%s', /*sale_value*/
			'%s' /*sale_type*/
		  ));
		}  
		
		 if($save_in_db) {
			$save = true;
			if(isset($resid) && $resid != 0){
				$id = $resid;
			} else {
				$id = $wpdb->get_var('SELECT MAX(id) FROM ' . $wpdb->prefix . 'wpdevart_reservations');
			}
			if($status == "approved") {
				if(isset($resid) && $resid != 0){
					$this->change_date_avail_count($id,true,"update",$old_reserv);
				} else{
					$this->change_date_avail_count($id,true,"insert",array());
				}
			}
			$send_mail = $this->send_mail($emails,$form,$extra_data,$count_item,$price,$currency,$total_price,$extras_price,$check_in,$check_out,$single_day,$start_hour, $end_hour,$sale_percent_value,$files, $sale_type);
			$reserv_info = array('reservation_id' => $id,
				'calendar_id' => $this->id,                       
				'single_day' => $single_day,                       
				'check_in' => $check_in,         
				'check_out' => $check_out,         
				'start_hour' => $start_hour,         
				'end_hour' => $end_hour,         
				'currency' => $currency,         
				'count_item' => $count_item,         
				'price' => $price,         
				'total_price' => $total_price,         
				'extras' => $extra_data,         
				'extras_price' => $extras_price,         
				'form' => $form,         
				'email' => $emails,
                'sale_percent' => $sale_percent_value,
				'files' => $files,
                'sale_type' => $sale_type				
				);
		 } 
		$result = array($save, $send_mail, $reserv_info); 
		return $result;
	}
	private function send_mail($emails,$form_data,$extras_data,$count_item,$price,$currency,$total_price,$extras_price,$check_in,$check_out,$single_day,$start_hour, $end_hour,$sale_percent,$files, $sale_type){
		
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		$sale_percent_html = "";
		$cur_pos = (isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? "before" : "after";
		if(isset($sale_percent) && !empty($sale_percent)){
			if($sale_type == "percent"){
				$sale_percent_value = ($total_price * 100) / (100 - $sale_percent);
				$sale_percent_html = (($cur_pos == "before" ? $currency : '') . $sale_percent_value . ($cur_pos == "after" ? $currency : '')) . " - " . $sale_percent . "% = ";
			} else {
				$sale_percent_value = $total_price + $sale_percent;
				$sale_percent_html = (($cur_pos == "before" ? $currency : '') . $sale_percent_value . ($cur_pos == "after"  ? $currency : '')) . " - " . ($cur_pos == "before" ? $currency : '') . $sale_percent . ($cur_pos == "after" ? $currency : '') ." = ";
			}
		}
		$countries = self::get_countries();
		$admin_email_types = array();
		$user_email_types = array();
		$admin_error_types = array();
		$user_error_types = array();
		$hour_html = "";
		$form_data = $this->get_form_data($form_data);
        $extras_data = $this->get_extra_data($extras_data,$price);
		if($check_in) {
			$check_in = date($this->theme_option["date_format"], strtotime($check_in));
			$check_out = date($this->theme_option["date_format"], strtotime($check_out));
			$res_day = $check_in. "-" .$check_out;
		} else {
			$res_day = date($this->theme_option["date_format"], strtotime($single_day));
		}
		$hide_price = (isset($this->theme_option['hide_price']) && $this->theme_option['hide_price'] == "on") ? true : false;
		if(isset($start_hour) && $start_hour != ""){
			$hour_html = $start_hour;
		}
		if(isset($end_hour) && $end_hour != ""){
			$hour_html = $hour_html." - ".$end_hour;
		}
		if($hour_html != ""){
			$hour_html = "<tr><td style='padding: 1px 7px;'>".__('Hour','booking-calendar')."</td> <td  style='padding: 1px 7px;'>".$hour_html.'</td></tr>';
		}
		$site_url = site_url();
		$moderate_link = admin_url() . "admin.php?page=wpdevart-reservations";
		$res_info = "<table border='1' style='border-collapse:collapse;min-width: 360px;'>
						<caption style='text-align:left;'>".__('Details','booking-calendar')."</caption>
						<tr><td style='padding: 1px 7px;'>".__('Reservation dates','booking-calendar')."</td><td style='padding: 1px 7px;'>".$res_day."</td></tr>".$hour_html."
						<tr><td style='padding: 1px 7px;'>".__('Item Count','booking-calendar')."</td><td style='padding: 1px 7px;'>".$count_item."</td></tr>";
		if($price != "NaN" && !$hide_price){				
			$res_info .= "<tr><td style='padding: 1px 7px;'>".__('Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".($cur_pos == "before" ? esc_html($currency) : '') . $price . ($cur_pos == "after" ? esc_html($currency) : '')."</td></tr>";
		}
		if($total_price != "NaN" && !$hide_price){
			$res_info .= "<tr><td style='padding: 1px 7px;'>".__('Total Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".$sale_percent_html . ($cur_pos == "before" ? esc_html($currency) : '') . $total_price . ($cur_pos == "after" ? esc_html($currency) : '')."</td></tr>";
		}
		$res_info .= "</table>";
		$form = "";
		$extras = "";		
		if(count($form_data)) {
			$form .= "<table border='1' style='border-collapse:collapse;min-width: 360px;'>";
			$form .= "<caption style='text-align:left;'>".__('Contact Information','booking-calendar')."</caption>";
			foreach($form_data as $form_fild_data) {
				if($form_fild_data['type'] == 'countries' && trim($form_fild_data['value']) != "") {
					$form .= "<tr><td style='padding: 1px 7px;'>". $form_fild_data["label"] ."</td> <td style='padding: 1px 7px;'>". $countries[$form_fild_data["value"]] ."</td></tr>";
				}else {
					$form .= "<tr><td style='padding: 1px 7px;'>". $form_fild_data["label"] ."</td> <td style='padding: 1px 7px;'>". $form_fild_data["value"] ."</td></tr>";
				}
			}
			$form .= "</table>";
		}	
		if(count($extras_data)) {
			$extras .= "<table border='1' style='border-collapse:collapse;min-width: 360px;'>";
			$extras .= "<caption style='text-align:left;'>".__('Extra Information','booking-calendar')."</caption>";
			foreach($extras_data as $extra_data) {
				$extras .= "<tr><td colspan='2' style='padding: 1px 7px;'>".$extra_data["group_label"]."</td></tr>";
				$extras .= "<tr><td style='padding: 1px 7px;'>". $extra_data["label"] ."</td>"; 
				$extras .= "<td style='padding: 1px 7px;'>";
				if($extra_data["price_type"] == "percent") {
					$extras .= "<span class='price-percent'>".$extra_data["operation"].$extra_data["price_percent"]."%</span>";
					$extras .= "<span class='price'>".$extra_data["operation"] . ($cur_pos == "before" ? esc_html($currency) : '') . $extra_data["price"] . ($cur_pos == "after" ? esc_html($currency) : '')."</span></td></tr>";
				} else {
					$extras .= "<span class='price'>".$extra_data["operation"] .($cur_pos == "before" ? esc_html($currency) : '') . $extra_data["price"] . ($cur_pos == "after" ? esc_html($currency) : '')."</span></td></tr>";
				}
				
			}
			$extras .= "<tr><td style='padding: 1px 7px;'>" . __('Price change','booking-calendar')."</td><td style='padding: 1px 7px;'>".(($extras_price<0)? "" : "+").($cur_pos == "before" ? esc_html($currency) : '') . $extras_price . ($cur_pos == "after" ? esc_html($currency) : '')."</td></tr>";
			$extras .= "</table>";
		}
		if(isset($this->theme_option['notify_admin_on_book']) && $this->theme_option['notify_admin_on_book'] == "on") {
			$admin_email_types[] = 'notify_admin_on_book';
		}
		if(isset($this->theme_option['notify_user_on_book']) && $this->theme_option['notify_user_on_book'] == "on") {
			$user_email_types[] = 'notify_user_on_book';
		}
		if(isset($this->theme_option['enable_instant_approval']) && $this->theme_option['enable_instant_approval'] == "on") {
			if(isset($this->theme_option['notify_admin_on_approved']) && $this->theme_option['notify_admin_on_approved'] == "on") {
				$admin_email_types[] = 'notify_admin_on_approved';
			}
			if(isset($this->theme_option['notify_user_on_approved']) && $this->theme_option['notify_user_on_approved'] == "on") {
				$user_email_types[] = 'notify_user_on_approved';
			}
		}	
		
		
		/*Attachment*/
		$attachments = $files;
		
			/*Email to admin*/
		if(count($admin_email_types)) {	
			foreach($admin_email_types as $admin_email_type) {
				$to = "";
				$from = "";
				$fromname = "";
				$subject = "";
				$content = "";
				if(isset($this->theme_option[$admin_email_type.'_to']) && $this->theme_option[$admin_email_type.'_to'] != "") {
					$to = stripslashes($this->theme_option[$admin_email_type.'_to']);
				}
				if(isset($this->theme_option[$admin_email_type.'_fromname']) && $this->theme_option[$admin_email_type.'_fromname'] != "") {
					$fromname = stripslashes($this->theme_option[$admin_email_type.'_fromname']);
				}
				if(isset($this->theme_option[$admin_email_type.'_subject']) && $this->theme_option[$admin_email_type.'_subject'] != "") {
					$subject = stripslashes($this->theme_option[$admin_email_type.'_subject']);
				}
				if(isset($this->theme_option[$admin_email_type.'_content']) && $this->theme_option[$admin_email_type.'_content'] != "") {
					$content = stripslashes($this->theme_option[$admin_email_type.'_content']);
					$content = str_replace("[calendartitle]", $this->calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[moderatelink]", $moderate_link, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $total_price, $content);
					$mail_content = "<div class='wpdevart_email' style='text-align: center;color:".((isset($this->theme_option['mail_color']) && $this->theme_option['mail_color'] != "") ? $this->theme_option['mail_color'] : "#5A5A5A")." !important;background-color:".((isset($this->theme_option['mail_bg']) && $this->theme_option['mail_bg'] != "") ? $this->theme_option['mail_bg'] : "#e8e8f7")." !important;line-height: 1.5;'>";
					if(isset($this->theme_option['mail_header_img']) && $this->theme_option['mail_header_img'] != ""){
						$mail_content .= "<img src='".esc_url($this->theme_option['mail_header_img'])."' style='max-width:670px;margin:20px auto 0;'>";
					}
					$mail_content .= "<div style='width: 670px;margin: 0 auto;padding: 15px;background-color:" .((isset($this->theme_option['mail_content_bg']) && $this->theme_option['mail_content_bg'] != "") ? $this->theme_option['mail_content_bg'] : "#e8e8f7")." !important;'>".$content."</div>";
					if(isset($this->theme_option['mail_footer_text']) && $this->theme_option['mail_footer_text'] != ""){
						$mail_content .= "<p style='color:" .((isset($this->theme_option['mail_footer_text_color']) && $this->theme_option['mail_footer_text_color'] != "") ? $this->theme_option['mail_footer_text_color'] : "#a7a7a7")." !important;padding: 10px 0; font-size: 13px;'>".$this->theme_option['mail_footer_text']."</p>";
					}
					$mail_content .= "</div>";
				}
				if(isset($this->theme_option[$admin_email_type.'_from']) && $this->theme_option[$admin_email_type.'_from'] != "") {
					if(isset($this->theme_option['use_phpmailer']) && $this->theme_option['use_phpmailer'] == "on"){
						if(trim($this->theme_option[$admin_email_type.'_from']) == "[useremail]") {
							$from = $emails;
						} else {
							$from = $this->theme_option[$admin_email_type.'_from'];
						}
					} else {
						if(trim($this->theme_option[$admin_email_type.'_from']) == "[useremail]") {
							$from = "From: '" . $fromname . "' <" . $emails . ">" . "\r\n";
						} else {
							$from = "From: '" . $fromname . "' <" . stripslashes($this->theme_option[$admin_email_type.'_from']) . ">" . "\r\n";
						}
					}
				}
				
				
				if(isset($this->theme_option['use_phpmailer']) && $this->theme_option['use_phpmailer'] == "on"){
					$mail_to_send = new PHPMailer();
					$mail_to_send->CharSet  = get_option('blog_charset');
					$mail_to_send->FromName = $fromname;
					$mail_to_send->From     = $from;
					$mail_to_send->Subject  = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $subject ) ));
					$mail_to_send->Body 	= $mail_content ;
					if(!$mail_to_send->Body) {	
						$mail_to_send->Body = $mail_to_send->FromName ." sent you this email";
					}
					if(count($attachments)){
						foreach($attachments as $attachment){
							$mail_to_send->AddAttachment($attachment);
						}
					}
					$mail_to_send->AltBody = wp_strip_all_tags($content);
					$mail_to_send->IsHTML(true);
					$to_arr = explode(",", $to);
					foreach($to_arr as $mail){
						$mail_to_send->AddAddress($mail);
					}
					if ($mail_to_send->Send() ) {
						$admin_error_types[$admin_email_type] = true;
					} else {
						$admin_error_types[$admin_email_type] = false;
					}
				} else {
					$headers = "MIME-Version: 1.0\n" . $from . " Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
					$admin_error_types[$admin_email_type] = wp_mail($to, $subject, $mail_content, $headers, $attachments);
				}
			}	
		}	
			/*Email to user*/
		if(count($user_email_types)) {	
			foreach($user_email_types as $user_email_type) {	
				$from = "";
				$fromname = "";
				$subject = "";
				$content = "";
				$to = $emails;
				if(isset($this->theme_option[$user_email_type.'_subject']) && $this->theme_option[$user_email_type.'_subject'] != "") {
					$subject = stripslashes($this->theme_option[$user_email_type.'_subject']);
				}
				if(isset($this->theme_option[$user_email_type.'_fromname']) && $this->theme_option[$user_email_type.'_fromname'] != "") {
					$fromname = stripslashes($this->theme_option[$user_email_type.'_fromname']);
				}
				if(isset($this->theme_option[$user_email_type.'_content']) && $this->theme_option[$user_email_type.'_content'] != "") {
					$content = stripslashes($this->theme_option[$user_email_type.'_content']);
					$content = str_replace("[calendartitle]", $this->calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $total_price, $content);
					$mail_content = "<div class='wpdevart_email' style='text-align: center;color:".((isset($this->theme_option['mail_color']) && $this->theme_option['mail_color'] != "") ? $this->theme_option['mail_color'] : "#5A5A5A")." !important;background-color:".((isset($this->theme_option['mail_bg']) && $this->theme_option['mail_bg'] != "") ? $this->theme_option['mail_bg'] : "#e8e8f7")." !important;line-height: 1.5;'>";
					if(isset($this->theme_option['mail_header_img']) && $this->theme_option['mail_header_img'] != ""){
						$mail_content .= "<img src='".esc_url($this->theme_option['mail_header_img'])."' style='max-width:670px;margin:20px auto 0;'>";
					}
					$mail_content .= "<div style='width: 670px;margin: 0 auto;padding: 15px;background-color:" .((isset($this->theme_option['mail_content_bg']) && $this->theme_option['mail_content_bg'] != "") ? $this->theme_option['mail_content_bg'] : "#e8e8f7")." !important;'>".$content."</div>";
					if(isset($this->theme_option['mail_footer_text']) && $this->theme_option['mail_footer_text'] != ""){
						$mail_content .= "<p style='color:" .((isset($this->theme_option['mail_footer_text_color']) && $this->theme_option['mail_footer_text_color'] != "") ? $this->theme_option['mail_footer_text_color'] : "#a7a7a7")." !important;padding: 10px 0; font-size: 13px;'>".$this->theme_option['mail_footer_text']."</p>";
					}
					$mail_content .= "</div>";
				}
				if(isset($this->theme_option[$user_email_type.'_from']) && $this->theme_option[$user_email_type.'_from'] != "") {
					if(isset($this->theme_option['use_phpmailer']) && $this->theme_option['use_phpmailer'] == "on"){
						$from = $this->theme_option[$user_email_type.'_from'];
					} else {
						$from = "From: '" . $fromname . "' <" . stripslashes($this->theme_option[$user_email_type.'_from']) . ">" . "\r\n";
					}
				}
				if(isset($this->theme_option['use_phpmailer']) && $this->theme_option['use_phpmailer'] == "on"){
					$mail_to_send = new PHPMailer();
					$mail_to_send->CharSet = 'UTF-8';
					$mail_to_send->FromName = $fromname;
					$mail_to_send->From     = $from;
					$mail_to_send->Subject  = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $subject ) ));
					$mail_to_send->Body 	= $mail_content;
					if(!$mail_to_send->Body) {	
						$mail_to_send->Body = $mail_to_send->FromName ." sent you this email";
					}
					if(count($attachments)){
						foreach($attachments as $attachment){
							$mail_to_send->AddAttachment($attachment);
						}
					}
					$mail_to_send->AltBody = wp_strip_all_tags($content);
					$mail_to_send->IsHTML(true);
					$to_arr = explode(",", $to);
					foreach($to_arr as $mail){
						$mail_to_send->AddAddress($mail);
					}
					if ($mail_to_send->Send() ) {
						$user_error_types[$user_email_type] = true;
					} else {
						$user_error_types[$user_email_type] = false;
					}
				} else {
					$headers = "MIME-Version: 1.0\n" . $from . " Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
					$user_error_types[$user_email_type] = wp_mail($to, $subject, $mail_content, $headers, $attachments);
				}
			}
		}	
		$result = array($admin_error_types,$user_error_types);
		return 	$result;
	}
	
	public function get_styles(){
		return "";
	}
	
	private function change_date_avail_count( $id,$approve,$type = "",$old_reserv = array() ){
		global $wpdb; 	
		$reserv_info = $wpdb->get_row($wpdb->prepare('SELECT calendar_id, single_day, check_in, check_out, start_hour, 	end_hour, count_item, status FROM ' . $wpdb->prefix . 'wpdevart_reservations WHERE id="%d"', $id),ARRAY_A);
		if(isset($reserv_info["count_item"])) {
			$count_item = $reserv_info["count_item"];
		} else {
			$count_item = 1;
		}
		$cal_id = $reserv_info["calendar_id"]; 
		/*UPDATE*/
		if($type == "update" && $approve === true){
			if($old_reserv["single_day"] == "") {
				$start_date = $old_reserv["check_in"];
				$date_diff = abs($this->get_date_diff($old_reserv["check_in"],$old_reserv["check_out"]));
				for($i=0; $i <= $date_diff; $i++) {
					if(isset($this->theme_option["price_for_night"]) && $this->theme_option["price_for_night"] == "on"  && $i == $date_diff){
						continue;
					}
					$day = date( 'Y-m-d', strtotime($start_date. " +" . $i . " day" ));
					$unique_id = $cal_id."_".$day;
					$day_data = json_decode($this->get_date_data( $unique_id ),true);
				
					$day_data["available"] = $day_data["available"] + $old_reserv['count_item'];
					if($day_data["available"]  > 0)
						$day_data["status"] = "available";
					$day_info_jsone = json_encode($day_data);
					$update_in_db = $wpdb->update($wpdb->prefix . 'wpdevart_dates', array(
						'calendar_id' => $cal_id,
						'day' => $day,
						'data' => $day_info_jsone,
					  ), array('unique_id' => $unique_id));
				}
			} else {
				$unique_id = $cal_id."_".$old_reserv["single_day"];
				$day_data = json_decode($this->get_date_data( $unique_id ),true);
				if($old_reserv["end_hour"] == "" && $old_reserv["start_hour"] == "") {
					$day_data["available"] = $day_data["available"] + $old_reserv['count_item'];
				} else {
					if($old_reserv["end_hour"] == "") {
						$day_data["hours"][$old_reserv["start_hour"]]["available"] =  $day_data["hours"][$old_reserv["start_hour"]]["available"] + $old_reserv['count_item'];
						$day_data["hours"][$old_reserv["start_hour"]]["status"] = "available";
						$count = 1;	
					} else {
						/*multihour here*/
						if(count($day_data["hours"])) {
							$start = 0;
							$count = 0;							
							foreach($day_data["hours"] as $key => $hour) {
								if($key == $old_reserv["start_hour"]) {
									$start = 1;
								} 
								if($start == 1) {
									$day_data["hours"][$key]["available"] =  $day_data["hours"][$key]["available"] + $old_reserv['count_item'];
									$count += 1;
								}
								if($key == $old_reserv["end_hour"]) {
									$start = 0;
								}
								if($day_data["hours"][$key]["available"] > 0)
									$day_data["hours"][$key]["status"] = "available";
							}
						}
					}
					$day_data["available"] = $day_data["available"] + ($old_reserv['count_item']*$count);
				}
				if($day_data["available"] > 0) {
					$day_data["status"] = "available";
				}
				
				$day_info_jsone = json_encode($day_data);
				$update_in_db = $wpdb->update($wpdb->prefix . 'wpdevart_dates', array(
					'calendar_id' => $cal_id,
					'day' => $old_reserv["single_day"],
					'data' => $day_info_jsone,
				  ), array('unique_id' => $unique_id));
			}
		}
		/*UPDATE END*/
		
		if($reserv_info["single_day"] == "") {
			$start_date = $reserv_info["check_in"];
			$date_diff = abs($this->get_date_diff($reserv_info["check_in"],$reserv_info["check_out"]));
			for($i=0; $i <= $date_diff; $i++) {
				if(isset($this->theme_option["price_for_night"]) && $this->theme_option["price_for_night"] == "on"  && $i == $date_diff){
					continue;
				}
				$day = date( 'Y-m-d', strtotime($start_date. " +" . $i . " day" ));
				$unique_id = $cal_id."_".$day;
				$day_data = json_decode($this->get_date_data( $unique_id ),true);
				if($approve === true) {
					$day_data["available"] = $day_data["available"] - $count_item;
					if($day_data["available"] == 0) {
						$day_data["status"] = "booked";
					}
				} else {
					$day_data["available"] = $day_data["available"] + $count_item;
					$day_data["status"] = "available";
				}
				$day_info_jsone = json_encode($day_data);
				$update_in_db = $wpdb->update($wpdb->prefix . 'wpdevart_dates', array(
					'calendar_id' => $cal_id,
					'day' => $day,
					'data' => $day_info_jsone,
				  ), array('unique_id' => $unique_id));
			}
		} else {
			$unique_id = $cal_id."_".$reserv_info["single_day"];
			$day_data = json_decode($this->get_date_data( $unique_id ),true);
			if($approve === true) {
				if($reserv_info["end_hour"] == "" && $reserv_info["start_hour"] == "") {
					$day_data["available"] = $day_data["available"] - $count_item;
				} else {
					if($reserv_info["end_hour"] == "") {
						$day_data["hours"][$reserv_info["start_hour"]]["available"] =  $day_data["hours"][$reserv_info["start_hour"]]["available"] - $count_item;
						if($day_data["hours"][$reserv_info["start_hour"]]["available"] == 0) {
							$day_data["hours"][$reserv_info["start_hour"]]["status"] = "booked";
						}
						$count = 1;	
					} else {
						/*multihour here*/
						if(count($day_data["hours"])) {
							$start = 0;
							$count = 0;							
							foreach($day_data["hours"] as $key => $hour) {
								if($key == $reserv_info["start_hour"]) {
									$start = 1;
								} 
								if($start == 1) {
									$day_data["hours"][$key]["available"] =  $day_data["hours"][$key]["available"] - $count_item;
									$count += 1;
								}
								if($key == $reserv_info["end_hour"]) {
									$start = 0;
								}
								if($day_data["hours"][$key]["available"] == 0) {
									$day_data["hours"][$key]["status"] = "booked";
								}
							}
						}
					}
					$day_data["available"] = $day_data["available"] - ($count_item*$count);
				}
				if($day_data["available"] == 0) {
					$day_data["status"] = "booked";
				}
			} else {
				if($reserv_info["end_hour"] == "" && $reserv_info["start_hour"] == "") {
					$day_data["available"] = $day_data["available"] + $count_item;
				} else {
					if($reserv_info["end_hour"] == "") {
						$day_data["hours"][$reserv_info["start_hour"]]["available"] =  $day_data["hours"][$reserv_info["start_hour"]]["available"] + $count_item;
						$day_data["hours"][$reserv_info["start_hour"]]["status"] = "available";
						$count = 1;	
					} else {
						/*multihour here*/
						if(count($day_data["hours"])) {
							$start = 0; 
							$count = 0;	
							foreach($day_data["hours"] as $key => $hour) {
								if($key == $reserv_info["start_hour"]) {
									$start = 1;
								}
								if($start == 1) {
									$day_data["hours"][$key]["available"] =  $day_data["hours"][$key]["available"] + $count_item;
									$count += 1;
								}
								if($key == $reserv_info["end_hour"]) {
									$start = 0;
								}
								if($day_data["hours"][$key]["available"] != 0) {
									$day_data["hours"][$key]["status"] = "available";
								}
							}
						}
					}
					$day_data["available"] = $day_data["available"] + ($count_item * $count);
				}
				$day_data["status"] = "available";
			}
			
			$day_info_jsone = json_encode($day_data);
		
			$update_in_db = $wpdb->update($wpdb->prefix . 'wpdevart_dates', array(
				'calendar_id' => $cal_id,
				'day' => $reserv_info["single_day"],
				'data' => $day_info_jsone,
			  ), array('unique_id' => $unique_id));
		}
	}
	private function get_date_data( $unique_id ) {
		global $wpdb;
		$row = $wpdb->get_row($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_dates WHERE unique_id="%s"', $unique_id),ARRAY_A);
		$date_info = $row["data"];
		return $date_info;
	}
	
	private function get_reservation_row_calid( $id, $date ) {
		global $wpdb;
		$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'wpdevart_reservations WHERE calendar_id=%d and ((check_in <= %s and check_out >= %s) or single_day = %s)',$id,$date,$date,$date),ARRAY_A);
		return $rows;
	}
	private function get_form_data($form) {
		global $wpdb;
		if($form) {
			$form_value = json_decode($form, true);
			$cal_id = $this->id;
			$form_id = $wpdb->get_var($wpdb->prepare('SELECT form_id FROM ' . $wpdb->prefix . 'wpdevart_calendars WHERE id="%d"', $cal_id));
			$form_info = $wpdb->get_var($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_forms WHERE id="%d"', $form_id));
			$form_info = json_decode($form_info, true);
			if(isset($form_info['apply']) || isset($form_info['save']))	{
				array_shift($form_info);
			}
			foreach($form_info as $key=>$form_fild_info) { 
				if(isset($form_value["wpdevart_".$key])) {
					$form_info[$key]["value"] = $form_value["wpdevart_".$key];
				}
				else {
					$form_info[$key]["value"] = "";
				}
			}
		} else {
			$form_info = array();
		}
		return $form_info;
	} 
	
	private function get_extra_data($extra,$price = false) {
		global $wpdb;
		if($price !== false) {
			$price = $price;
			$extra = $extra;
		} else  {
			$price = $extra["price"];
			$extra = $extra["extras"];
		}
		if($extra) {
			$extras_value = json_decode($extra, true);
			$cal_id = $this->id;
			$extra_id = $wpdb->get_var($wpdb->prepare('SELECT extra_id FROM ' . $wpdb->prefix . 'wpdevart_calendars WHERE id="%d"', $cal_id));
			$extra_info = $wpdb->get_var($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_extras WHERE id="%d"', $extra_id));
			$extra_info = json_decode($extra_info, true);
			
			if(isset($extra_info['apply']) || isset($extra_info['save']))	{
				array_shift($extra_info);
			}
			foreach($extras_value as $key=>$extra_value) { 
				if(isset($extra_info[$key])) {
					$extras_value[$key]["group_label"] = $extra_info[$key]["label"];
					if($extra_value['price_type'] == "percent") {
						$extras_value[$key]["price"] = ($price*$extra_value['price_percent'])/100;
					} else {
						$extras_value[$key]["price"] = $extra_value['price_percent'];
					}
				}
				else {
					$extras_value[$key]["group_label"] = "";
				}
			}
		} else {
			$extras_value = array();
		}
		return $extras_value;
	} 
	
	private function  get_hours_count($single_day,$start_hour,$end_hour){
		global $wpdb;
		$count_av = array();
		$price = 0;
		$unique_id = $this->id . "_" . $single_day;
		$get_date = $wpdb->get_var($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_dates WHERE unique_id="%s"', $unique_id));
		$day_data = json_decode($get_date,true);
		if(isset($day_data["hours"])){
			if($end_hour == "") {
				$count_av[] =  $day_data["hours"][$start_hour]["available"];
				$price += $day_data["hours"][$start_hour]['price'];
			} else {
				/*multihour here*/
				if(count($day_data["hours"])) {
					$start = 0;					
					foreach($day_data["hours"] as $key => $hour) {
						if($key == $start_hour) {
							$start = 1;
						} 
						if($start == 1) {
							$count_av[] =  $day_data["hours"][$key]["available"];
							$price += $day_data["hours"][$key]['price'];
						}
						if($key == $end_hour) {
							$start = 0;
						}
					}
				}
			}
		}
		if(count($count_av))
			$count_av = min($count_av);
		
		return array("min" => $count_av,"price" => $price);
	}
	
	private function  get_interval_dates($start_date,$end_date = ""){
		global $wpdb;
		$id = 0;
		$selected_count = array(); // main genereted days
		$avaible_days_array = array();
		$result = array();
		$price = 0;
		$get_cur_call_all_dates = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'wpdevart_dates WHERE calendar_id="%d"', $this->id),ARRAY_A);
		
		foreach($get_cur_call_all_dates as $key => $value){
			$avaible_days_array[$key] = $value['day'];		
		}
		if($end_date != ""){
			$date_diff = abs($this->get_date_diff($start_date,$end_date));
			if($date_diff > 3500){
				return "";
			}
			for($i=0; $i <= $date_diff; $i++) {
				if(isset($this->theme_option['price_for_night']) && $this->theme_option['price_for_night'] == "on" && $i == $date_diff) {
					continue;
				}
				$day = date( 'Y-m-d', strtotime($start_date. " +" . $i . " day" ));
				$week_day = date('w', strtotime($start_date. " +" . $i . " day" ));
				if(!(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					if(false !== $key = array_search($day,$avaible_days_array)){
						$data = json_decode($get_cur_call_all_dates[$key]['data'],true);
						$selected_count[] = $data['available'];
						$price += $data['price'];
					}
				}
			}
		} else {
			if(isset($this->theme_option['hours_enabled']) && $this->theme_option['hours_enabled'] == "on") {
				$day = date( 'Y-m-d', strtotime($start_date));
				$week_day = date('w', strtotime($start_date));
				if(!(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					if(false !== $key = array_search($day,$avaible_days_array)){
						$data = json_decode($get_cur_call_all_dates[$key]['data'],true);
						if(isset($data['hours'])){
							foreach($data['hours'] as $hour){
								$selected_count[] = $hour['available'];
								$price += $hour['price'];
							}
						}
					}
				}
			} else{
				$day = date( 'Y-m-d', strtotime($start_date));
				$week_day = date('w', strtotime($start_date));
				if(!(isset($this->theme_option['unavailable_week_days']) && in_array($week_day,$this->theme_option['unavailable_week_days']))) {
					if(false !== $key = array_search($day,$avaible_days_array)){
						$data = json_decode($get_cur_call_all_dates[$key]['data'],true);
						$selected_count[] = $data['available'];
						$price += $data['price'];
					}
				}	
			}
		}
		$result["min"] = 0;
		if(count($selected_count))
			$result["min"] = min($selected_count);
		$result["price"] = $price;
		return $result;
	}
	
	public function get_form_data_rows( $id ) {
		global $wpdb;
		$row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'wpdevart_forms WHERE id="%d"', $id, ARRAY_A));
	   
		return $row;
	}  
	
	public static function get_animations_type_array($animation=''){
		if($animation=='' || $animation=='none')
			return '';
		if($animation=='random'){	
			return self::$list_of_animations[array_rand(self::$list_of_animations,1)];
		}
		return $animation;
	}
	
	public static function get_countries(){
		$countries = array('' => __('Select Country','booking-calendar'),	'AF' => 'Afghanistan',	'AX' => 'Aland Islands',	'AL' => 'Albania',	'DZ' => 'Algeria',	'AS' => 'American Samoa',	'AD' => 'Andorra',	'AO' => 'Angola',	'AI' => 'Anguilla',	'AQ' => 'Antarctica',	'AG' => 'Antigua And Barbuda',	'AR' => 'Argentina',	'AM' => 'Armenia',	'AW' => 'Aruba',	'AU' => 'Australia',	'AT' => 'Austria',	'AZ' => 'Azerbaijan',	'BS' => 'Bahamas',	'BH' => 'Bahrain',	'BD' => 'Bangladesh',	'BB' => 'Barbados',	'BY' => 'Belarus',	'BE' => 'Belgium',	'BZ' => 'Belize',	'BJ' => 'Benin',	'BM' => 'Bermuda',	'BT' => 'Bhutan',	'BO' => 'Bolivia',	'BA' => 'Bosnia And Herzegovina',	'BW' => 'Botswana',	'BV' => 'Bouvet Island',	'BR' => 'Brazil',	'IO' => 'British Indian Ocean Territory',	'BN' => 'Brunei Darussalam',	'BG' => 'Bulgaria',	'BF' => 'Burkina Faso',	'BI' => 'Burundi',	'KH' => 'Cambodia',	'CM' => 'Cameroon',	'CA' => 'Canada',	'CV' => 'Cape Verde',	'KY' => 'Cayman Islands',	'CF' => 'Central African Republic',	'TD' => 'Chad',	'CL' => 'Chile',	'CN' => 'China',	'CX' => 'Christmas Island',	'CC' => 'Cocos (Keeling) Islands',	'CO' => 'Colombia',	'KM' => 'Comoros',	'CG' => 'Congo',	'CD' => 'Congo, Democratic Republic',	'CK' => 'Cook Islands',	'CR' => 'Costa Rica',	'CI' => 'Cote D\'Ivoire',	'HR' => 'Croatia',	'CU' => 'Cuba',	'CY' => 'Cyprus',	'CZ' => 'Czech Republic',	'DK' => 'Denmark',	'DJ' => 'Djibouti',	'DM' => 'Dominica',	'DO' => 'Dominican Republic',	'EC' => 'Ecuador',	'EG' => 'Egypt',	'SV' => 'El Salvador',	'GQ' => 'Equatorial Guinea',	'ER' => 'Eritrea',	'EE' => 'Estonia',	'ET' => 'Ethiopia',	'FK' => 'Falkland Islands (Malvinas)',	'FO' => 'Faroe Islands',	'FJ' => 'Fiji',	'FI' => 'Finland',	'FR' => 'France',	'GF' => 'French Guiana',	'PF' => 'French Polynesia',	'TF' => 'French Southern Territories',	'GA' => 'Gabon',	'GM' => 'Gambia',	'GE' => 'Georgia',	'DE' => 'Germany',	'GH' => 'Ghana',	'GI' => 'Gibraltar',	'GR' => 'Greece',	'GL' => 'Greenland',	'GD' => 'Grenada',	'GP' => 'Guadeloupe',	'GU' => 'Guam',	'GT' => 'Guatemala',	'GG' => 'Guernsey',	'GN' => 'Guinea',	'GW' => 'Guinea-Bissau',	'GY' => 'Guyana',	'HT' => 'Haiti',	'HM' => 'Heard Island & Mcdonald Islands',	'VA' => 'Holy See (Vatican City State)',	'HN' => 'Honduras',	'HK' => 'Hong Kong',	'HU' => 'Hungary',	'IS' => 'Iceland',	'IN' => 'India',	'ID' => 'Indonesia',	'IR' => 'Iran, Islamic Republic Of',	'IQ' => 'Iraq',	'IE' => 'Ireland',	'IM' => 'Isle Of Man',	'IL' => 'Israel',	'IT' => 'Italy',	'JM' => 'Jamaica',	'JP' => 'Japan',	'JE' => 'Jersey',	'JO' => 'Jordan',	'KZ' => 'Kazakhstan',	'KE' => 'Kenya',	'KI' => 'Kiribati',	'KR' => 'Korea',	'KW' => 'Kuwait',	'KG' => 'Kyrgyzstan',	'LA' => 'Lao People\'s Democratic Republic',	'LV' => 'Latvia',	'LB' => 'Lebanon',	'LS' => 'Lesotho',	'LR' => 'Liberia',	'LY' => 'Libyan Arab Jamahiriya',	'LI' => 'Liechtenstein',	'LT' => 'Lithuania',	'LU' => 'Luxembourg',	'MO' => 'Macao',	'MK' => 'Macedonia',	'MG' => 'Madagascar',	'MW' => 'Malawi',	'MY' => 'Malaysia',	'MV' => 'Maldives',	'ML' => 'Mali',	'MT' => 'Malta',	'MH' => 'Marshall Islands',	'MQ' => 'Martinique',	'MR' => 'Mauritania',	'MU' => 'Mauritius',	'YT' => 'Mayotte',	'MX' => 'Mexico',	'FM' => 'Micronesia, Federated States Of',	'MD' => 'Moldova',	'MC' => 'Monaco',	'MN' => 'Mongolia',	'ME' => 'Montenegro',	'MS' => 'Montserrat',	'MA' => 'Morocco',	'MZ' => 'Mozambique',	'MM' => 'Myanmar',	'NA' => 'Namibia',	'NR' => 'Nauru',	'NP' => 'Nepal',	'NL' => 'Netherlands',	'AN' => 'Netherlands Antilles',	'NC' => 'New Caledonia',	'NZ' => 'New Zealand',	'NI' => 'Nicaragua',	'NE' => 'Niger',	'NG' => 'Nigeria',	'NU' => 'Niue',	'NF' => 'Norfolk Island',	'MP' => 'Northern Mariana Islands',	'NO' => 'Norway',	'OM' => 'Oman',	'PK' => 'Pakistan',	'PW' => 'Palau',	'PS' => 'Palestinian Territory, Occupied',	'PA' => 'Panama',	'PG' => 'Papua New Guinea',	'PY' => 'Paraguay',	'PE' => 'Peru',	'PH' => 'Philippines',	'PN' => 'Pitcairn',	'PL' => 'Poland',	'PT' => 'Portugal',	'PR' => 'Puerto Rico',	'QA' => 'Qatar',	'RE' => 'Reunion',	'RO' => 'Romania',	'RU' => 'Russian Federation',	'RW' => 'Rwanda',	'BL' => 'Saint Barthelemy',	'SH' => 'Saint Helena',	'KN' => 'Saint Kitts And Nevis',	'LC' => 'Saint Lucia',	'MF' => 'Saint Martin',	'PM' => 'Saint Pierre And Miquelon',	'VC' => 'Saint Vincent And Grenadines',	'WS' => 'Samoa',	'SM' => 'San Marino',	'ST' => 'Sao Tome And Principe',	'SA' => 'Saudi Arabia',	'SN' => 'Senegal',	'RS' => 'Serbia',	'SC' => 'Seychelles',	'SL' => 'Sierra Leone',	'SG' => 'Singapore',	'SK' => 'Slovakia',	'SI' => 'Slovenia',	'SB' => 'Solomon Islands',	'SO' => 'Somalia',	'ZA' => 'South Africa',	'GS' => 'South Georgia And Sandwich Isl.',	'ES' => 'Spain',	'LK' => 'Sri Lanka',	'SD' => 'Sudan',	'SR' => 'Suriname',	'SJ' => 'Svalbard And Jan Mayen',	'SZ' => 'Swaziland',	'SE' => 'Sweden',	'CH' => 'Switzerland',	'SY' => 'Syrian Arab Republic',	'TW' => 'Taiwan',	'TJ' => 'Tajikistan',	'TZ' => 'Tanzania',	'TH' => 'Thailand',	'TL' => 'Timor-Leste',	'TG' => 'Togo',	'TK' => 'Tokelau',	'TO' => 'Tonga',	'TT' => 'Trinidad And Tobago',	'TN' => 'Tunisia',	'TR' => 'Turkey',	'TM' => 'Turkmenistan',	'TC' => 'Turks And Caicos Islands',	'TV' => 'Tuvalu',	'UG' => 'Uganda',	'UA' => 'Ukraine',	'AE' => 'United Arab Emirates',	'GB' => 'United Kingdom',	'US' => 'United States',	'UM' => 'United States Outlying Islands',	'UY' => 'Uruguay',	'UZ' => 'Uzbekistan',	'VU' => 'Vanuatu',	'VE' => 'Venezuela',	'VN' => 'Viet Nam',	'VG' => 'Virgin Islands, British',	'VI' => 'Virgin Islands, U.S.',	'WF' => 'Wallis And Futuna',	'EH' => 'Western Sahara',	'YE' => 'Yemen',	'ZM' => 'Zambia',	'ZW' => 'Zimbabwe',);
		return $countries;
	}

}
