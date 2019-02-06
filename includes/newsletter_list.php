<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class NLSM_Newsletter_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Newsletter', 'simple-newsletter' ), //singular name of the listed records
			'plural'   => __( 'Newsletter', 'simple-newsletter' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
	}
	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_newsletter_info_list( $per_page = 10, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}wbnl";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}

	/**
	 * Delete a newsletter record.
	 *
	 * @param int $id newsletter ID
	 */
	public static function delete_newsletter( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
				"
					DELETE FROM {$wpdb->prefix}wbnl
					WHERE id = %d
				", 
					$id
			) );
	}
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wbnl";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no newsletter data is available */
	public function no_items() {
		_e( 'No items avaliable.', 'simple-newsletter' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'email':
			case 'phone':
			case 'regdate':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {
		$delete_nonce = wp_create_nonce( 'nlsm_delete_newsletter' );
		
		$title = '<strong>' . $item['name'] . '</strong>';
        $peded=1;
        if(!empty($_GET['peded'])) $peded=esc_html($_GET['peded']);
		$actions = [
			'delete' => sprintf( '<a href="?page=%s&paged=%d&action=%s&ID=%s&_nlsm_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), $peded, 'delete', absint( $item['id'] ), $delete_nonce )
		];
		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$setting=get_option('nlsm_option',['wb_nl_email'=>"1","wb_nl_name"=>"0","wb_nl_phone"=>"0"]);
		$setting=nlsm_esc_array($setting);

		$columns ['cb']      ='<input type="checkbox" />';
		//if($setting['wb_nl_name']){
		   $columns ['name']    = __( 'Name', 'simple-newsletter' );
		//}
		if($setting['wb_nl_phone']){
	    	$columns['phone'] = __( 'Phone', 'simple-newsletter' );
		}
		if($setting['wb_nl_email']){
			$columns['email']  =  __( 'Email', 'simple-newsletter' );
		}
		$columns['regdate']    = __( 'Register Date', 'simple-newsletter' );
		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ),
			'email' => array( 'email', true ),
			'phone' => array( 'phone', false ),
			'regdate' => array( 'regdate', false )
		);
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];
		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'newsletter_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_newsletter_info_list( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_nlsm_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'nlsm_delete_newsletter' ) ) {
				wp_die( __( 'You do not have the necessary access to change the data.', 'simple-newsletter' ) );
			}
			else {
				self::delete_newsletter( absint( $_GET['ID'] ) );
		                wp_redirect(admin_url('admin.php?page=wbnl&paged='.esc_attr( $_REQUEST['paged'] )));
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_newsletter( $id );

			}
		    wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}


class NLSM_Newsletter {

	static $instance;
	public $newsletter_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			__( 'Email Newsletter', 'simple-newsletter' ),
			__( 'Email Newsletter', 'simple-newsletter' ),
			'manage_options',
			'wbnl',
			[ $this, 'nlsm_email_newsletter' ]
		);
        
		add_action( "load-$hook", [ $this, 'screen_option' ] );
       	add_submenu_page(
       	              'wbnl',
       	               __( 'Setting', 'simple-newsletter' ),
       	               __( 'Setting', 'simple-newsletter' ),
       	               'manage_options','Setting',
       	               [ $this, 'nlsm_newsletter_setting' ]);
	}
	/**
	 * Email Newsletter page
	 */
	public function nlsm_email_newsletter() {
	if (!current_user_can('manage_options'))  {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'simple-newsletter' ) );
       }  
		?>
		<div class="wrap wbnlmain">
         <h2><?php _e( 'Email Newsletter', 'simple-newsletter' ); ?></h2>
         	<input type="submit" value="<?php _e( 'Export Email List', 'simple-newsletter' ); ?>" name="export_btn" class="button-primary" onclick="window.open('<?php echo plugins_url( '/email.export.php', __FILE__ ) ?>')" />
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->newsletter_obj->prepare_items();
								$this->newsletter_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}
    /**
	 * Setting Newsletter page
	 */
	public function nlsm_newsletter_setting() {
		if (!current_user_can('manage_options'))  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'simple-newsletter' ) );
        }
        $setting=get_option('nlsm_option',['wb_nl_email'=>"1","wb_nl_name"=>"0","wb_nl_phone"=>"0"]);
        $setting=nlsm_esc_array($setting);
?>
        <div class="wrap wbnlmain">
         <h2><?php _e( 'Newsletter Settings', 'simple-newsletter' ); ?></h2><br>
	 	 <form method="post" id='wbns'>
			<table class="widefat importers striped" style="min-width: 450px;">
    			<tr>
    				<td><label for='ywb_nl_email'><?php _e( 'Email: ', 'simple-newsletter' ); ?></label></td>
                    <td><input id='ywb_nl_email' type='radio' <?php echo ($setting['wb_nl_email']?'checked':'') ?> value='1' name='wb_nl_email'><label for='ywb_nl_email'><?php _e( 'Yes', 'simple-newsletter' ); ?></label></td>
                    <td><input id='nwb_nl_email'  type='radio' <?php echo (!$setting['wb_nl_email']?'checked':'') ?> value='0' name='wb_nl_email'><label for='nwb_nl_email' ><?php _e( 'no', 'simple-newsletter' ); ?></label></td>
                </tr>
                <tr>
    				<td><label for='ywb_nl_name'><?php _e( 'Name: ', 'simple-newsletter' ); ?></label></td>
                    <td><input id='ywb_nl_name' type='radio' <?php echo ($setting['wb_nl_name']?'checked':'') ?> value='1' name='wb_nl_name'><label for='ywb_nl_name'><?php _e( 'Yes', 'simple-newsletter' ); ?></label></td>
                    <td><input id='nwb_nl_name' type='radio' <?php echo (!$setting['wb_nl_name']?'checked':'') ?> value='0' name='wb_nl_name'><label for='nwb_nl_name' ><?php _e( 'no', 'simple-newsletter' ); ?></label></td>
                </tr>
                <tr>
    				<td><label for='ywb_nl_phone'><?php _e( 'Phone: ', 'simple-newsletter' ); ?></label></td>
                    <td><input id='ywb_nl_phone' type='radio' <?php echo ($setting['wb_nl_phone']?'checked':'') ?> value='1' name='wb_nl_phone'><label for='ywb_nl_phone'><?php _e( 'Yes', 'simple-newsletter' ); ?></label></td>
                    <td><input id='nwb_nl_phone' type='radio' <?php echo (!$setting['wb_nl_phone']?'checked':'') ?> value='0' name='wb_nl_phone'><label for='nwb_nl_phone'><?php _e( 'no', 'simple-newsletter' ); ?></label></td>
                </tr>
                <tr>
    				<td collspan='3'><p><input type='button' class='setting_btn button-primary' value="<?php _e( 'Save', 'simple-newsletter' ); ?>" ></p></td>
                </tr>
             </table>
	        <p class='res'></p>
	        <?php wp_nonce_field( 'nlsm_setting_nonce','nlsm_setting' ); ?>
	        <input type='hidden' name='action' value='setting' >
        </form>
       <?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Newsletter',
			'default' => 10,
			'option'  => 'newsletter_per_page'
		];
		add_screen_option( $option, $args );
		$this->newsletter_obj = new NLSM_Newsletter_List();
	}

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
add_action( 'plugins_loaded', function () {
	NLSM_Newsletter::get_instance();
} );
