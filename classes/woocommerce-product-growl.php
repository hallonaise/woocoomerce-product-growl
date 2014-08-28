<?php

	class WooCommerceProductGrowl
	{

		private $cookie_prefix = 'wp-wc-product-growl-';
		private $wpdb = NULL;

		public function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
		}

		/**
		*	Plugins "Working horse"
		*
		*	@author Michal Kwiatkowski <michal@hallonaise.se>
		*	@return void
		**/
		public function init()
		{
			$visitor_id = $this->get_visitor_id();

			if (!$visitor_id) {
				$visitor_id = $this->save_visitor();
			}

			// Check if page is a product page
			if (is_product()) {
				global $post;
				
				$product = get_product($post->ID);

				if ($product) {
					$this->save_view($product->id, $visitor_id);
				}
			}
		}

		/**
		*	Getting visitor id from cookie
		*
		*	@author Michal Kwiatkowski <michal@hallonaise.se>
		*	@return int|bool
		**/
		private function get_visitor_id()
		{
			if (isset($_COOKIE[$this->cookie_prefix . 'visitor-id'])) {
				return (int)$_COOKIE[$this->cookie_prefix . 'visitor-id'];
			} else {
				return FALSE;
			}
		}

		/**
		*	Saving visitors id in cookie (for 1 year) and database
		*
		*	@author Michal Kwiatkowski <michal@hallonaise.se>
		*	@return int|bool
		*
		**/
		private function save_visitor()
		{
			$this->wpdb->insert($this->wpdb->prefix . 'woocommerce_product_growl_visitor', array(
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'time' => date('Y-m-d H:i:s')
			));

			if ((int)$this->wpdb->insert_id > 0) {
				setcookie($this->cookie_prefix . 'visitor-id', (int)$this->wpdb->insert_id, time() + ((3600 * 24) * 365), '/');
				return (int)$this->wpdb->insert_id;
			} else {
				return FALSE;
			}
		}

		/**
		*	Saving view for the product, both in cookie (for 5 days) and in database.
		*	Function saves the info only if cookie is not set
		*
		*	@author Michal Kwiatkowski <michal@hallonaise.se>
		*	@param int $product_id
		*	@param int $visitor_id
		*	@return int|bool
		**/
		private function save_view($product_id = NULL, $visitor_id = NULL)
		{
			if ((int)$product_id > 0 && (int)$visitor_id > 0) {
				if (!isset($_COOKIE[$this->cookie_prefix . 'view-' . (int)$product_id])) {
					$this->wpdb->insert($this->wpdb->prefix . 'woocommerce_product_growl_view', array(
							'product_id' => (int)$product_id,
							'visitor_id' => (int)$visitor_id,
							'time' => date('Y-m-d H:i:s')
						)
					);

					// Save it even ad meta value ("just in case")
					$product_views = get_post_meta($product_id, 'product_views');
					if ($product_views) {
						update_post_meta($product_id, 'product_views', $product_views++);
					} else {
						add_post_meta($product_id, 'product_views', 1);
					}

					if ((int)$this->wpdb->insert_id > 0) {
						setcookie($this->cookie_prefix . 'view-' . (int)$product_id, (int)$this->wpdb->insert_id, time() + ((3600 * 24) * 5), '/');
						return (int)$this->wpdb->insert_id;
					} else {
						return FALSE;
					}
				}
			}
		}

		/**
		*	Getting number of views for a product, by the time period
		*
		*	@author Michal Kwiatkowski <michal@hallonaise.se>
		*	@param int $product_id
		*	@param string $time_period (need to be suported by strtotime)
		*	@return int|bool
		*
		**/
		public function get_number_of_views($product_id = NULL, $time_period = '5 days ago')
		{
			if ((int)$product_id > 0 && isset($time_period)) {

				$sql = $this->wpdb->prepare(
					'SELECT COUNT(*) AS count FROM ' . $this->wpdb->prefix . 'woocommerce_product_growl_view 
					WHERE product_id = %d AND time >= %s', (int)$product_id, date('Y-m-d H:i:s', strtotime($time_period))
				);

				return $this->wpdb->get_var($sql);
			} else {
				return FALSE;
			}
		}

		/**
		*	@todo Implement
		**/
		public function get_number_of_orders()
		{

		}
	}