<?php

	/**
	*	Adding growl functionality to product page
	*
	*	@todo Add administration page for customization of growl behavior (look & feel)
	*	and function for getting number of order by product id
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	**/

	/**
	*	Plugin Name: WooCommerce Product Growl
	*	Description: Adding growl functionality to product page
	*	Version: 0.1
	*	Author: Hallonaise
	**/

	if (!defined('ABSPATH')) {
			exit;
	}

	// Run only if WooCommerce is activated
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

		require_once dirname(__FILE__) . '/classes/woocommerce-product-growl.php';

		global $woocommerce_product_growl_version;
		$woocommerce_product_growl_version = '0.1';

		add_action('wp', 'woocommerce_product_growl_init');
		
		add_action('wp_ajax_nopriv_get_view_count', 'woocommerce_product_growl_ajax_get_view_count');
		add_action('wp_ajax_get_view_count', 'woocommerce_product_growl_ajax_get_view_count');

		add_action('wp_ajax_nopriv_get_order_count', 'woocommerce_product_growl_ajax_get_order_count');
		add_action('wp_ajax_get_order_count', 'woocommerce_product_growl_ajax_get_order_count');

		register_activation_hook(__FILE__, 'woocommerce_product_growl_install');
		register_uninstall_hook(__FILE__, 'woocommerce_product_growl_uninstall');
	}

	/**
	*	Installation function, creating tables in database and saving version information
	*
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	*	@return void
	**/
	function woocommerce_product_growl_install()
	{
		global $wpdb;
		global $woocommerce_product_growl_version;

		// Set collation
		$charset_collate = '';

		if (!empty($wpdb->charset)) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		if (!empty($wpdb->collate)) {
		  $charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		$sqls[] = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'woocommerce_product_growl_visitor' . '(
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ip_address varchar(15) DEFAULT "" NOT NULL,
			user_agent tinytext DEFAULT "" NOT NULL,
			time datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
			UNIQUE KEY id (id)
			) ' . $charset_collate . ';';

		$sqls[] = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'woocommerce_product_growl_view' . '(
			id bigint(20) NOT NULL AUTO_INCREMENT,
			product_id bigint(20) NOT NULL,
			visitor_id bigint(20) NOT NULL,
			time datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
			UNIQUE KEY id (id)
			) ' . $charset_collate . ';';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$current_version = get_option('woocommerce_product_growl_version');

		// Compare plugin version
		if (($current_version != FALSE && $current_version != '') && $current_version != $woocommerce_product_growl_version) {
			update_option('woocommerce_product_growl_version', $woocommerce_product_growl_version);
		} else {
			if ($sqls) {
				foreach ($sqls as $sql) {
					dbDelta($sql);
				}
			}
			add_option('woocommerce_product_growl_version', $woocommerce_product_growl_version);
		}
	}

	/**
	*	Doing some clean up
	*
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	*	@return void
	**/
	function woocommerce_product_growl_uninstall()
	{
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			exit;
		}

		global $wpdb;

		delete_option('woocommerce_product_growl_version');
		delete_site_option('woocommerce_product_growl_version');

		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'woocommerce_product_growl_visitor');
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'woocommerce_product_growl_view');
	}

	/**
	*	Initialing "working horse"
	*	Creating visitors, saving views and adding js + css (if on product page)
	*
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	*	@return void
	**/
	function woocommerce_product_growl_init()
	{
    	$product_growl = new WooCommerceProductGrowl();
		$product_growl->init();

		if (is_product()) {
			global $post;

			$product = get_product($post->ID);

			// Load javascript file only if on product page
			if ($product) {
				wp_enqueue_style('woocommerce-product-growl-style', plugins_url('/css/woocommerce-product-growl.css', __FILE__));
				wp_enqueue_script('woocommerce-product-growl-script', plugins_url('/js/woocommerce-product-growl.js', __FILE__), array('jquery'));
				wp_localize_script('woocommerce-product-growl-script', 'wpg_ajax_data', array(
						'ajax_url' => admin_url('admin-ajax.php'),
						'product_id' => $product->id
					)
				);
			}
		}	
	}

	/**
	*	Function used by AJAX for get getting number of views by product id
	*	
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	*	@return string json-string for javascript
	**/
	function woocommerce_product_growl_ajax_get_view_count()
	{
		if (isset($_GET['product_id']) && (int)$_GET['product_id'] > 0) {
			$product_growl = new WooCommerceProductGrowl();
			$number_of_views = $product_growl->get_number_of_views((int)$_GET['product_id']);

			if ((int)$number_of_views > 0) {
				echo json_encode(array('status' => TRUE, 'number_of_views' => (int)$number_of_views));	
			} else {
				echo json_encode(array('status' => FALSE, 'message' => 'number_of_views_equals_zero'));	
			}
		} else {
			echo json_encode(array('status' => FALSE, 'message' => 'product_id_is_not_set'));
		}
		exit;
	}

	/**
	*	Function used by AJAX for getting number of orders by product id
	*	
	*	@todo Implement
	*	@author Michal Kwiatkowski <michal@hallonaise.se>
	*	@return void
	**/
	function woocommerce_product_growl_ajax_get_order_count()
	{
		exit;
	}