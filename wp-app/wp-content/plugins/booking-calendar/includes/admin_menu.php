<?php 

/*############  Booking calendar Admin Menu Class ################*/

class wpdevart_bc_admin_menu{
	
	private $menu_name;

	function __construct($param){
		$this->menu_name=$param['menu_name'];
	}

    /*############  Create menu function ################*/
	
	public function create_menu(){
		if ( get_option( 'wpdevart_permissions' ) !== false ) {
			$permissions = get_option( 'wpdevart_permissions' );
			$permissions = json_decode($permissions,true);
		}
		$wpdevart_pages = array('calendar_page','reservation_page','form_page','extra_page','theme_page','global_settings_page');
		foreach($wpdevart_pages as $wpdevart_page) {
			if(!isset($permissions[$wpdevart_page])){
				$permissions[$wpdevart_page] = "publish_pages";
			}
		}
	
		//var_dump($permissions);
		
		$main_page = add_menu_page( $this->menu_name, $this->menu_name, $permissions['calendar_page'], 'wpdevart-calendars', array($this, 'calendars_function'),WPDEVART_URL.'css/images/menu_icon.png');
		$page_bookings =	add_submenu_page('wpdevart-calendars',  "Calendars",  "Calendars", $permissions['calendar_page'], 'wpdevart-calendars', array($this, 'calendars_function'));
		$page_reservation = add_submenu_page( 'wpdevart-calendars', 'Reservations', 'Reservations', $permissions['reservation_page'], 'wpdevart-reservations', array($this, 'resrvations'));
		$page_forms = add_submenu_page( 'wpdevart-calendars', 'Forms', 'Forms', $permissions['form_page'], 'wpdevart-forms', array($this, 'forms_function'));
		$page_extra = add_submenu_page( 'wpdevart-calendars', 'Extras', 'Extras', $permissions['extra_page'], 'wpdevart-extras', array($this, 'extras_function'));
		$page_themes = add_submenu_page( 'wpdevart-calendars', 'Themes', 'Themes', $permissions['theme_page'], 'wpdevart-themes', array($this, 'themes_function'));
		$page_global = add_submenu_page( 'wpdevart-calendars', 'Global Settings', 'Global Settings', $permissions['global_settings_page'], 'wpdevart-global-settings', array($this, 'global_settings_function'));
		$page_manage = add_submenu_page( 'wpdevart-calendars', 'User permissions', 'User permissions', 'manage_options', 'wpdevart-user-permissions', array($this, 'themes_user_permissions'));
		$page_uninstall = add_submenu_page( 'wpdevart-calendars', 'Uninstall'  , 'Uninstall', 'manage_options', 'wpdevart-booking-uninstall', array($this, 'uninstall_booking'));
		$page_featured = add_submenu_page( 'wpdevart-calendars', 'Featured plugins', 'Featured plugins', 'manage_options', 'wpdevart-add-booking', array($this, 'featured_plugins'));
		add_action('admin_print_styles-' .$main_page, array($this,'calendar_requeried_scripts'));
		add_action('admin_print_styles-' .$page_bookings, array($this,'calendar_requeried_scripts'));	
		add_action('admin_print_styles-' .$page_reservation, array($this,'menu_requeried_scripts'));	
		add_action('admin_print_styles-' .$page_themes, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_global, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_manage, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_uninstall, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_forms, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_extra, array($this,'menu_requeried_scripts'));
		add_action('admin_print_styles-' .$page_featured, array($this,'menu_requeried_scripts'));
	}

    /*############  Menu Requeried Scripts function ################*/
	
	public function menu_requeried_scripts(){
		wp_enqueue_script('wp-color-picker');		
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_media(); 
		wp_enqueue_style(WPDEVART_PLUGIN_PREFIX.'-admin-style', WPDEVART_URL.'css/admin_style.css',array(),WPDEVART_VERSION);
		wp_enqueue_style(WPDEVART_PLUGIN_PREFIX.'calendar-style', WPDEVART_URL.'css/booking.css',array(),WPDEVART_VERSION);
		wp_register_script( WPDEVART_PLUGIN_PREFIX.'-admin-script', WPDEVART_URL.'js/admin_script.js', array("jquery"),WPDEVART_VERSION );
		wp_localize_script( WPDEVART_PLUGIN_PREFIX.'-admin-script', WPDEVART_PLUGIN_PREFIX."_admin", array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'   => wp_create_nonce( WPDEVART_PLUGIN_PREFIX . '_ajax_nonce' ),
			'required' => __("is required.",'booking-calendar'),
			'emailValid' => __("Enter the valid email address.",'booking-calendar'),
			'hour' => __("Hour",'booking-calendar'),
			'price' => __("Price",'booking-calendar'),
			'marked_price' => __("Marked Price",'booking-calendar'),
			'available' => __("Available",'booking-calendar'),
			'booked' => __("Booked",'booking-calendar'),
			'unavailable' => __("Unavailable",'booking-calendar'),
			'number_availability' => __("Number Availabile",'booking-calendar'),
			'h_info' => __("Hour Information",'booking-calendar'),
			'date' => __("Date",'booking-calendar')
		) );
		wp_enqueue_script( WPDEVART_PLUGIN_PREFIX.'-admin-script' );
		wp_register_script( 'wpdevart-booking-script', WPDEVART_URL.'js/booking.js', array("jquery"),WPDEVART_VERSION);
		wp_localize_script( 'wpdevart-booking-script', WPDEVART_PLUGIN_PREFIX, array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'       => wp_create_nonce( 'wpdevart_ajax_nonce' ),
			'required' => __("is required.",'booking-calendar'),
			'emailValid' => __("Enter the valid email address.",'booking-calendar'),
			'date' => __("Date",'booking-calendar'),
			'hour' => __("Hour",'booking-calendar')
		) );
		wp_enqueue_script( 'wpdevart-booking-script' );
		if (function_exists('add_thickbox')) add_thickbox();
	}
	public function calendar_requeried_scripts(){
		wp_enqueue_style('wpdevart-font-awesome', WPDEVART_URL . 'css/font-awesome/font-awesome.css',array(),WPDEVART_VERSION);
		wp_enqueue_style(WPDEVART_PLUGIN_PREFIX.'-admin-style', WPDEVART_URL.'css/admin_style.css',array(),WPDEVART_VERSION);
		wp_enqueue_style(WPDEVART_PLUGIN_PREFIX.'calendar-style', WPDEVART_URL.'css/booking.css',array(),WPDEVART_VERSION);
		wp_register_script( WPDEVART_PLUGIN_PREFIX.'-admin-calendar', WPDEVART_URL.'js/admin_calendar.js', array("jquery"),WPDEVART_VERSION );
		wp_localize_script( WPDEVART_PLUGIN_PREFIX.'-admin-calendar', WPDEVART_PLUGIN_PREFIX, array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'   => wp_create_nonce( WPDEVART_PLUGIN_PREFIX . '_ajax_nonce' ),
			'hour' => __("Hour",'booking-calendar'),
			'price' => __("Price",'booking-calendar'),
			'marked_price' => __("Marked Price",'booking-calendar'),
			'available' => __("Available",'booking-calendar'),
			'booked' => __("Booked",'booking-calendar'),
			'unavailable' => __("Unavailable",'booking-calendar'),
			'number_availability' => __("Number Availabile",'booking-calendar'),
			'h_info' => __("Hour Information",'booking-calendar'),
		) );
		wp_enqueue_script( WPDEVART_PLUGIN_PREFIX.'-admin-calendar' );
	}	
			
	public function calendars_function(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/Calendars.php');
		$controller = new wpdevart_bc_ControllerCalendars();
		$controller->perform();
		
	}

    /*############  Calendar themes function ################*/
	
	public function themes_function(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/Themes.php');
		$controller = new wpdevart_bc_ControllerThemes();
		$controller->perform();
	}	
	public function global_settings_function(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/GlobalSettings.php');
		$controller = new wpdevart_bc_ControllerGlobalSettings();
		$controller->perform();
	}	
	
    /*############  Forms function ################*/	
	
	public function forms_function(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/Forms.php');
		$controller = new wpdevart_bc_ControllerForms();
		$controller->perform();
	}
	public function extras_function(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/Extras.php');
		$controller = new wpdevart_bc_ControllerExtras();
		$controller->perform();
	}
	
    /*############  Reservations function ################*/	
	
	public function resrvations(){
        require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/Reservations.php');
		$controller = new wpdevart_bc_ControllerReservations();
		$controller->perform();
	}
	
	public function themes_user_permissions(){
	    require_once(WPDEVART_PLUGIN_DIR . 'admin/controllers/UserPermissions.php');
		$controller = new wpdevart_bc_ControllerUserPermissions();
		$controller->perform();
	}
	
	
	
	/*################################## FEATURED PLUGINS #########################################*/
	public function featured_plugins(){
		$plugins_array=array(
			'coming_soon'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/coming_soon.jpg',
						'site_url'		=>	'http://wpdevart.com/wordpress-coming-soon-plugin/',
						'title'			=>	'Coming soon and Maintenance mode',
						'description'	=>	'Coming soon and Maintenance mode plugin is an awesome tool to show your visitors that you are working on your website to make it better.'
						),
			'countdown_extendet'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/countdown_extendet.png',
						'site_url'		=>	'https://wordpress.org/plugins/countdown-wpdevart-extended/',
						'title'			=>	'Countdown Timer – Extended version, Popup Countdown',
						'description'	=>	'The most functional and beautiful Countdown Timer plugin for WordPress.'
						),
			'Contact forms'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/contact_forms.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-contact-form-plugin/',
						'title'			=>	'Contact Form',
						'description'	=>	'Contact Form plugin is an nice and handy tool for creating different types of contact forms on your WordPress websites.'
						),	
			 'gallery_album'=>array(
						'image_url'	=>	WPDEVART_URL.'css/images/featured_plugins/gallery.png',
						'site_url'	=>	'http://wpdevart.com/wordpress-gallery-plugin',
						'title'	=>	'WordPress Gallery plugin',
						'description'	=>	'Gallery plugin is an useful tool that will help you to create Galleries and Albums. Try our nice Gallery views and awesome animations.'
						),			
			'youtube'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/youtube.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-youtube-embed-plugin',
						'title'			=>	'WordPress YouTube Embed',
						'description'	=>	'YouTube Embed plugin is an convenient tool for adding video to your website. Use YouTube Embed plugin to add YouTube videos in posts/pages, widgets.'
						),
			'lightbox'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/lightbox.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-lightbox-plugin',
						'title'			=>	'WordPress Lightbox plugin',
						'description'	=>	'WordPress lightbox plugin is an high customizable and responsive product for displaying images and videos in popup.'
						),
			'countdown'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/countdown.jpg',
						'site_url'		=>	'http://wpdevart.com/wordpress-countdown-plugin/',
						'title'			=>	'WordPress Countdown plugin',
						'description'	=>	'WordPress Countdown plugin is an nice tool to create and insert countdown timers into your posts/pages and widgets.'
						),
            'facebook-comments'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/facebook-comments-icon.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-facebook-comments-plugin/',
						'title'			=>	'WordPress Facebook comments',
						'description'	=>	'Our Facebook comments plugin will help you to display Facebook Comments on your website. You can use Facebook Comments on your pages/posts.'
						),						
			'facebook'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/facebook.jpg',
						'site_url'		=>	'http://wpdevart.com/wordpress-facebook-like-box-plugin',
						'title'			=>	'Facebook Like Box',
						'description'	=>	'Our Facebook like box plugin will help you to display Facebook like box on your wesite, just add Facebook Like box widget to your sidebar and use it..'
						),
			'poll'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/poll.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-polls-plugin',
						'title'			=>	'Poll',
						'description'	=>	'WordPress Polls plugin is an wonderful tool for creating polls and survey forms for your visitors. You can use our polls on widgets, posts and pages.'
						),
			'twitter'=>array(
						'image_url'		=>	WPDEVART_URL.'css/images/featured_plugins/twitter.png',
						'site_url'		=>	'http://wpdevart.com/wordpress-twitter-plugin',
						'title'			=>	'Twitter button plus',
						'description'	=>	'Twitter button plus is nice and useful tool to show Twitter tweet button on your website.'
						),															
			
		);
		?>
        <script>
		
        jQuery(window).resize(wpdevart_countdown_feature_resize);
		jQuery(document).ready(function(e) {
            wpdevart_countdown_feature_resize();
        });
		
		function wpdevart_countdown_feature_resize(){
			var wpdevart_countdown_width=jQuery('.featured_plugin_main').eq(0).parent().width();
			var count_of_elements=Math.max(parseInt(wpdevart_countdown_width/450),1);
			var width_of_plugin=((wpdevart_countdown_width-count_of_elements*24-2)/count_of_elements);
			jQuery('.featured_plugin_main').width(width_of_plugin);
			jQuery('.featured_plugin_information').css('max-width',(width_of_plugin-160)+'px');
		}
       	</script>
		<h1>Featured Plugins</h1>
		<?php foreach($plugins_array as $key=>$plugin) { ?>
		<div class="featured_plugin_main">
			<span class="featured_plugin_image"><a target="_blank" href="<?php echo $plugin['site_url'] ?>"><img src="<?php echo $plugin['image_url'] ?>"></a></span>
			<span class="featured_plugin_information">
				<span class="featured_plugin_title"><h4><a target="_blank" href="<?php echo $plugin['site_url'] ?>"><?php echo $plugin['title'] ?></a></h4></span>
				<span class="featured_plugin_description"><?php echo $plugin['description'] ?></span>
			</span>
			<div style="clear:both"></div>                
		</div>
		<?php } 
	}
	
	public function uninstall_booking() {
		global $wpdb;
		if(isset( $_POST['uninstall_booking_data'] )   && wp_verify_nonce( $_POST['uninstall_booking_data'], 'uninstall_booking')){
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_calendars`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_dates`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_forms`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_extras`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_themes`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_reservations`");
			$wpdb->query("DROP TABLE `" . $wpdb->prefix . "wpdevart_payments`");
			delete_option("wpdevart_booking_version");
			?>
			<div id="message" class="updated fade">
			  <p><?php _e('The following Database Tables successfully deleted:','booking-calendar'); ?></p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_calendars,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_dates,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_forms,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_extras,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_themes,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_reservations,</p>
			  <p><?php echo $wpdb->prefix; ?>wpdevart_payments,</p>
			</div>
			<div class="wrap">
			  <h1><?php _e('Uninstall Booking Calendar','booking-calendar'); ?></h1>
			  <p><strong><a href="<?php echo wp_nonce_url('plugins.php?action=deactivate&amp;plugin=booking-calendar-pro/booking_calendar.php', 'deactivate-plugin_booking-calendar-pro/booking_calendar.php'); ?>"><?php _e('Click Here','booking-calendar'); ?></a><?php _e(' To Finish the Uninstallation','booking-calendar'); ?></strong></p>
			</div>
		  <?php
			return;
		}
		?>
		<div id="wpdevart_uninstal_container" class="wpdevart-list-container">
			<form method="post" action="admin.php?page=wpdevart-booking-uninstall" style="width:99%;">
			 <?php wp_nonce_field('uninstall_booking','uninstall_booking_data'); ?>
			    <div class="div-for-clear">
					<span class="admin_logo"></span>
					<h1><?php _e('Uninstall Booking calendar','booking-calendar'); ?></h1>
					<a target="blank" href="<?php echo wpdevart_booking_support_url; ?>" class="wp_support">Support</a>
				</div>
				<p><?php _e('Deactivating Booking calendar plugin does not remove any data that may have been created. To completely remove this plugin, you can uninstall it here.','booking-calendar'); ?></p>
				<p style="color: red;"><strong><?php _e('WARNING:','booking-calendar'); ?></strong><?php _e('Once uninstalled, this can\'t be undone. You should use a Database Backup plugin of WordPress to back up all the data first.','booking-calendar'); ?></p>
				<p style="color: red"><strong><?php _e('The following Database Tables will be deleted:','booking-calendar'); ?></strong></p>
				<table class="widefat">
				  <thead>
					<tr>
					  <th>Database Tables</th>
					</tr>
				  </thead>
				  <tr>
					<td valign="top">
					  <ol>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_calendars</li>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_dates</li>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_forms</li>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_extras</li>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_themes</li>
						  <li><?php echo $wpdb->prefix; ?>wpdevart_reservations</li>
					  </ol>
					</td>
				  </tr>
				</table>
				<p style="text-align: center;">
				  <?php _e('Do you really want to uninstall Booking Calendar?','booking-calendar'); ?>
				</p>
				<p style="text-align: center;">
				  <input type="checkbox" id="check_yes" value="yes" />&nbsp;<label for="check_yes"><?php _e('Yes','booking-calendar'); ?></label>
				</p>
				<p style="text-align: center;">
				  <input type="submit" value="UNINSTALL" class="button-primary" onclick="if (check_yes.checked) {	if (!confirm('You are About to Uninstall booking calendar.\nThis Action Is Not Reversible.')) {return false; } } else { return false; }" />
				</p>
			</form>
		</div>
  <?php    
	}
	
	
}