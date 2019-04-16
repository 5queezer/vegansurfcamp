<?php
class wpdevart_bc_ControllerReservations {
	private $model;	
	private $view;	
	  
	public function __construct() {
		require_once(WPDEVART_PLUGIN_DIR . "/admin/models/Reservations.php");
		$this->model = new wpdevart_bc_ModelReservations();
		require_once(WPDEVART_PLUGIN_DIR . "/admin/views/Reservations.php");
		$this->view = new wpdevart_bc_ViewReservations($this->model);
	}  	
	  
	public function perform() {
		$task = wpdevart_bc_Library::get_value('task');
		$id = wpdevart_bc_Library::get_value('id', 0);
		$action = wpdevart_bc_Library::get_value('action');
		if (method_exists($this, $task)) {
		  $this->$task($id);
		}
		else {
		  $this->display_reservations();
		}
	}
	
	private function display_reservations($id=0,$send_mail = array()){
		$this->view->display_reservations($id,$send_mail);
	}  
	
	private function display_month_reservations(){
		$this->view->display_month_reservations();
	}
  	
	private function edit( $id ){
		$this->view->edit( $id );
	}
	
	private function delete( $id ){
		global $wpdb; 
		$send_mail = array();
		$res_status = $this->model->get_reservation_row( $id );
		$delete_res = $wpdb->query($wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'wpdevart_reservations WHERE id="%d"',$id ));
		if($delete_res) {
			if($res_status["status"] == "approved") {
				$this->change_date_avail_count( $id, false, $res_status );
			}
			if($res_status["status"] != 'rejected'){
				$send_mail = $this->send_mail($id,"deleted", $res_status);
			}
		}
		$this->display_reservations(0,$send_mail);
	}
	
	private function add() {
		$this->view->add();
	}
	
	private function save($id){
		if(isset($_POST["wpdevart-submit".$id])){
			$booking_obg = new wpdevart_bc_calendar();
			$result = $booking_obg->wpdevart_booking_calendar($_SESSION["clendar_id"]);
			echo $result;
		}
		$this->display_reservations();
	}

	private function delete_selected(){
		global $wpdb; 
		$send_mail = array();
		$check_for_action = (isset($_POST['check_for_action']) ? ( $_POST['check_for_action']) : '');
		foreach($check_for_action as $check){
			$res_status = $this->model->get_reservation_row( $check );
			$delete_res = $wpdb->query($wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'wpdevart_reservations WHERE id="%d"',$check ));
			if($delete_res) {
				if($res_status["status"] == "approved") {
					$this->change_date_avail_count( $check, false, $res_status );
				}
				if($res_status["status"] != 'rejected'){
					$send_mail = $this->send_mail($check,"deleted", $res_status);
				}
			}
		}
		$this->display_reservations(0,$send_mail);
	}
	
	private function approve_selected(){
		global $wpdb; 
		$send_mail = array();
		$check_for_action = (isset($_POST['check_for_action']) ? ( $_POST['check_for_action']) : '');
		foreach($check_for_action as $check){
			$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
				array('status' => "approved",
					  'is_new' => 0	),
				array('id' => $check),
				array('%s')
			);
			if($update_res) {
				$this->change_date_avail_count( $check, true );
				$send_mail = $this->send_mail($check,"approved");
			}
		}

		$this->display_reservations(0,$send_mail);
	}
	
	private function canceled_selected(){
		global $wpdb; 
		$send_mail = array();
		$check_for_action = (isset($_POST['check_for_action']) ? ( $_POST['check_for_action']) : '');
		foreach($check_for_action as $check){
			$res_status = $this->model->get_reservation_row( $check );
			$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
				array('status' => "canceled",
					  'is_new' => 0),
				array('id' => $check),
				array('%s')
			);
			if($update_res) {
				if($res_status["status"] == "approved") {
					$this->change_date_avail_count( $check, false );
				}
				$send_mail = $this->send_mail($check,"canceled");
			}
		}
		$this->display_reservations(0,$send_mail);
	}
	
	private function reject_selected(){
		global $wpdb; 
		$send_mail = array();
		$check_for_action = (isset($_POST['check_for_action']) ? ( $_POST['check_for_action']) : '');
		foreach($check_for_action as $check){
			$res_status = $this->model->get_reservation_row( $check );
			$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
				array('status' => "rejected",
					  'is_new' => 0),
				array('id' => $check),
				array('%s')
			);
			if($update_res) {
				if($res_status["status"] == "approved") {
					$this->change_date_avail_count( $check, false );
				}
				$send_mail = $this->send_mail($check,"rejected");
			}
		}
		$this->display_reservations(0,$send_mail);
	}
	
	
	private function approve( $id ){
		global $wpdb; 
		$send_mail = array();
		$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
            array('status' => "approved",
				  'is_new' => 0),
            array('id' => $id),
            array('%s')
        );
		if($update_res) {
			$this->change_date_avail_count( $id, true );
			$send_mail = $this->send_mail($id,"approved");
		}
		
		$this->display_reservations(0,$send_mail);
	}
	
	private function canceled( $id ){
		global $wpdb; 
		$send_mail = array();  
		$res_status = $this->model->get_reservation_row( $id );
		$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
            array('status' => "canceled",
				  'is_new' => 0),
            array('id' => $id),
            array('%s')
        );
		if($update_res) {
			if($res_status["status"] == "approved") {
				$this->change_date_avail_count( $id, false );
			}
			$send_mail = $this->send_mail($id,"canceled");
		}
		$this->display_reservations(0,$send_mail);
	}
	private function reject( $id ){
		global $wpdb;
		$send_mail =  array();		
		$res_status = $this->model->get_reservation_row( $id );
		$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
            array('status' => "rejected",
				  'is_new' => 0),
            array('id' => $id),
            array('%s')
        );
		if($update_res) {
			if($res_status["status"] == "approved") {
				$this->change_date_avail_count( $id, false );
			}
			$send_mail = $this->send_mail($id,"rejected");
		}
		$this->display_reservations(0,$send_mail);
	}
	private function mark_read( $id ){
		global $wpdb;
		$update_res = $wpdb->update($wpdb->prefix . 'wpdevart_reservations',
            array('is_new' => 0),
            array('id' => $id),
            array('%s')
        );
		$this->display_reservations(0,array());
	}
	private function mark_read_selected( $id ){
		global $wpdb;
		if(isset($_POST['check_for_action'])){
			$check_for_action = (isset($_POST['check_for_action']) ? ( $_POST['check_for_action']) : '');
			$check_ids = implode(",",$check_for_action);
			$update_res = $wpdb->query('UPDATE '.$wpdb->prefix . 'wpdevart_reservations SET is_new=0 WHERE id IN ('.$check_ids.')');
		}
		$this->display_reservations(0,array());
	}
	public function export_as_csv(){
		global $wpdb;
		$reservations = $this->model->get_reservations_for_export();
		
		$form_id = $wpdb->get_var($wpdb->prepare('SELECT form_id FROM ' . $wpdb->prefix . 'wpdevart_calendars WHERE id="%d"', $_SESSION["clendar_id"]));
		$form_info = $wpdb->get_var($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_forms WHERE id="%d"', $form_id));
		$extra_id = $wpdb->get_var($wpdb->prepare('SELECT extra_id FROM ' . $wpdb->prefix . 'wpdevart_calendars WHERE id="%d"', $_SESSION["clendar_id"]));
		$extra_info = $wpdb->get_var($wpdb->prepare('SELECT data FROM ' . $wpdb->prefix . 'wpdevart_extras WHERE id="%d"', $extra_id));
		$res = $this->model->get_reservations_rows(0,"ARRAY_A");
		$currency = isset($res[0]) ? $res[0]["currency"] : "$";
		
		if ( !empty($reservations)) {
			$title = $this->model->get_calendar_title();
			$reservations = $this->model->get_form_data_new($reservations,$res,$form_info);
			$reservations = $this->model->get_extra_data_new($reservations,$res,$extra_info,$currency);
			$form_lables = $this->model->get_labels($form_info);
			$extra_lables = $this->model->get_labels($extra_info);
			$col_names = array_merge(array(
						  "id" => "ID",
						  "calendar_id" => "Calendar ID",
						  "single_day" => "Single Day",
						  "check_in" => "Check In",
						  "check_out" => "Check Out",
						  "start_hour" => "Start Hour",
						  "end_hour" => "End Hour",
						  "count_item" => "Count Item",
						  "price" => "Price",
						  "total_price" => "Total Price",
						  "extras_price" =>  "Extras Price",
						  "status" => "Status",
						  "payment_method" => "Payment Method",
						  "payment_status" => "Payment Status",
						  "date_created" => "Created Date"
						),$form_lables,$extra_lables);

			$upload_direct = wp_upload_dir();
			$booking_path = $upload_direct['basedir'] . '/booking_calendar';
			if ( !is_dir($booking_path) ) {
				mkdir($booking_path, 0777);
			}
			$temp = $booking_path . '/' . $title . '.txt';
			if ( file_exists($temp) ) {
				unlink($temp);
			}
			$output = fopen($temp, "a");

			foreach ($col_names as $i => $name) {
				$col_names[$i] = ltrim($name, '=+-@');
			}
			fputcsv($output, $col_names, ",");

			foreach ( $reservations as $index => $record ) {
				foreach ( $record as $i => $rec ) {
					$record[$i] = ltrim($rec, '=+-@');
				}
				fputcsv($output, $record, ",");
			}
			
			fclose($output); 
			$txtfile = fopen($temp, "r");
			$txtfilecontent = fread($txtfile, filesize($temp));
			fclose($txtfile);
			$filename = $title . "_" . date('Ymd') . ".csv";
			header('Content-Encoding: UTF-8');
			header('content-type: application/csv; charset=UTF-8');
			header("Content-Disposition: attachment; filename=\"$filename\"");
			echo "\xEF\xBB\xBF";
			echo $txtfilecontent;
			unlink($temp);
		
		}
	}
	
	public function change_date_avail_count( $id,$approve,$reserv_info_del="" ){
		global $wpdb;
		$theme_rows = $this->model->get_theme_rows();
		if($reserv_info_del == "") {
			$reserv_info = $this->model->get_reservation_row($id);
		} else {
			$reserv_info = $reserv_info_del;
		}		
		$cal_id = $reserv_info["calendar_id"]; 
		if(isset($reserv_info["count_item"])) {
			$count_item = $reserv_info["count_item"];
		} else {
			$count_item = 1;
		}
		if($reserv_info["single_day"] == "") {
			$start_date = $reserv_info["check_in"];
			$date_diff = abs($this->get_date_diff($reserv_info["check_in"],$reserv_info["check_out"]));
			for($i=0; $i <= $date_diff; $i++) {
				if(isset($theme_rows["price_for_night"]) && $theme_rows["price_for_night"] == "on"  && $i == $date_diff){
					continue;
				}
				$day = date( 'Y-m-d', strtotime($start_date. " +" . $i . " day" ));
				$unique_id = $cal_id."_".$day;
				$day_data = json_decode($this->model->get_date_data( $unique_id ),true);
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
			$day_data = json_decode($this->model->get_date_data( $unique_id ),true);
			if($approve === true) {
				if($reserv_info["end_hour"] == "" && $reserv_info["start_hour"] == "") {
					$day_data["available"] = $day_data["available"] - $count_item;
				} else {
					if(isset($day_data["hours"])){
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
	
	private function send_mail($res_id,$type,$reserv_info_del=""){
		
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		$countries = self::get_countries();
		$email_array = array();
		$admin_email_types = array();
		$user_email_types = array();
		$admin_error_types = array();
		$user_error_types = array();
		$hour_html = "";
		$calendar_title = $this->model->get_calendar_title();
		if($reserv_info_del == "") {
			$form_result = $this->model->get_reservation_row($res_id);
		} else {
			$form_result = $reserv_info_del;
		}
		$theme_rows = $this->model->get_theme_rows($form_result["calendar_id"]);
		$form_datas = $this->model->get_form_data($form_result["form"]);
        $extras_data = $this->model->get_extra_data($form_result["extras"],"mail",$form_result["price"]);
		if($form_result["check_in"]) {
			$check_in = date($theme_rows["date_format"], strtotime($form_result["check_in"]));
			$check_out = date($theme_rows["date_format"], strtotime($form_result["check_out"]));
			$res_day = $check_in. "-" .$check_out;
		} else {
			$res_day = date($theme_rows["date_format"], strtotime($form_result["single_day"]));
		}
		$cur_pos = (isset($theme_rows['currency_pos']) && $theme_rows['currency_pos'] == "before") ? "before" : "after";
		$hide_price = (isset($theme_rows['hide_price']) && $theme_rows['hide_price'] == "on") ? true : false;
		if(isset($form_result["start_hour"]) && $form_result["start_hour"] != ""){
			$hour_html = $form_result["start_hour"];
		}
		if(isset($form_result["end_hour"]) && $form_result["end_hour"] != ""){
			$hour_html = $hour_html." - ".$form_result["end_hour"];
		}
		if($hour_html != ""){
			$hour_html = "<tr><td style='padding: 1px 7px;'>".__('Hour','booking-calendar')."</td> <td  style='padding: 1px 7px;'>".$hour_html.'</td></tr>';
		}
		$site_url = site_url();
		$moderate_link = admin_url() . "admin.php?page=wpdevart-reservations";
		$res_info = "<table border='1' style='border-collapse:collapse;min-width: 360px;'>
						<caption style='text-align:left;'>".__('Details','booking-calendar')."</caption>
						<tr><td style='padding: 1px 7px;'>".__('Reservation dates','booking-calendar')."</td><td style='padding: 1px 7px;'>".$res_day."</td></tr>".$hour_html."
						<tr><td style='padding: 1px 7px;'>".__('Item Count','booking-calendar')."</td><td style='padding: 1px 7px;'>".$form_result["count_item"]."</td></tr>";
		if($form_result["price"] != "NaN" && !$hide_price){
			$res_info .= "<tr><td style='padding: 1px 7px;'>".__('Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".(($cur_pos == "before" ? $form_result["currency"] : '') . $form_result["price"] . ($cur_pos == "after" ? $form_result["currency"] : ''))."</td></tr>";
		}
		if($form_result["total_price"] != "NaN" && !$hide_price){
			/*Sale percent*/
			$sale_percent_html = "";
			if(isset($form_result["sale_percent"]) && !empty($form_result["sale_percent"])){
				if($form_result["sale_type"] == "percent"){
					$sale_percent_value = ($form_result["total_price"] * 100) / (100 - $form_result["sale_percent"]);
					$sale_percent_html = (($cur_pos == "before" ? $form_result["currency"] : '') . $sale_percent_value . ($cur_pos == "after" ? $form_result["currency"] : '')) . " - " . $form_result["sale_percent"] . "% = ";
				} else{
					$sale_percent_value = $form_result["total_price"] + $form_result["sale_percent"];
					$sale_percent_html = (($cur_pos == "before" ? $form_result["currency"] : '') . $sale_percent_value . ($cur_pos == "after" ? $form_result["currency"] : '')) . " - " . ($cur_pos == "before" ? $form_result["currency"] : '') . $form_result["sale_percent"] . ($cur_pos == "after" ? $form_result["currency"] : '') ." = ";
				}
			}
			$res_info .= "<tr><td style='padding: 1px 7px;'>".__('Total Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".$sale_percent_html . (($cur_pos == "before" ? $form_result["currency"] : '') . $form_result["total_price"] . ($cur_pos == "after" ? $form_result["currency"] : ''))."</td></tr>";
		}
		$res_info .= "</table>";
		$form = "";
		$extras = "";		
		if(count($form_datas)) {
			$form .= "<table border='1' style='border-collapse:collapse;min-width: 360px;'>";
			$form .= "<caption style='text-align:left;'>" . __('Contact Information','booking-calendar')."</caption>";
			foreach($form_datas as $form_fild_data) {
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
					$extras .= "<span class='price'>".$extra_data["operation"] . (($cur_pos == "before" ? $form_result["currency"] : '') . (isset($extra_data["price"])? $extra_data["price"] : "") . ($cur_pos == "after" ? $form_result["currency"] : ''))."</span></td></tr>";
				} else {
					$extras .= "<span class='price'>".$extra_data["operation"] . (($cur_pos == "before" ? $form_result["currency"] : '') . $extra_data["price"] . ($cur_pos == "after" ? $form_result["currency"] : ''))."</span></td></tr>";
				}
				
			}
			$extras .= "<tr><td style='padding: 1px 7px;'>".__('Price change','booking-calendar')."</td><td style='padding: 1px 7px;'>+".(($cur_pos == "before" ? $form_result["currency"] : '') . $form_result["extras_price"] . ($cur_pos == "after" ? $form_result["currency"] : ''))."</td></tr>";
			$extras .= "</table>";
		}
		$emails = $form_result["email"];
		
		if(isset($theme_rows['notify_admin_on_book']) && $theme_rows['notify_admin_on_book'] == "on" && $type == "book") {
			$admin_email_types[] = 'notify_admin_on_book';
		}
		if(isset($theme_rows['notify_admin_on_approved']) && $theme_rows['notify_admin_on_approved'] == "on" && $type == "approved") {
			$admin_email_types[] = 'notify_admin_on_approved';
		}
		/*if(isset($theme_rows['notify_admin_paypal']) && $theme_rows['notify_admin_paypal'] == "on" && $type == "admin_paypal") {
			$admin_email_types[] = 'notify_admin_paypal';
		}*/
		
		
		if(isset($theme_rows['notify_user_on_book']) && $theme_rows['notify_user_on_book'] == "on" && $type == "book") {
			$user_email_types[] = 'notify_user_on_book';
		}
		if(isset($theme_rows['notify_user_on_approved']) && $theme_rows['notify_user_on_approved'] == "on" && $type == "approved") {
			$user_email_types[] = 'notify_user_on_approved';
		}
		if(isset($theme_rows['notify_user_canceled']) && $theme_rows['notify_user_canceled'] == "on" && $type == "canceled") {
			$user_email_types[] = 'notify_user_canceled';
		}
		if(isset($theme_rows['notify_user_deleted']) && $theme_rows['notify_user_deleted'] == "on" && ($type == "deleted" ||  $type == "rejected")) {
			$user_email_types[] = 'notify_user_deleted';
		}
		/*if(isset($theme_rows['notify_user_paypal']) && $theme_rows['notify_user_paypal'] == "on" && $type == "user_paypal") {
			$user_email_types[] = 'notify_user_paypal';
		}*/
		/*Email to admin on approved*/
		if(count($admin_email_types)) {	
			foreach($admin_email_types as $admin_email_type) {
				$to = "";
				$from = "";
				$fromname = "";
				$subject = "";
				$content = "";
				if(isset($theme_rows[$admin_email_type.'_to']) && $theme_rows[$admin_email_type.'_to'] != "") {
					$to = stripslashes($theme_rows[$admin_email_type.'_to']);
				}
				if(isset($theme_rows[$admin_email_type.'_fromname']) && $theme_rows[$admin_email_type.'_fromname'] != "") {
					$fromname = stripslashes($theme_rows[$admin_email_type.'_fromname']);
				}
				if(isset($theme_rows[$admin_email_type.'_subject']) && $theme_rows[$admin_email_type.'_subject'] != "") {
					$subject = stripslashes($theme_rows[$admin_email_type.'_subject']);
				}
				if(isset($theme_rows[$admin_email_type.'_content']) && $theme_rows[$admin_email_type.'_content'] != "") {
					$content = stripslashes($theme_rows[$admin_email_type.'_content']);
					$content = str_replace("[calendartitle]", $calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[moderatelink]", $moderate_link, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $form_result["total_price"], $content);
					$mail_content = "<div class='wpdevart_email' style='text-align: center;color:".((isset($theme_rows['mail_color']) && $theme_rows['mail_color'] != "") ? $theme_rows['mail_color'] : "#5A5A5A")." !important;background-color:".((isset($theme_rows['mail_bg']) && $theme_rows['mail_bg'] != "") ? $theme_rows['mail_bg'] : "#e8e8f7")." !important;line-height: 1.5;'>";
					if(isset($theme_rows['mail_header_img']) && $theme_rows['mail_header_img'] != ""){
						$mail_content .= "<img src='".esc_url($theme_rows['mail_header_img'])."' style='max-width:670px;margin:20px auto 0;'>";
					}
					$mail_content .= "<div style='width: 670px;margin: 0 auto;padding: 15px;background-color:" .((isset($theme_rows['mail_content_bg']) && $theme_rows['mail_content_bg'] != "") ? $theme_rows['mail_content_bg'] : "#e8e8f7")." !important;'>".$content."</div>";
					if(isset($theme_rows['mail_footer_text']) && $theme_rows['mail_footer_text'] != ""){
						$mail_content .= "<p style='color:" .((isset($theme_rows['mail_footer_text_color']) && $theme_rows['mail_footer_text_color'] != "") ? $theme_rows['mail_footer_text_color'] : "#a7a7a7")." !important;padding: 10px 0; font-size: 13px;'>".$theme_rows['mail_footer_text']."</p>";
					}
					$mail_content .= "</div>";
				}
				if(isset($theme_rows[$admin_email_type.'_from']) && $theme_rows[$admin_email_type.'_from'] != "") {
					if(isset($theme_rows['use_phpmailer']) && $theme_rows['use_phpmailer'] == "on"){
						if(trim($theme_rows[$admin_email_type.'_from']) == "[useremail]") {
							$from = $emails;
						} else {
							$from = $theme_rows[$admin_email_type.'_from'];
						}
					} else {
						if(trim($theme_rows[$admin_email_type.'_from']) == "[useremail]") {
							$from = "From: '" . $fromname . "' <" . $emails . ">" . "\r\n";
						} else {
							$from = "From: '" . $fromname . "' <" . stripslashes($theme_rows[$admin_email_type.'_from']) . ">" . "\r\n";
						}
					}
				}
				if(isset($theme_rows['use_phpmailer']) && $theme_rows['use_phpmailer'] == "on"){
					$mail_to_send = new PHPMailer();
					$mail_to_send->CharSet  = get_option('blog_charset');
					$mail_to_send->FromName = $fromname;
					$mail_to_send->From     = $from;
					$mail_to_send->Subject  = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $subject ) ));
					$mail_to_send->Body 	= $mail_content ;
					if(!$mail_to_send->Body) {	
						$mail_to_send->Body = $mail_to_send->FromName ." sent you this email";
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
					$admin_error_types[$admin_email_type] = wp_mail($to, $subject, $mail_content, $headers);
				}
			}	
		}	
			/*Email to user on approved*/
		if(count($user_email_types)) {	
			foreach($user_email_types as $user_email_type) {	
				$from = "";
				$fromname = "";
				$subject = "";
				$content = "";
				$to = $emails;
				if(isset($theme_rows[$user_email_type.'_subject']) && $theme_rows[$user_email_type.'_subject'] != "") {
					$subject = stripslashes($theme_rows[$user_email_type.'_subject']);
				}
				if(isset($theme_rows[$user_email_type.'_fromname']) && $theme_rows[$user_email_type.'_fromname'] != "") {
					$fromname = stripslashes($theme_rows[$user_email_type.'_fromname']);
				}
				if(isset($theme_rows[$user_email_type.'_content']) && $theme_rows[$user_email_type.'_content'] != "") {
					$content = stripslashes($theme_rows[$user_email_type.'_content']);
					$content = str_replace("[calendartitle]", $calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $form_result["total_price"], $content);
					$mail_content = "<div class='wpdevart_email' style='text-align: center;color:".((isset($theme_rows['mail_color']) && $theme_rows['mail_color'] != "") ? $theme_rows['mail_color'] : "#5A5A5A")." !important;background-color:".((isset($theme_rows['mail_bg']) && $theme_rows['mail_bg'] != "") ? $theme_rows['mail_bg'] : "#e8e8f7")." !important;line-height: 1.5;'>";
					if(isset($theme_rows['mail_header_img']) && $theme_rows['mail_header_img'] != ""){
						$mail_content .= "<img src='".esc_url($theme_rows['mail_header_img'])."' style='max-width:670px;margin:20px auto 0;'>";
					}
					$mail_content .= "<div style='width: 670px;margin: 0 auto;padding: 15px;background-color:" .((isset($theme_rows['mail_content_bg']) && $theme_rows['mail_content_bg'] != "") ? $theme_rows['mail_content_bg'] : "#e8e8f7")." !important;'>".$content."</div>";
					if(isset($theme_rows['mail_footer_text']) && $theme_rows['mail_footer_text'] != ""){
						$mail_content .= "<p style='color:" .((isset($theme_rows['mail_footer_text_color']) && $theme_rows['mail_footer_text_color'] != "") ? $theme_rows['mail_footer_text_color'] : "#a7a7a7")." !important;padding: 10px 0; font-size: 13px;'>".$theme_rows['mail_footer_text']."</p>";
					}
					$mail_content .= "</div>";
				}
				if(isset($theme_rows[$user_email_type.'_from']) && $theme_rows[$user_email_type.'_from'] != "") {
					if(isset($theme_rows['use_phpmailer']) && $theme_rows['use_phpmailer'] == "on"){
						$from = $theme_rows[$user_email_type.'_from'];
					} else {
						$from = "From: '" . $fromname . "' <" . stripslashes($theme_rows[$user_email_type.'_from']) . ">" . "\r\n";
					}
				}
				if(isset($theme_rows['use_phpmailer']) && $theme_rows['use_phpmailer'] == "on"){
					$mail_to_send = new PHPMailer();
					$mail_to_send->CharSet = 'UTF-8';
					$mail_to_send->FromName = $fromname;
					$mail_to_send->From     = $from;
					$mail_to_send->Subject  = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $subject ) ));
					$mail_to_send->Body 	= $mail_content;
					if(!$mail_to_send->Body) {	
						$mail_to_send->Body = $mail_to_send->FromName ." sent you this email";
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
					$user_error_types[$user_email_type] = wp_mail($to, $subject, $mail_content, $headers);
				}
			}
		}
		$result = array($admin_error_types,$user_error_types);
		return 	$result;	
	}

 
  	private function get_date_diff($date1, $date2) {
		$start = strtotime($date1);
		$end = strtotime($date2);
		$datediff = $start - $end;
		return floor($datediff/(60*60*24));
	}

	public static function get_countries(){
		$countries = array('' => __('Select Country','booking-calendar'),	'AF' => 'Afghanistan',	'AX' => 'Aland Islands',	'AL' => 'Albania',	'DZ' => 'Algeria',	'AS' => 'American Samoa',	'AD' => 'Andorra',	'AO' => 'Angola',	'AI' => 'Anguilla',	'AQ' => 'Antarctica',	'AG' => 'Antigua And Barbuda',	'AR' => 'Argentina',	'AM' => 'Armenia',	'AW' => 'Aruba',	'AU' => 'Australia',	'AT' => 'Austria',	'AZ' => 'Azerbaijan',	'BS' => 'Bahamas',	'BH' => 'Bahrain',	'BD' => 'Bangladesh',	'BB' => 'Barbados',	'BY' => 'Belarus',	'BE' => 'Belgium',	'BZ' => 'Belize',	'BJ' => 'Benin',	'BM' => 'Bermuda',	'BT' => 'Bhutan',	'BO' => 'Bolivia',	'BA' => 'Bosnia And Herzegovina',	'BW' => 'Botswana',	'BV' => 'Bouvet Island',	'BR' => 'Brazil',	'IO' => 'British Indian Ocean Territory',	'BN' => 'Brunei Darussalam',	'BG' => 'Bulgaria',	'BF' => 'Burkina Faso',	'BI' => 'Burundi',	'KH' => 'Cambodia',	'CM' => 'Cameroon',	'CA' => 'Canada',	'CV' => 'Cape Verde',	'KY' => 'Cayman Islands',	'CF' => 'Central African Republic',	'TD' => 'Chad',	'CL' => 'Chile',	'CN' => 'China',	'CX' => 'Christmas Island',	'CC' => 'Cocos (Keeling) Islands',	'CO' => 'Colombia',	'KM' => 'Comoros',	'CG' => 'Congo',	'CD' => 'Congo, Democratic Republic',	'CK' => 'Cook Islands',	'CR' => 'Costa Rica',	'CI' => 'Cote D\'Ivoire',	'HR' => 'Croatia',	'CU' => 'Cuba',	'CY' => 'Cyprus',	'CZ' => 'Czech Republic',	'DK' => 'Denmark',	'DJ' => 'Djibouti',	'DM' => 'Dominica',	'DO' => 'Dominican Republic',	'EC' => 'Ecuador',	'EG' => 'Egypt',	'SV' => 'El Salvador',	'GQ' => 'Equatorial Guinea',	'ER' => 'Eritrea',	'EE' => 'Estonia',	'ET' => 'Ethiopia',	'FK' => 'Falkland Islands (Malvinas)',	'FO' => 'Faroe Islands',	'FJ' => 'Fiji',	'FI' => 'Finland',	'FR' => 'France',	'GF' => 'French Guiana',	'PF' => 'French Polynesia',	'TF' => 'French Southern Territories',	'GA' => 'Gabon',	'GM' => 'Gambia',	'GE' => 'Georgia',	'DE' => 'Germany',	'GH' => 'Ghana',	'GI' => 'Gibraltar',	'GR' => 'Greece',	'GL' => 'Greenland',	'GD' => 'Grenada',	'GP' => 'Guadeloupe',	'GU' => 'Guam',	'GT' => 'Guatemala',	'GG' => 'Guernsey',	'GN' => 'Guinea',	'GW' => 'Guinea-Bissau',	'GY' => 'Guyana',	'HT' => 'Haiti',	'HM' => 'Heard Island & Mcdonald Islands',	'VA' => 'Holy See (Vatican City State)',	'HN' => 'Honduras',	'HK' => 'Hong Kong',	'HU' => 'Hungary',	'IS' => 'Iceland',	'IN' => 'India',	'ID' => 'Indonesia',	'IR' => 'Iran, Islamic Republic Of',	'IQ' => 'Iraq',	'IE' => 'Ireland',	'IM' => 'Isle Of Man',	'IL' => 'Israel',	'IT' => 'Italy',	'JM' => 'Jamaica',	'JP' => 'Japan',	'JE' => 'Jersey',	'JO' => 'Jordan',	'KZ' => 'Kazakhstan',	'KE' => 'Kenya',	'KI' => 'Kiribati',	'KR' => 'Korea',	'KW' => 'Kuwait',	'KG' => 'Kyrgyzstan',	'LA' => 'Lao People\'s Democratic Republic',	'LV' => 'Latvia',	'LB' => 'Lebanon',	'LS' => 'Lesotho',	'LR' => 'Liberia',	'LY' => 'Libyan Arab Jamahiriya',	'LI' => 'Liechtenstein',	'LT' => 'Lithuania',	'LU' => 'Luxembourg',	'MO' => 'Macao',	'MK' => 'Macedonia',	'MG' => 'Madagascar',	'MW' => 'Malawi',	'MY' => 'Malaysia',	'MV' => 'Maldives',	'ML' => 'Mali',	'MT' => 'Malta',	'MH' => 'Marshall Islands',	'MQ' => 'Martinique',	'MR' => 'Mauritania',	'MU' => 'Mauritius',	'YT' => 'Mayotte',	'MX' => 'Mexico',	'FM' => 'Micronesia, Federated States Of',	'MD' => 'Moldova',	'MC' => 'Monaco',	'MN' => 'Mongolia',	'ME' => 'Montenegro',	'MS' => 'Montserrat',	'MA' => 'Morocco',	'MZ' => 'Mozambique',	'MM' => 'Myanmar',	'NA' => 'Namibia',	'NR' => 'Nauru',	'NP' => 'Nepal',	'NL' => 'Netherlands',	'AN' => 'Netherlands Antilles',	'NC' => 'New Caledonia',	'NZ' => 'New Zealand',	'NI' => 'Nicaragua',	'NE' => 'Niger',	'NG' => 'Nigeria',	'NU' => 'Niue',	'NF' => 'Norfolk Island',	'MP' => 'Northern Mariana Islands',	'NO' => 'Norway',	'OM' => 'Oman',	'PK' => 'Pakistan',	'PW' => 'Palau',	'PS' => 'Palestinian Territory, Occupied',	'PA' => 'Panama',	'PG' => 'Papua New Guinea',	'PY' => 'Paraguay',	'PE' => 'Peru',	'PH' => 'Philippines',	'PN' => 'Pitcairn',	'PL' => 'Poland',	'PT' => 'Portugal',	'PR' => 'Puerto Rico',	'QA' => 'Qatar',	'RE' => 'Reunion',	'RO' => 'Romania',	'RU' => 'Russian Federation',	'RW' => 'Rwanda',	'BL' => 'Saint Barthelemy',	'SH' => 'Saint Helena',	'KN' => 'Saint Kitts And Nevis',	'LC' => 'Saint Lucia',	'MF' => 'Saint Martin',	'PM' => 'Saint Pierre And Miquelon',	'VC' => 'Saint Vincent And Grenadines',	'WS' => 'Samoa',	'SM' => 'San Marino',	'ST' => 'Sao Tome And Principe',	'SA' => 'Saudi Arabia',	'SN' => 'Senegal',	'RS' => 'Serbia',	'SC' => 'Seychelles',	'SL' => 'Sierra Leone',	'SG' => 'Singapore',	'SK' => 'Slovakia',	'SI' => 'Slovenia',	'SB' => 'Solomon Islands',	'SO' => 'Somalia',	'ZA' => 'South Africa',	'GS' => 'South Georgia And Sandwich Isl.',	'ES' => 'Spain',	'LK' => 'Sri Lanka',	'SD' => 'Sudan',	'SR' => 'Suriname',	'SJ' => 'Svalbard And Jan Mayen',	'SZ' => 'Swaziland',	'SE' => 'Sweden',	'CH' => 'Switzerland',	'SY' => 'Syrian Arab Republic',	'TW' => 'Taiwan',	'TJ' => 'Tajikistan',	'TZ' => 'Tanzania',	'TH' => 'Thailand',	'TL' => 'Timor-Leste',	'TG' => 'Togo',	'TK' => 'Tokelau',	'TO' => 'Tonga',	'TT' => 'Trinidad And Tobago',	'TN' => 'Tunisia',	'TR' => 'Turkey',	'TM' => 'Turkmenistan',	'TC' => 'Turks And Caicos Islands',	'TV' => 'Tuvalu',	'UG' => 'Uganda',	'UA' => 'Ukraine',	'AE' => 'United Arab Emirates',	'GB' => 'United Kingdom',	'US' => 'United States',	'UM' => 'United States Outlying Islands',	'UY' => 'Uruguay',	'UZ' => 'Uzbekistan',	'VU' => 'Vanuatu',	'VE' => 'Venezuela',	'VN' => 'Viet Nam',	'VG' => 'Virgin Islands, British',	'VI' => 'Virgin Islands, U.S.',	'WF' => 'Wallis And Futuna',	'EH' => 'Western Sahara',	'YE' => 'Yemen',	'ZM' => 'Zambia',	'ZW' => 'Zimbabwe',);
		return $countries;
	}
}

?>