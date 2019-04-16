<?php
class wpdevart_bc_ControllerPayments {
	private $model;	
	private $theme_option;	
	private $res_edit;
	  
	public function __construct() {
		require_once(WPDEVART_PLUGIN_DIR . "/admin/models/Payment.php");
		$this->model = new wpdevart_bc_ModelPayments();
		$theme_id = isset($_GET['theme_id']) ? esc_html(stripslashes($_GET['theme_id'])) : 0;
		$this->theme_option = $this->model->get_setting_rows($theme_id);
		require_once(WPDEVART_PLUGIN_DIR . "/admin/controllers/Reservations.php");
		$this->res_edit = new wpdevart_bc_ControllerReservations();
	}  	
	  
	public function perform() {
		$task = wpdevart_bc_Library::get_value('task');
		$id = wpdevart_bc_Library::get_value('id', 0);
		if (method_exists($this, $task)) {
		  $this->$task();
		}
		else {
		  $this->paypal_notify();
		}
	}
	
	private function paypal_notify(){
		global $wpdb;
		$sandbox = (isset($this->theme_option["payment_mode"]) && $this->theme_option["payment_mode"] == 'live') ? "live" : "sandbox";
		$res_id = isset($_GET['res_id']) ? (int)stripslashes($_GET['res_id']) : 0;
		$cal_id = isset($_GET['cal_id']) ? (int)stripslashes($_GET['cal_id']) : 0;
		
		$url_paypal = ($sandbox == "sandbox") ? 'https://www.sandbox.paypal.com/webscr?' : 'https://www.paypal.com/cgi-bin/webscr?';
		
		$ipnData = array();
        foreach ($_POST as $key => $value) {
          $ipnData[$key] = $value;
        }
        $requestData = array('cmd' => '_notify-validate') + $ipnData;
        $request = http_build_query($requestData);

        $curl = curl_init();
        curl_setopt_array($curl, array(
		  CURLOPT_URL => $url_paypal,
		  CURLOPT_HEADER => 0,
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $request,
		  CURLOPT_SSL_VERIFYPEER => true,
		  CURLOPT_SSLVERSION => 1,
		  CURLOPT_RETURNTRANSFER => 1));
		  
        $response = curl_exec($curl);
		if(!$response){
			$response = "";
		}
        curl_close($curl);
		$date = date('Y-m-d H:i:s');		
		$ip = $_SERVER['REMOTE_ADDR'];
		$total = isset($_POST['mc_gross']) ? esc_html($_POST['mc_gross']) : "";
		$tax_value = isset($_POST['tax']) ? esc_html($_POST['tax']) : "";
		$payment_status = isset($_POST['payment_status']) ? esc_html($_POST['payment_status']) : "";

		$payment_address = isset($_POST['address_country']) ? "Country: " . esc_html($_POST['address_country']) . "<br>" : "";
		$payment_address .= isset($_POST['address_state']) ? "State: " . esc_html($_POST['address_state']) . "<br>" : '';
		$payment_address .= isset($_POST['address_city']) ? "City: " . esc_html($_POST['address_city']) . "<br>" : '';
		$payment_address .= isset($_POST['address_street']) ? "Street: " . esc_html($_POST['address_street']) . "<br>" : '';
		$payment_address .= isset($_POST['address_zip']) ? "Zip Code: " . esc_html($_POST['address_zip']) . "<br>" : '';
		$payment_address .= isset($_POST['address_status']) ? "Address Status: " . esc_html($_POST['address_status']) . "<br>" : '';
		$payment_address .= isset($_POST['address_name']) ? "Name: " . esc_html($_POST['address_name']) . "<br>" : '';
		$paypal_info = "";
		$paypal_info .= isset($_POST['payer_status']) ? "Payer Status - " . esc_html($_POST['payer_status']) . "<br>" : '';
		$paypal_info .= isset($_POST['payer_email']) ? "Payer Email - " . esc_html($_POST['payer_email']) . "<br>" : '';
		$paypal_info .= isset($_POST['first_name']) ? "Payer Name - " . esc_html($_POST['first_name']) : '';
		$paypal_info .= isset($_POST['last_name']) ? " " . esc_html($_POST['last_name']) . "<br>" : '';
		$paypal_info .= isset($_POST['txn_id']) ? "Transaction - " . esc_html($_POST['txn_id']) . "<br>" : '';
		$paypal_info .= isset($_POST['payment_type']) ? "Payment Type - " . esc_html($_POST['payment_type']) . "<br>" : '';
		$id = $wpdb->get_var($wpdb->prepare('SELECT pay_id FROM ' . $wpdb->prefix . 'wpdevart_payments WHERE res_id="%d"', $res_id));
		
		if(!is_null($id) && $id){
		  $save_db = $wpdb->update($wpdb->prefix . 'wpdevart_payments', array(
			'payment_price' => $total,
			'tax' => $tax_value,
			'pay_status' => $payment_status,
			'ip' => $ip,
			'ipn' => $response,
			'payment_address' => $payment_address,
			'payment_info' => $paypal_info,
			'modified_date' => $date      
		  ), array('res_id' => $res_id));
		} else {
		  $save_db = $wpdb->insert($wpdb->prefix . 'wpdevart_payments', array(
			'res_id' => $res_id,
			'payment_price' => $total,
			'tax' => $tax_value,
			'pay_status' => $payment_status,
			'ip' => $ip,
			'ipn' => $response,
			'payment_address' => $payment_address,
			'payment_info' => $paypal_info,
			'modified_date' => $date      
		  ), array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s'
		  ));
		} 
		  
		if($save_db)  {
			if($payment_status == "Completed" || $payment_status == 'Pending'){
				$this->send_mail($res_id,$cal_id, "completed");
				if(isset($this->theme_option['enable_psuccess_approval']) && $this->theme_option['enable_psuccess_approval'] == "on") {
					$change_status = $wpdb->update($wpdb->prefix . 'wpdevart_reservations', array('status' => "approved"), array('id' => $res_id));
					$this->res_edit->change_date_avail_count( $res_id,true);
				}
			}
			else if($payment_status == 'Failed' || $payment_status == 'Denied' || $payment_status == 'Expired' || $payment_status == 'Voided' || $payment_status == 'Refunded' || $payment_status == 'Processed'){
				$this->send_mail($res_id,$cal_id, "failed");
			}
		}
	}
	private function paypal_cancel(){
        global $wpdb;
		$res_id = isset($_GET['res_id']) ? (int)stripslashes($_GET['res_id']) : 0;
		  $save_db = $wpdb->insert($wpdb->prefix . 'wpdevart_payments', array(
		    'res_id' => $res_id,
			'pay_status' => "cancelled"  
		  ), array(
			'%d',
			'%s'
		  ));
	}
	
	private function send_mail($res_id,$cal_id, $type){
		$countries = wpdevart_bc_BookingCalendar::get_countries();
		$reserv = $this->model->get_reservation_row($res_id);
		$admin_email_types = array();
		$user_email_types = array();
		$admin_error_types = array();
		$user_error_types = array();
		$hour_html = "";
		$calendar_title = $this->model->get_calendar_title($cal_id);
		$form_data = $this->model->get_form_data($reserv["form"],$cal_id);
        $extras_data = $this->model->get_extra_data($reserv["extras"],$reserv["price"],$cal_id);
		if($reserv["check_in"]) {
			$check_in = date($this->theme_option["date_format"], strtotime($reserv["check_in"]));
			$check_out = date($this->theme_option["date_format"], strtotime($reserv["check_out"]));
			$res_day = $check_in. "-" .$check_out;
		} else {
			$res_day = date($this->theme_option["date_format"], strtotime($reserv["single_day"]));
		}
		if(isset($reserv["start_hour"]) && $reserv["start_hour"] != ""){
			$hour_html = $reserv["start_hour"];
		}
		if(isset($reserv["end_hour"]) && $reserv["end_hour"] != ""){
			$hour_html = $hour_html." - ".$reserv["end_hour"];
		}
		if($hour_html != ""){
			$hour_html = "<tr><td style='padding: 1px 7px;'>".__('Hour','booking-calendar')."</td> <td  style='padding: 1px 7px;'>".$hour_html.'</td></tr>';
		}
		$site_url = site_url();
		$moderate_link = admin_url() . "admin.php?page=wpdevart-reservations";
		$res_info = "<table border='1' style='border-collapse:collapse;min-width: 360px;'>
						<caption style='text-align:left;'>".__('Details','booking-calendar')."</caption>
						<tr><td style='padding: 1px 7px;'>".__('Reservation dates','booking-calendar')."</td><td style='padding: 1px 7px;'>".$res_day."</td></tr>".$hour_html."
						<tr><td style='padding: 1px 7px;'>".__('Item Count','booking-calendar')."</td><td style='padding: 1px 7px;'>".$reserv["count_item"]."</td></tr>
						<tr><td style='padding: 1px 7px;'>".__('Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reserv["currency"]) : '') . $reserv["price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reserv["currency"]) : '')."</td></tr>
						<tr><td style='padding: 1px 7px;'>".__('Total Price','booking-calendar')."</td> <td style='padding: 1px 7px;'>".((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reserv["currency"]) : '') . $reserv["total_price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reserv["currency"]) : '')."</td></tr>
					</table>";
		$form = "";
		$extras = "";		
		if(count($form_data)) {
			$form .= "<table border='1' style='border-collapse:collapse;min-width: 360px;'>";
			$form .= "<caption style='text-align:left;'>Contact Information</caption>";
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
					$extras .= "<span class='price'>".$extra_data["operation"] . ((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reserv["currency"]) : '') . $extra_data["price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reserv["currency"]) : '')."</span></td></tr>";
				} else {
					$extras .= "<span class='price'>".$extra_data["operation"] .((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reserv["currency"]) : '') . $extra_data["price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reserv["currency"]) : '')."</span></td></tr>";
				}
				
			}
			$extras .= "<tr><td style='padding: 1px 7px;'>".__('Price change','booking-calendar')."</td><td style='padding: 1px 7px;'>".(($reserv["extras_price"]<0)? "" : "+").((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "before") ? esc_html($reserv["currency"]) : '') . $reserv["extras_price"] . (((isset($this->theme_option['currency_pos']) && $this->theme_option['currency_pos'] == "after") || !isset($this->theme_option['currency_pos'])) ? esc_html($reserv["currency"]) : '')."</td></tr>";
			$extras .= "</table>";
		}
		if(isset($this->theme_option['notify_admin_paypal']) && $this->theme_option['notify_admin_paypal'] == "on" && $type == "completed") {
			$admin_email_types[] = 'notify_admin_paypal';
		}
		if(isset($this->theme_option['notify_user_paypal']) && $this->theme_option['notify_user_paypal'] == "on" && $type == "completed") {
			$user_email_types[] = 'notify_user_paypal';
		}
		if(isset($this->theme_option['notify_user_paypal_failed']) && $this->theme_option['notify_user_paypal_failed'] == "on" && $type == "failed") {
			$user_email_types[] = 'notify_user_paypal_failed';
		}
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
					$content = str_replace("[calendartitle]", $calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[moderatelink]", $moderate_link, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $reserv["total_price"], $content);
					$content = "<div class='wpdevart_email' style='color:#5A5A5A !important;line-height: 1.5;'>".$content."</div>";
				}
				if(isset($this->theme_option[$admin_email_type.'_from']) && $this->theme_option[$admin_email_type.'_from'] != "") {
					if(trim($this->theme_option[$admin_email_type.'_from']) == "[useremail]") {
						$from = "From: '" . $fromname . "' <" . $reserv["email"] . ">" . "\r\n";
					} else {
						$from = "From: '" . $fromname . "' <" . stripslashes($this->theme_option[$admin_email_type.'_from']) . ">" . "\r\n";
					}
				}
				$headers = "MIME-Version: 1.0\n" . $from . " Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
				
				
				$admin_error_types[$admin_email_type] = wp_mail($to, $subject, $content, $headers);
			}	
		}	
			/*Email to user*/
		if(count($user_email_types)) {	
			foreach($user_email_types as $user_email_type) {	
				$from = "";
				$fromname = "";
				$subject = "";
				$content = "";
				$to = $reserv["email"];
				if(isset($this->theme_option[$user_email_type.'_subject']) && $this->theme_option[$user_email_type.'_subject'] != "") {
					$subject = stripslashes($this->theme_option[$user_email_type.'_subject']);
				}
				if(isset($this->theme_option[$user_email_type.'_fromname']) && $this->theme_option[$user_email_type.'_fromname'] != "") {
					$fromname = stripslashes($this->theme_option[$user_email_type.'_fromname']);
				}
				if(isset($this->theme_option[$user_email_type.'_content']) && $this->theme_option[$user_email_type.'_content'] != "") {
					$content = stripslashes($this->theme_option[$user_email_type.'_content']);
					$content = str_replace("[calendartitle]", $calendar_title, $content);
					$content = str_replace("[details]", $res_info, $content);
					$content = str_replace("[siteurl]", $site_url, $content);
					$content = str_replace("[form]", $form, $content);
					$content = str_replace("[extras]", $extras, $content);
					$content = str_replace("[totalprice]", $reserv["total_price"], $content);
					$content = "<div class='wpdevart_email' style='color:#5A5A5A !important;line-height: 1.5;'>".$content."</div>";
				}
				if(isset($this->theme_option[$user_email_type.'_from']) && $this->theme_option[$user_email_type.'_from'] != "") {
					$from = "From: '" . $fromname . "' <" . stripslashes($this->theme_option[$user_email_type.'_from']) . ">" . "\r\n";
				}
				$headers = "MIME-Version: 1.0\n" . $from . " Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
				
				$user_error_types[$user_email_type] = wp_mail($to, $subject, $content, $headers);
			}
		}	
		$result = array($admin_error_types,$user_error_types);
		return 	$result;
	}

}

?>