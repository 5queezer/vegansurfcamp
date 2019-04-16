<?php
class wpdevart_bc_Library {
	
	public static function print_pro_message(){
		$msg = "";
		if (WPDEVART_PRO == "free"){
			$msg ='<a href="' . WPDEVART_PLUGIN_URL . '"><span class="pro_feature"> (Upgrade to Pro Version)</span></a>';
		}
		elseif (WPDEVART_PRO == "pro") {
			$msg ='<a href="' . WPDEVART_PLUGIN_URL . '"><span class="pro_feature"> (Upgrade to Extended Version)</span></a>';
		}
		return $msg;										
	}
	
	public static function print_field_pro_message($args){
		$msg = ""; 
		if (isset($args["pro"]) &&  WPDEVART_PRO != "extended") {
			if (WPDEVART_PRO == "free") {
				$msg = "<span class='pro_feature' data-pro='" . $args["pro"]  . "'>(" . ucfirst($args["pro"]) . ")</span>";
			} 
			elseif (WPDEVART_PRO == "pro" && $args["pro"] == "extended") {
				$msg = "<span class='pro_feature' data-pro='" . $args["pro"]  . "'>(" . ucfirst($args["pro"]) . ")</span>";
			}
		}
		
		return $msg;										
	}
	
	public static function is_pro($args){
		$pro = false; 
		if (isset($args["pro"]) &&  WPDEVART_PRO != "extended") {
			if (WPDEVART_PRO == "free") {
				$pro = true; 
			} 
			elseif (WPDEVART_PRO == "pro" && $args["pro"] == "extended") {
				$pro = true; 
			}
		}
		
		return $pro;										
	}
	
	public static function get_value($key, $default_value=""){
		if (isset($_GET[$key])) {
		  $value = sanitize_text_field($_GET[$key]);
		}
		elseif (isset($_POST[$key])) {
		  $value = sanitize_text_field($_POST[$key]);
		}
		else {
		  $value = '';
		}
		if (!$value) {
		  $value = $default_value;
		}
		return $value;
	}
	
	public static function getData($data, $key, $type = "text", $default_value = "", $cond = null ){
		switch($type){
			case "text":
			$sanitize = "sanitize_text_field";
		}
        if (isset($data[$key])) {
		  if(!is_null($cond)){	
			  if($cond){	
				$value = $sanitize($data[$key]);
			  }else  {
				  $value = $default_value;
			  }
		  } else {
			  $value = $sanitize($data[$key]);
		  }
		}
		else  {
		  $value = $default_value;
		}
		return $value;
	}
	
	public static function sanitizeAllPost($post, $textareas = array() ){
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$saved_parametrs = array();
		foreach($post as $post_mein_key => $post_mein_value){
			if(!is_array($post_mein_value)){
                if(in_array($post_mein_key,$textareas))	{			
					$saved_parametrs[sanitize_key($post_mein_key)] = wp_kses(stripslashes($post_mein_value),$allowed_tags);
				} else{
					$saved_parametrs[sanitize_key($post_mein_key)] = sanitize_text_field(stripslashes($post_mein_value));
				}
			} else{
				foreach($post_mein_value as $post_items_key => $post_items_value){
					if(!is_array($post_items_value)){
						if(in_array($post_mein_key,$textareas))	{			
							$saved_parametrs[sanitize_key($post_mein_key)][sanitize_key($post_items_key)]= wp_kses(stripslashes($post_items_value),$allowed_tags);
						} else{						
							$saved_parametrs[sanitize_key($post_mein_key)][sanitize_key($post_items_key)]=sanitize_text_field(stripslashes($post_items_value));	
						}
					} else{
						foreach($post_items_value as $key => $value){
							if(in_array($post_mein_key,$textareas))	{			
								$saved_parametrs[sanitize_key($post_mein_key)][sanitize_key($post_items_key)][sanitize_key($key)]= wp_kses(stripslashes($value),$allowed_tags);
							} else{	
								if(!is_array($value)){
									$saved_parametrs[sanitize_key($post_mein_key)][sanitize_key($post_items_key)][sanitize_key($key)]=sanitize_text_field(stripslashes($value));
								} else{
									foreach($value as $k=>$v){
									$saved_parametrs[sanitize_key($post_mein_key)][sanitize_key($post_items_key)][sanitize_key($key)][$k]=sanitize_text_field(stripslashes($v));
									}
								}
							}							
						}	
					}						
				}	
			}			
		}
		return $saved_parametrs;
	}
	 
    public static function wpdevart_callback_empty($args,$value) {
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			  </div>
		  </div>
	  </div>
	  <?php
	}
	 
    public static function wpdevart_callback_info($args,$value) {
	  ?>
	  <div class="wpdevart-item-container <?php echo isset($args['class']) ? $args['class'] : ""; ?> div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-info" id="wpdevart_wrap_<?php echo $args['id']; ?>">
				<?php echo $args['description']; ?>
			  </div>
		  </div>
	  </div>
	  <?php
	}
	
	public static function wpdevart_callback_color($args,$value) {
		if(!is_array($value))
			$value=sanitize_text_field($value);
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-color" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			    <?php if (self::is_pro($args)) : ?>
					<div class="pro-field  overlay"></div>
				<?php endif; ?>
				<input type="text" id="<?php echo $args['id']; ?>" name="<?php echo $args['id']; ?>" value="<?php if(!is_array($value)) echo $value; ?>" <?php echo ((isset($args['disabled']) && $args['disabled']==true))? "disabled='disabled'" : ""; ?> class="color <?php echo self::is_pro($args)? "pro-field" : ""; ?>" <?php echo self::is_pro($args) ? "readonly" : ""; ?>>
			  </div>
		  </div>
	  </div>
	  <script  type="text/javascript">
		 jQuery(document).ready(function() {
		   jQuery('.color').wpColorPicker();
		 });
      </script>
	  <?php
	}
	
	public static function wpdevart_callback_upload($args,$value) {
		if(!is_array($value))
			$value=esc_url($value);
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-color" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			    <input type="text" class="wp-media-input <?php echo self::is_pro($args) ? "pro-field" : ""; ?>" name="<?php echo $args['id']; ?>" id="<?php echo $args['id']; ?>_input" value="<?php if(!is_array($value)) echo $value; ?>"/>
			    <input type="button" class="button wp-media-buttons-icon <?php echo self::is_pro($args) ? "pro-field" : ""; ?>" name="<?php echo $args['id']; ?>_button" id="<?php echo $args['id']; ?>" value="<?php _e("Add Image","booking-calendar"); ?>"/>
				
			  </div>
		  </div>
	  </div>
	  <script  type="text/javascript">
		 jQuery(document).ready(function() {
		   jQuery('#<?php echo $args['id']; ?>').click(function(e) {
				var imageUrl = "";
				media_uploader = wp.media({
					frame:    "post", 
					state:    "insert", 
					multiple: false 
				});

				media_uploader.on("insert", function(){
					var uploaded_image = media_uploader.state().get('selection').first().attributes.url;
					jQuery('#<?php echo $args['id']; ?>_input').val(uploaded_image);
				});	
				media_uploader.open();
			});
		 });
      </script>
	  <?php
	}
    public static function wpdevart_callback_select($args,$value) {
		
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-checkbox" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			    <div class="stylesh-select">
				<?php if(isset($args['currency']) && $args['currency'] === true) { ?>
					<select id="<?php echo $args['id']; ?>" name="<?php echo $args['id']; ?>" <?php echo (isset($args['onchange'])? 'onchange="'.$args['onchange'].'"' : '' ); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>>
					<?php
					foreach ($args['valid_options'] as $valid_option) { ?>
						<option value='<?php echo $valid_option['code']; ?>' <?php echo selected($value,$valid_option['code']); ?>><?php echo $valid_option['name']. ' - ' .$valid_option['simbol']; ?></option>
					<?php  } ?>
					</select>
				<?php } else { ?>
					<select id="<?php echo $args['id']; ?>" name="<?php echo $args['id']; ?>" <?php echo (isset($args['onchange'])? 'onchange="'.$args['onchange'].'"' : '' ); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>>
					<?php
					foreach ($args['valid_options'] as $key => $valid_option) { ?>
						<option value='<?php echo $key; ?>' <?php echo selected($value,$key); ?>><?php echo $valid_option; ?></option>
					<?php  } ?>
					</select>	
				<?php } ?>
			    </div>
				<?php if($args['id'] == "theme_id" && count($args['valid_options']) == 0){ ?>
					<p style="color: #e20404;margin: 0;font-weight: bold;"><?php _e('Add Theme','booking-calendar'); ?></p>
				<?php } ?>
			  </div>
		  </div>
	  </div>
	  <?php
	}


    public static function wpdevart_callback_checkbox($args,$value) {
	  ?>
	   <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-checkbox stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['id']; ?>">
				<?php
				if (isset($args['valid_options']) && $args['valid_options']!='') {
					foreach ($args['valid_options'] as $key => $valid_option) { ?>
						<input type='checkbox' id='checkbox_<?php echo $key; ?>' value='<?php echo $key; ?>' name='<?php echo $args['id'].'[]'; ?>' <?php echo checked(in_array($key,$value)); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>><label for='checkbox_<?php echo $key; ?>'><?php echo $valid_option; ?></label>
					<?php }  
				 } else {  ?>
					<input type='checkbox' id='<?php echo $args['id']; ?>' name='<?php echo $args['id']; ?>' <?php echo checked($value,'on'); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>>
					<label for='<?php echo $args['id']; ?>' class="label_switch"></label>
				<?php } ?>
			  </div>
		  </div>
	  </div>
	  <?php
	}

    public static function wpdevart_callback_radio($args,$value) {
	  ?>
	<div class="wpdevart-item-container div-for-clear">
	    <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['id']; ?>">
				<?php
				if (isset($args['valid_options']) && $args['valid_options']!='') {
					foreach ($args['valid_options'] as $key => $valid_option) { ?>
						<input type='radio' id='radio_<?php echo $key; ?>' value='<?php echo $key; ?>' name='<?php echo $args['id']; ?>' <?php checked($value,$key); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>><label for='radio_<?php echo $key; ?>'><?php echo $valid_option; ?></label>
					<?php }  
				}  ?>
			  </div>
		</div>
	</div>
	  <?php
	}


    public static function wpdevart_callback_text($args,$value) {
			
		if(!is_array($value))
			$value=sanitize_text_field($value);
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title" id="label_<?php echo $args['id']; ?>" >
				 <span class="wpdevart-title"><?php echo $args['title']; ?></span>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio div-for-clear" id="wpdevart_wrap_<?php echo $args['id']; ?>">
				<input type="text" id="<?php echo $args['id']; ?>" name="<?php echo $args['id']; ?>" value="<?php if(!is_array($value) && $value != "0") echo htmlspecialchars(stripslashes($value)); ?>" <?php echo ((isset($args['readonly']) && $args['readonly']==true))? "readonly" : ""; ?> <?php echo (isset($args['width']) && $args['width'])? 'style="width:'.$args['width'].'px"': ""; ?> <?php echo self::is_pro($args) ? "class='pro-field' readonly" : ""; ?>>
			  </div>
		  </div>
	  </div>
	  <?php
	}


    public static function wpdevart_callback_textarea($args,$value) {
	  ?>
	   <div class="wpdevart-item-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="wpdevart-title"><?php echo $args['title']; ?>
				 <?php  echo self::print_field_pro_message($args); ?>
				 <?php if(isset($args['required']) && $args['required'] == "on"){ ?>
				    <span class="wpdevart-required">*</span> 
				 <?php } ?>
				 </span>
				 <?php if(isset($args['description']) && $args['description'] != "") { ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
				 <?php } ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			    <?php if(isset($args['wp_editor']) && $args['wp_editor'] && user_can_richedit()) {
					wp_editor(((!is_array($value))? $value : ""), $args['id'], array('teeny' => FALSE, 'textarea_name' => $args['id'], 'media_buttons' => FALSE, 'textarea_rows' => 5));
				} else { ?>
					<textarea id="<?php echo $args['id']; ?>" name="<?php echo $args['id']; ?>" <?php echo self::is_pro($args) ? "class='pro-field' readonly" : ""; ?>><?php if(!is_array($value) && $value != "") echo $value; ?></textarea>
				<?php } ?>
			  </div>
		  </div>
	  </div>
	  <?php
	}


    public static function wpdevart_callback_checkbox_enable($args,$value) {
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	    <div class="wpdevart-fild-item-container"> 
	      <div class="section-title">
		     <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			 <?php  echo self::print_field_pro_message($args); ?>
			 <?php if(isset($args['description']) && $args['description'] != "") { ?>
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
			 <?php } ?>
		  </div>
		  <div class="wpdevart-item-elem-container element-checkbox-enable stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			  <input type="checkbox" class="checkbox <?php echo self::is_pro($args) ? 'pro-field' : ""; ?>" name="<?php echo $args['id']; ?>" id="<?php echo $args['id'] ?>" <?php checked($value,'on'); ?>>
			  <label for='<?php echo $args['id']; ?>' class="label_switch"></label>
		  </div>
		</div>
	  </div>
	  <script>
	  jQuery(document).ready(function () {

		var wpdevart_element_<?php echo $args["id"]; ?> = {
		  id : "<?php echo $args["id"]; ?>",
		  enable : [
			<?php
			foreach ($args['enable'] as $enable) :
			echo "'". $enable ."', ";
			endforeach; 
			?>        
			]
		};
		wpdevart_elements.checkbox_enable(wpdevart_element_<?php echo $args["id"]; ?>);
		jQuery('#<?php echo $args["id"]; ?>').on( "click", function() {
		  wpdevart_elements.checkbox_enable(wpdevart_element_<?php echo $args["id"]; ?>);
		});

	  });
	  </script>
	  <?php
	}

    public static function wpdevart_callback_radio_enable($args,$value) {
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	    <div class="wpdevart-fild-item-container">
	      <div class="section-title">
		     <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			 <?php  echo self::print_field_pro_message($args); ?>
			 <?php if(isset($args['description']) && $args['description'] != "") { ?>
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
			 <?php } ?>
		  </div>
		  <div class="wpdevart-item-elem-container element-radio-enable" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			<?php
			if (isset($args['valid_options']) && $args['valid_options']!='') {
				foreach ($args['valid_options'] as $key => $valid_option) { ?>
					<input type='radio' id='radio_<?php echo $key; ?>' value='<?php echo $key; ?>' name='<?php echo $args['id']; ?>' <?php echo checked($value,$key); ?> <?php echo self::is_pro($args) ? "class='pro-field'" : ""; ?>><label for='radio_<?php echo $key; ?>'><?php echo $valid_option; ?></label>
				<?php }  
			}
			?>
		  </div>
		</div>
	  </div>
	  <script>
	  jQuery(document).ready(function () {

		var wpdevart_element_<?php echo $args["id"]; ?> = {
		  id : "<?php echo $args["id"]; ?>",
		  enable : [
			<?php
			foreach ($args['enable'] as $key => $value) {
                echo "{key: '" . $key ."', val: [" ; 
				if(is_array($value)) {
					foreach ($value as $item){
						echo "'".$item."',";
					} 
				} else {
					echo "'".$value."',";
				}
				echo "]},";
            }  
			?>        
			]
		};
		
		wpdevart_elements.radio_enable(wpdevart_element_<?php echo $args["id"]; ?>);
		jQuery('input[type=radio][name="<?php echo $args['id']; ?>"]').on( "change", function() {
		  wpdevart_elements.radio_enable(wpdevart_element_<?php echo $args["id"]; ?>);
		});

	  });
	  </script>
	  <?php
	}

    public static function wpdevart_callback_hidden($args,$value) {
	  ?>
	  <input type="hidden" name="<?php echo $args["id"]; ?>" id="<?php echo $args["id"]; ?>" value="<?php echo $args["default"]; ?>">
	  <?php
	}
	

    public static function wpdevart_callback_conditions($args,$value) {
		$placeholder = (isset($args['day']) && $args['day'] == true) ? __("Day Count","booking-calendar") : __("Hour Count","booking-calendar");
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	    <div class="wpdevart-fild-item-container">
	      <div class="section-title">
		     <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			 <?php  echo self::print_field_pro_message($args); ?>
			 <?php if(isset($args['description']) && $args['description'] != "") { ?>
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
			 <?php } ?>
		  </div>
		  <div class="wpdevart-item-elem-container element-sale-conditions" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			<span class="add_hour <?php echo self::is_pro($args) ? "pro-field" : ""; ?>"  onclick="add_conditions(this,'<?php echo $args['id']; ?>','<?php echo $placeholder; ?>','<?php _e("Percent","booking-calendar"); ?>');"><?php _e("Add Conditions","booking-calendar"); ?></span> 
			<?php
			if (isset($value['count']) && count($value['count'])) {
				for($i=0; $i<count($value['count']); $i++) { ?>
				    <div class="conditions_element div-for-clear">
						<input type='text' class='short_input' value='<?php echo isset($value['count'][$i]) ? $value['count'][$i] : ""; ?>' name='<?php echo $args['id']; ?>[count][]' placeholder="<?php echo $placeholder; ?>">
						<select name="<?php echo $args['id']; ?>[type][]">
							<option value="percent" <?php selected(isset($value['type'][$i]) && $value['type'][$i] == "percent"); ?>>Percent</option>
							<option value="price" <?php selected(isset($value['type'][$i]) && $value['type'][$i] == "price"); ?>>Price</option>
						</select>
						<input type='text' class='short_input' value='<?php echo isset($value['percent'][$i]) ? $value['percent'][$i] : ""; ?>' name='<?php echo $args['id']; ?>[percent][]' placeholder="<?php _e("Percent","booking-calendar"); ?>">
						<span class="delete_hour_item"><i class="fa fa-close"></i></span>
					</div>
				<?php }  
			} else { ?>
				    <div class="conditions_element div-for-clear">
						<input type='text' class='short_input' value='' name='<?php echo $args['id']; ?>[count][]' placeholder="<?php echo $placeholder; ?>">
						<select name="<?php echo $args['id']; ?>[type][]">
							<option value="percent" selected="selected">Percent</option>
							<option value="price">Price</option>
						</select>
						<input type='text' class='short_input' value='' name='<?php echo $args['id']; ?>[percent][]' placeholder="<?php _e("Percent","booking-calendar"); ?>">
						<span class="delete_hour_item"><i class="fa fa-close"></i></span>
					</div>
				<?php
			}
			?>
		  </div>
		</div>
	  </div>
	  <?php
	}
	
	
    public static function wpdevart_callback_hours_element($args,$value) {
	  ?>
	  <div class="wpdevart-item-container div-for-clear">
	    <div class="wpdevart-fild-item-container">
	      <div class="section-title">
		     <span class="wpdevart-title"><?php echo $args['title']; ?></span>
			 <?php  echo self::print_field_pro_message($args); ?>
			 <?php if(isset($args['description']) && $args['description'] != "") { ?>
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo $args['description']; ?></span></span>
			 <?php } ?>
		  </div>
		  <div class="wpdevart-item-elem-container element-radio-enable" id="wpdevart_wrap_<?php echo $args['id']; ?>">
			<span class="add_hour <?php echo self::is_pro($args) ? "pro-field" : ""; ?>" onclick="add_hour(this,'<?php echo $args['id']; ?>');"><?php _e("Add Hour","booking-calendar"); ?></span> 
		    <span class="add_default" onclick="add_default(this,'<?php echo $args['id']; ?>');"><?php _e("Add Default","booking-calendar"); ?></span> 
			<?php
			if (isset($value['hour_value']) && count($value['hour_value'])) {
				for($i=0; $i<count($value['hour_value']); $i++) { ?>
				    <div class="hour_element div-for-clear">
						<input type='text' class='hour_value short_input' value='<?php echo isset($value['hour_value'][$i]) ? sanitize_text_field($value['hour_value'][$i]) : ""; ?>' name='<?php echo $args['id']; ?>[hour_value][]' placeholder="<?php _e("Hour","booking-calendar"); ?>">
						<input type='text' class='hour_price short_input' value='<?php echo isset($value['hour_price'][$i]) ? sanitize_text_field($value['hour_price'][$i]) : ""; ?>' name='<?php echo $args['id']; ?>[hour_price][]' placeholder="<?php _e("Price","booking-calendar"); ?>">
						<input type='text' class='hours_marked_price short_input' value='<?php echo isset($value['hours_marked_price'][$i]) ? sanitize_text_field($value['hours_marked_price'][$i]) : ""; ?>' name='<?php echo $args['id']; ?>[hours_marked_price][]' placeholder="<?php _e("Marked Price","booking-calendar"); ?>">
						<select name='<?php echo $args['id']; ?>[hours_availability][]' class="half_input">
						   <option value="available" <?php selected(isset($value['hour_price'][$i]) && $value['hour_price'][$i] == "available"); ?>><?php _e("Available","booking-calendar"); ?></option>
						   <option value="booked" <?php selected(isset($value['hour_price'][$i]) && $value['hour_price'][$i] == "booked"); ?>><?php _e("Booked","booking-calendar"); ?></option>
						   <option value="unavailable" <?php selected(isset($value['hour_price'][$i]) && $value['hour_price'][$i] == "unavailable"); ?>><?php _e("Unavailable","booking-calendar"); ?></option>
						</select>
						<input type='text' class='hours_number_availability half_input' value='<?php echo isset($value['hours_number_availability'][$i]) ? sanitize_text_field($value['hours_number_availability'][$i]) : ""; ?>' name='<?php echo $args['id']; ?>[hours_number_availability][]' placeholder="<?php _e("Number Availabile","booking-calendar"); ?>">
						<input type='text' class='hour_info full_input' value='<?php echo isset($value['hour_info'][$i]) ? sanitize_text_field($value['hour_info'][$i]) : ""; ?>' name='<?php echo $args['id']; ?>[hour_info][]' placeholder="<?php _e("Hour Information","booking-calendar"); ?>">
						<span class="delete_hour_item"><i class="fa fa-close"></i></span>
					</div>
				<?php }  
			} else { ?>
				    <div class="hour_element div-for-clear">
						<input type='text' class='hour_value short_input' value='' name='<?php echo $args['id']; ?>[hour_value][]' placeholder="<?php _e("Hour","booking-calendar"); ?>">
						<input type='text' class='hour_price short_input' value='' name='<?php echo $args['id']; ?>[hour_price][]' placeholder="<?php _e("Price","booking-calendar"); ?>">
						<input type='text' class='hours_marked_price short_input' value='' name='<?php echo $args['id']; ?>[hours_marked_price][]' placeholder="<?php _e("Marked Price","booking-calendar"); ?>">
						<select name='<?php echo $args['id']; ?>[hours_availability][]' class="half_input">
						   <option value="available"><?php _e("Available","booking-calendar"); ?></option>
						   <option value="booked"><?php _e("Booked","booking-calendar"); ?></option>
						   <option value="unavailable"><?php _e("Unavailable","booking-calendar"); ?></option>
						</select>
						<input type='text' class='hours_number_availability half_input' value='' name='<?php echo $args['id']; ?>[hours_number_availability][]' placeholder="<?php _e("Number Availabile","booking-calendar"); ?>">
						<input type='text' class='hour_info full_input' value='' name='<?php echo $args['id']; ?>[hour_info][]' placeholder="<?php _e("Hour Information","booking-calendar"); ?>">
						<span class="delete_hour_item"><i class="fa fa-close"></i></span>
					</div>
				<?php
			}
			?>
		  </div>
		</div>
		<?php /*<input type='hidden' id='hour_value_element' value='<?php echo $value; ?>' name='<?php echo $args['id']; ?>'> */ ?>
	  </div>
	  <?php
	}
	
	
	
	/*
	*FORM
	*/
     
	 
    public static function wpdevart_form_select($args,$value) {
	  ?>
	  <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<select id="<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>" disabled="disabled">
				<?php foreach ($args['valid_options'] as $key => $valid_option) { ?>
					<option value='<?php echo $key; ?>' <?php echo selected($value,$key); ?>><?php echo $valid_option; ?></option>
				<?php  } ?>
				</select>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					<label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Multiple select",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='form_multi_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[multi]' <?php echo checked(isset($value['multi']) && $value['multi'] == 'on'); ?> >
					<label for='form_multi_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Options",'booking-calendar'); ?>
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php _e('Add one per line','booking-calendar'); ?></span></span>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<textarea id='form_opt_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[options]'><?php if(isset($value['options'])) echo sanitize_textarea_field($value['options']); ?></textarea>
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}
	
	 public static function wpdevart_form_countries($args,$value) {
	  ?>
	  <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<select id="<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>" disabled="disabled">
				<?php foreach ($args['valid_options'] as $key => $valid_option) { ?>
					<option value='<?php echo $key; ?>' <?php echo selected($value,$key); ?>><?php echo $valid_option; ?></option>
				<?php  } ?>
				</select>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					<label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}


    public static function wpdevart_form_checkbox($args,$value) {
	  ?>
	   <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<?php
				if (isset($args['options']) && $args['options']!='') {
					foreach ($args['options'] as $key => $valid_option) { ?>
						<input type='checkbox' id='checkbox_<?php echo $key; ?>' value='<?php echo $key; ?>' name='<?php echo $args['name'].'[]'; ?>' disabled='disabled'><label for='checkbox_<?php echo $key; ?>'><?php echo $valid_option; ?></label>
					<?php }  
				 } else {  ?>
					<input type='checkbox' id='<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>'  disabled='disabled'>
				<?php } ?>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
			<div class="form-fild-options">
				<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
				<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			    <div class="wpdevart-item-container div-for-clear">
					<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
					<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
						<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
					</div>
				</div>
			    <div class="wpdevart-item-container div-for-clear">
					<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
					<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
						<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					   <label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
					</div>
				</div>
		    </div>
	  </div>
	  <?php
	}

    public static function wpdevart_form_radio($args,$value) {
	  ?>
	<div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	    <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<?php
				if (isset($args['options']) && $args['options']!='') {
					foreach ($args['options'] as $key => $valid_option) { ?>
						<input type='radio' id='radio_<?php echo $key; ?>' value='<?php echo $key; ?>' name='<?php echo $args['name']; ?>' disabled='disabled'><label for='radio_<?php echo $key; ?>'><?php echo $valid_option; ?></label>
					<?php }  
				} ?>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		</div>
		<div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo $value['label']; ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					   <label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
		</div>
	</div>
	  <?php
	}


    public static function wpdevart_form_text($args,$value) {
	  ?>
	  <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title" id="label_<?php echo $args['name']; ?>" >
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<input type="text" id="<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>"  disabled='disabled'>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
			<div class="form-fild-options">
				<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
				<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			    <div class="wpdevart-item-container div-for-clear">
					<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
					<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
						<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>"  class="form_label">
					</div>
				</div>
			    <div class="wpdevart-item-container div-for-clear">
					<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
					<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
						<input type='checkbox' id='required_<?php echo $args['name']; ?>' name="<?php echo $args['name']; ?>[required]" <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					   <label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
					</div>
				</div>
			    <div class="wpdevart-item-container div-for-clear red-section">
					<div class="section-title"> <?php echo __("Is Email",'booking-calendar'); ?> 
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo __("Use only for Email field",'booking-calendar'); ?></span></span></div>
					<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
						<input type='checkbox' id='form_isemail_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[isemail]' <?php echo checked(isset($value['isemail']) && $value['isemail'] == 'on'); ?> >
					   <label for='form_isemail_<?php echo $args['name']; ?>' class="label_switch"></label>
					</div>
				</div>
		    </div>
	  </div>
	  <?php
	}


    public static function wpdevart_form_textarea($args,$value) {
	  ?>
	   <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<textarea id="<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>" disabled='disabled'></textarea>
				
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					<label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}

    public static function wpdevart_form_recapthcha($args,$value) {
	  ?>
	   <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required">*</span>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}
	
	public static function wpdevart_form_upload($args,$value) {
	  ?>
	   <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php echo $args['label']; ?></span><span class="wpdevart-required"><?php if(isset($value['required']) && $value['required'] == 'on') echo "*"; ?></span>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php echo $args['name']; ?>">
				<input type="file" id="<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>" disabled='disabled'>
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[type]' value="<?php echo $args['type']; ?>">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo $value['label']; ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Required",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='required_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[required]' <?php echo checked(isset($value['required']) && $value['required'] == 'on'); ?> class="form_req">
					<label for='required_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php _e("Allowed File Types",'booking-calendar'); ?> 
				<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo __("Choose the file types that you want to allow users for submitting in form.",'booking-calendar'); ?></span></span>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<textarea id='form_opt_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[extensions]'><?php echo (isset($value['extensions']))? $value['extensions'] : "png,jpg,jpeg,gif,doc,xls,xlsx"; ?></textarea>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php _e("Allowed maximum size(KB)",'booking-calendar'); ?>
					<span class="wpdevart-info-container">?<span class="wpdevart-info"><?php echo __("Type the allowed maximum size for uploading files(KB).",'booking-calendar'); ?></span></span>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='text' id='max_size_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[max_size]' value="<?php echo (isset($value['max_size']))? $value['max_size'] : 20000; ?>">
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}
	
    public static function wpdevart_extras_field($args,$value = "") {
       if(isset($value["items"]) && is_array($value["items"])) {
		   $last_element = end($value["items"]);
		   $max_id = str_replace('field_item', '', $last_element['name']);
	   } else {
		   $max_id = 3;
	   }	 
	   
	  ?>
	   <div class="wpdevart-item-container wpdevart-item-parent-container div-for-clear">
	      <div class="wpdevart-fild-item-container">
			  <div class="section-title">
				 <span class="section-title-txt"><?php if(isset($args['label'])) echo $args['label']; ?>
			  </div>
			  <div class="wpdevart-item-elem-container element-radio" id="wpdevart_wrap_<?php if(isset($args['name'])) echo $args['name']; ?>">
				<div class="delete-form-fild"><i class="fa fa-close"></i>
				</div>
				<div class="open-form-fild-options"><i class="fa fa-chevron-down" aria-hidden="true"></i>
				</div>
			  </div>
		  </div>
		  <div class="form-fild-options">
			<input type="hidden" name='<?php echo $args['name']; ?>[name]' value="<?php echo $args['name']; ?>">
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Label",'booking-calendar'); ?> </div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type="text" id="label_<?php echo $args['name']; ?>" name="<?php echo $args['name']; ?>[label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>" class="form_label">
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Regardless of counting days",'booking-calendar'); ?> 
				<?php echo WPDEVART_PRO == "free" ? "<span class='pro_feature' data-pro='" . $args["pro"]  . "'>(Pro)</span>" : ""; ?>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='independent_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[independent]' <?php echo checked(isset($value['independent']) && $value['independent'] == 'on'); ?> <?php echo WPDEVART_PRO == "free" ? "class='pro-field'" : ""; ?>>
			        <label for='independent_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title"> <?php echo __("Regardless of Item count",'booking-calendar'); ?> 
				<?php echo WPDEVART_PRO == "free" ? "<span class='pro_feature' data-pro='" . $args["pro"]  . "'>(Pro)</span>" : ""; ?>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear stylesh-checkbox" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					<input type='checkbox' id='independent_counts_<?php echo $args['name']; ?>' name='<?php echo $args['name']; ?>[independent_counts]' <?php echo checked(isset($value['independent_counts']) && $value['independent_counts'] == 'on'); ?> <?php echo WPDEVART_PRO == "free" ? "class='pro-field'" : ""; ?>>
			        <label for='independent_counts_<?php echo $args['name']; ?>' class="label_switch"></label>
				</div>
			</div>
			<div class="wpdevart-item-container div-for-clear">
				<div class="section-title div-for-clear"><span class="extra-items"> <?php echo __("Items",'booking-calendar'); ?></span>
					<div class="add_extra_field_item" data-max="<?php echo $max_id; ?>" data-field="<?php echo $args['name']; ?>"></div>
				</div>
				<div class="wpdevart-item-elem-container div-for-clear" id="wpdevart_wrap_<?php echo $args['name']; ?>">
					
					<div class="wpdevart-extra-items-container">
						<ul class="extra-items-labels div-for-clear">
							<li>Label</li>
							<li>Operation</li>
							<li>Price type</li>
							<li>Price/Percent</li>
						</ul>
						<?php if(isset($args["items"]) && count($args["items"])) { ?>
							<ul class="wpdevart-extra-item-container div-for-clear">
							<?php foreach($args["items"] as $key=>$item) {
								if(isset($value['items'][''.$key.''])) {
									$val = $value['items'][''.$key.''];
								} else {
									$val = 0;
								}
								echo self::wpdevart_extras_field_item($args['name'],$item,$val);
							 } ?>
							</ul> 
						<?php } ?>
						<?php if(WPDEVART_PRO == "free") : ?>
						<ul class="pro-feature extra-items-labels div-for-clear">
							<li></li>
							<li><span class="pro_feature">(Pro)</span></li>
							<li><span class="pro_feature">(Pro)</span></li>
							<li><span class="pro_feature"></span></li>
						</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		  </div>
	  </div>
	  <?php
	}
	
	public static function wpdevart_redirect($url){ ?>
			<script>
				window.location = "<?php echo $url; ?>";
			</script>	
		<?php exit();
	}
	
		
    public static function wpdevart_extras_field_item($name,$args,$value=0) {
	  ?>
		<li> 
			<div class="wpdevart-extra-item  div-for-clear">
				<input type="hidden" name="<?php echo $name; ?>[items][<?php echo $args["name"]; ?>][name]" value="<?php echo (isset($args["name"]))? $args["name"] : ""; ?>">
				<input type="text" name="<?php echo $name; ?>[items][<?php echo $args["name"]; ?>][label]" value="<?php if(isset($value['label'])) echo sanitize_text_field($value['label']); ?>">
				<select name="<?php echo $name; ?>[items][<?php echo $args["name"]; ?>][operation]" <?php echo WPDEVART_PRO == "free" ? "class='pro-field'" : ""; ?>>
					<option value="+" <?php selected(isset($value["operation"]) && $value["operation"]=="+"); ?>>+</option>
					<option value="-" <?php selected(isset($value["operation"]) && $value["operation"]=="-"); ?>>-</option>
				</select>
				<select name="<?php echo $name; ?>[items][<?php echo $args["name"]; ?>][price_type]" <?php echo WPDEVART_PRO == "free" ? "class='pro-field'" : ""; ?>>
					<option value="price" <?php selected(isset($value["price_type"]) && $value["price_type"]=="price"); ?>>Price</option>
					<option value="percent" <?php selected(isset($value["price_type"]) && $value["price_type"]=="percent"); ?>>Percent</option>
				</select>
				<input type="text" name="<?php echo $name; ?>[items][<?php echo $args["name"]; ?>][price_percent]" value="<?php if(isset($value['price_percent'])) echo sanitize_text_field($value['price_percent']); ?>">
				<div class="delete-extra-fild"><i class="fa fa-close"></i></div>
			</div>
		</li>
	  <?php
	}
	
	public static function items_nav($wpdevart_page,$items_count,$form_id){ ?>   
        <script type="text/javascript">
			function get_page(x,y) {
				var items_county=<?php if($items_count){ if($items_count%20){ echo ($items_count-$items_count%20)/20+1;} else echo ($items_count-$items_count%20)/20;} else echo 1;?>;
				switch(y) {
					case 1:
						if(x >= items_county) {
							jQuery(".wpdevart_page").val(items_county);
						} else {
							jQuery(".wpdevart_page").val(x+1);
						}
					    break;
					case 2:
						jQuery(".wpdevart_page").val(items_county);
						break;
					case -1:
						if(x == 1) {
							jQuery(".wpdevart_page").val(1);
						} else {
							jQuery(".wpdevart_page").val(x-1);
						}
					    break;
					case -2:
					    jQuery(".wpdevart_page").val(1);
					    break;
					default:
					    jQuery(".wpdevart_page").val(1);
				}	
				jQuery("#<?php echo $form_id; ?>").submit();	
			 }
		</script>
		<div class="tablenav top">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo $items_count; ?> items</span>
				<?php 
				if($items_count > 20) {
					$first = "first-page";
					$prev = "prev-page";
					$next = "next-page";
					$last = "last-page";
					if($wpdevart_page==1) {
						$first = "first-page disabled";
						$prev = "prev-page disabled";
						$next = "next-page";
						$last = "last-page"; 
					}
					if($wpdevart_page>=(1+($items_count-$items_count%20)/20) ) {
						$first = "first-page ";
						$prev = "prev-page";
						$next = "next-page disabled";
						$last = "last-page disabled"; 
					} ?>     
					<span class="pagination-links">
						<a class="<?php echo $first; ?>" href="javascript:get_page(<?php echo $wpdevart_page; ?>,-2);">«</a>
						<a class="<?php echo $prev; ?>" href="javascript:get_page(<?php echo $wpdevart_page; ?>,-1);">‹</a>
						<span class="paging-input">
						<span class="total-pages"><?php echo $wpdevart_page; ?></span>
						of <span class="total-pages">
						<?php echo ($items_count-$items_count%20)/20+1; ?>
						</span>
						</span>
						<a class="<?php echo $next; ?>" href="javascript:get_page(<?php echo $wpdevart_page; ?>,1);">›</a>
						<a class="<?php echo $last; ?>" href="javascript:get_page(<?php echo $wpdevart_page; ?>,2);">»</a>
					</span>
				<?php } ?>
			</div>
	    </div >
		<input type="hidden" class="wpdevart_page" name="wpdevart_page" value="<?php echo (isset($_POST['wpdevart_page']))? esc_js($_POST['wpdevart_page']): 1; ?>"  />
		<?php
	
	}

 
 
}
?>