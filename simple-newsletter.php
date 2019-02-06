<?php
/*
Plugin Name: Simple newsletter
Description: Simple and custom newsletter that can use on any website.
Version: 1.1
Author: Sekineh Ebrahimzadeh
Author URI: http://wpbazar.com
*/

/**
Use function in theme :
nlsm_newsletter_form();
*/

add_action( 'plugins_loaded', 'nlsm_newsletter_load_textdomain' );
function nlsm_newsletter_load_textdomain() {
  load_plugin_textdomain( 'simple-newsletter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
//
function nlsm_output_buffer() {
	ob_start();
} 
add_action('init', 'nlsm_output_buffer');

//Add js and css
add_action( 'wp_enqueue_scripts', 'nlsm_newsletter_enqueue_scripts' );
function nlsm_newsletter_enqueue_scripts(){
	wp_enqueue_script( 'newsletter-js', plugins_url('asset/js/newsletter_ajax.js', __FILE__), array( 'jquery' ) );
	wp_localize_script( 'newsletter-js', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_register_style( 'css_newsletter', plugins_url('asset/css/newsletter.css', __FILE__), false, '1.0.0' );
	wp_enqueue_style( 'css_newsletter' );
}

//Add js and css
add_action( 'admin_enqueue_scripts', 'nlsm_newsletter_admin_init');
function nlsm_newsletter_admin_init(){
    wp_enqueue_script( 'newsletter-admin-js', plugins_url('asset/js/admin_newsletter_ajax.js', __FILE__));
	wp_register_style( 'css_admin', plugins_url('asset/css/admin.css', __FILE__), false, '1.0.0' );
	wp_enqueue_style( 'css_admin' );
}

// CREATE TABLES
register_activation_hook(__FILE__,'nlsm_install');
function nlsm_install() {
	global $wpdb;

	$create_wbnl_table = ("CREATE TABLE {$wpdb->prefix}wbnl
				(id bigint(20) unsigned NOT NULL auto_increment,
		         name varchar(60) NOT NULL,
				email varchar(60) NOT NULL,
		        phone varchar(12) NOT NULL,
				regdate varchar(60) NOT NULL,
				PRIMARY KEY  (id)) CHARSET=utf8");

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($create_wbnl_table);

}
/**
 * Sanitizing Arrays in WordPress
**/
function nlsm_sanitize_array( $input ) {
  return array_map( function( $val ) {
    return sanitize_text_field( $val );
  }, $input );
}
/**
 * Escaping Arrays in WordPress
**/
function nlsm_esc_array( $input ) {
  return array_map( function( $val ) {
    return esc_html( $val );
  }, $input );
}
/**
 * Save newsletter settings
 *
 * @return string
 */
add_action( 'wp_ajax_setting', 'nlsm_setting');
function nlsm_setting(){
	$setting = nlsm_sanitize_array($_POST);
	if ( !wp_verify_nonce( $setting['nlsm_setting'], 'nlsm_setting_nonce' ) || !check_ajax_referer( 'nlsm_setting_nonce','nlsm_setting', false ) ) {
		wp_die( __( 'You do not have the necessary access to change the data.', 'simple-newsletter' ) );
	}
	unset($setting['action']);
	unset($setting['nlsm_setting']);
	unset($setting['_wp_http_referer']);
	update_option('nlsm_option',$setting);
	_e( 'Settings saved successfully', 'simple-newsletter' );
	die();
}
/**
 * Add newsletter information in the database
 *
 * @return string
 */
add_action( 'wp_ajax_wbn_ajax_hook', 'nlsm_process');
add_action( 'wp_ajax_nopriv_wbn_ajax_hook', 'nlsm_process');
function nlsm_process(){
    $setting=get_option('nlsm_option',['wb_nl_email'=>"1","wb_nl_name"=>"0","wb_nl_phone"=>"0"]);
    $setting=nlsm_esc_array($setting);

    $nlsm_info=nlsm_sanitize_array($_POST);

    $email  = $nlsm_info['email'];
    $phone  = $nlsm_info['phone'];
    $name   = $nlsm_info['name'];
    $cdate  = date('Y-m-d H:i:s');

    if ( !wp_verify_nonce( $nlsm_info['nlsm_nonce'], 'nlsm_newsletter_nonce' ) || !check_ajax_referer( 'nlsm_newsletter_nonce','nlsm_nonce', false ) ) {
		wp_die( __( 'You do not have the necessary access to change the data.', 'simple-newsletter' ) );
	}

    $message  = '';
    $e_check =  $n_check = $p_check = 1;

		if($setting['wb_nl_email']){
			if(empty($email)){
			   $message     .= "<span class='err'>".__( 'Please enter the email address', 'simple-newsletter' )."</span>";
			   $e_check = 0;
			}elseif(!is_email($email) ){
			   $message     .= "<span class='err'>".__( 'Invalid email address', 'simple-newsletter' )."</span>";
			   $e_check = 0;
			}
		}

        if($setting['wb_nl_name']){
        	if(empty($name)){
        		$message     .= "<span class='err'>".__( 'Please enter the name', 'simple-newsletter' )."</span>";
        		$n_check = 0;
        	}
        }

        if($setting['wb_nl_phone']){
        	if(empty($phone)){
        		$message     .= "<span class='err'>".__( 'Please enter the phone number', 'simple-newsletter' )."</span>";
        		$p_check = 0;
        	}elseif(preg_match('/^[0-9]+$/', $phone)=== 0){
        		$message     .= "<span class='err'>".__( 'Invalid phone number', 'simple-newsletter' )."</span>";
                $p_check = 0;
        	}
        }
    //INSERT TO DB
    if(!empty($e_check) && !empty($n_check) && !empty($p_check))
    {
    	
    	global $wpdb;
    	    $where='';
    	    if($setting[wb_nl_email]){
    	        $where = $wpdb->prepare( 'email = %s OR', $email );
    	    }
    	    if($setting[wb_nl_phone]){
    	    	$where = $wpdb->prepare( 'phone = %s OR', $phone );
    	    }
    	    $where=substr($where, 0, -3);
    	    $get_info	= $wpdb->get_col("SELECT id FROM {$wpdb->prefix}wbnl WHERE $where");

    	if($get_info){
    	   $message     .= "<span class='err'>".__( 'You are already a member', 'simple-newsletter' )."</span>";
    	}else{

    	$inspector =$wpdb->query( $wpdb->prepare(
		"
			INSERT INTO {$wpdb->prefix}wbnl
			( name, email, phone, regdate )
			VALUES ( %s, %s, %s, %s)
		", 
			$name,
			$email, 
			$phone,
		    $cdate
	) );
    	   if($inspector){
    	      $message     .= "<span class='scss'>".__( 'Your information has been successfully recorded.', 'simple-newsletter' )."</span>";
    	   }
    	   
    	}//EMAIL DUBLICATE
    }//IF STATEMENT*/

    echo "<div> $message <div>";
    die();
    }
/**
 * Generate newsletter form
 *
 * @return string
 */
function nlsm_newsletter_form() {
	$setting=get_option('nlsm_option',['wb_nl_email'=>"1","wb_nl_name"=>"0","wb_nl_phone"=>"0"]);
	$setting=nlsm_esc_array($setting);
	$res='
	    <div class="wbnl">
	     <form id="wbnfe" class="register">';
	      if($setting['wb_nl_name']){
	      
	       $res .= '<input type="text" name="name" id="inputNmame" class="regfild" placeholder="'.__( 'Enter your name', 'simple-newsletter' ).'"/>';
	      }
	      if($setting['wb_nl_email']){
	   	    $res .= '<input type="text" name="email" id="inputEmail" class="regfild" placeholder="'.__( 'Enter your email address*', 'simple-newsletter' ).'"/>';
	   	  }
	   	  if($setting['wb_nl_phone']){
	        $res .= '<input type="text" name="phone" id="inputPhone" class="regfild" placeholder="'.__( 'Enter your phone number', 'simple-newsletter' ).'"/>';
	      }
	   $res .= '<input type="button" name="submit" class="regkey nsubmite btn" value="'. __( 'Subscribe to Newsletter', 'simple-newsletter' ).'"/>
	   	    <div id="nresulte" class="nresult"></div>
	        <input name="action" type="hidden" value="wbn_ajax_hook" />'.
	        wp_nonce_field( 'nlsm_newsletter_nonce','nlsm_nonce' ).'
	    </form>
	  </div> ';
	return $res;
   }
 add_shortcode('SMNewsLetter', 'nlsm_newsletter_form');
//----------------------------------------------------
include_once('includes/newsletter_list.php');
?>