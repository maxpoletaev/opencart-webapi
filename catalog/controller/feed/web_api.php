<?php

class ControllerFeedWebApi extends Controller {

	# Use print_r($json) instead json_encode($json)
	private $debug = true;

	public function getCategories() {
		$this->init();
		$this->load->model('catalog/category');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['parent'])) {
			$parent = $this->request->get['parent'];
		} else {
			$parent = 0;
		}

		if (isset($this->request->get['level'])) {
			$level = $this->request->get['level'];
		} else {
			$level = 1;
		}

		# -- End $_GET params --------------------------


		$json['categories'] = $this->getCategoriesTree($parent, $level);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	public function getCategory() {
		$this->init();
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$category = $this->model_catalog_category->getCategory($category_id);

		if (empty($category['category_id'])) {
			$this->error(404, 'Category not exist');
		}
		
		$json['category'] = array(
			'category_id'           => $category['category_id'],
			'name'                  => $category['name'],
			'description'           => $category['description'],
			'href'                  => $this->url->link('product/category', 'category_id=' . $category['category_id'])
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}


	public function getProducts() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true, 'products' => array());


		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$products = $this->model_catalog_product->getProducts(array(
			'filter_category_id'	=> $category_id
		));

		foreach ($products as $product) {

			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
			} else {
				$image = false;
			}

			if ((float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
			} else {
				$special = false;
			}

			$json['products'][] = array(
				'product_id'            => $product['product_id'],
				'name'                  => $product['name'],
				'description'           => $product['description'],
				'quantity'              => $product['quantity'],
				'pirce'                 => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
				'href'                  => $this->url->link('product/product', 'product_id=' . $product['product_id']),
				'thumb'                 => $image,
				'special'               => $special,
				'rating'                => $product['rating']
			);
		}

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	public function getProduct() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['product_id'])) {
			$product_id = $this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		# -- End $_GET params --------------------------

		$product = $this->model_catalog_product->getProduct($product_id);

		if (empty($product['product_id'])) {
			$this->error(404, 'Product not exist');
		}

		# product image
		if ($product['image']) {
			$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
		} else {
			$image = '';
		}

		#additional images
		$additional_images = $this->model_catalog_product->getProductImages($product['product_id']);
		$images = array();

		foreach ($additional_images as $additional_image) {
			$images[] = $this->model_tool_image->resize($additional_image, $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'));
		}

		#specal
		if ((float)$product['special']) {
			$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
		} else {
			$special = false;
		}

		#discounts
		$discounts = array();
		$data_discounts =  $this->model_catalog_product->getProductDiscounts($product['product_id']);

		foreach ($data_discounts as $discount) {
			$discounts[] = array(
				'quantity' => $discount['quantity'],
				'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product['tax_class_id'], $this->config->get('config_tax')))
			);
		}

		#options
		$options = array();

		foreach ($this->model_catalog_product->getProductOptions($product['product_id']) as $option) { 
			if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') { 
				$option_value_data = array();
				
				foreach ($option['option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product['tax_class_id'], $this->config->get('config_tax')));
						} else {
							$price = false;
						}
						
						$option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}
				
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option_value_data,
					'required'          => $option['required']
				);					
			} elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option['option_value'],
					'required'          => $option['required']
				);						
			}
		}

		#minimum
		if ($product['minimum']) {
			$minimum = $product['minimum'];
		} else {
			$minimum = 1;
		}

		$json['product'] = array(
			'product_id'                    => $product['product_id'],
			'seo_h1'                        => $product['seo_h1'],
			'name'                          => $product['name'],
			'manufacturer'                  => $product['manufacturer'],
			'model'                         => $product['model'],
			'reward'                        => $product['reward'],
			'points'                        => $product['points'],
			'image'                         => $image,
			'images'                        => $images,
			'quantity'                      => $product['quantity'],
			'price'                         => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
			'special'                       => $special,
			'discounts'                     => $discounts,
			'options'                       => $options,
			'minimum'                       => $minimum,
			'rating'                        => (int)$product['rating'],
			'description'                   => html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'),
			'attribute_groups'              => $this->model_catalog_product->getProductAttributes($product['product_id'])
		);


		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}


	public function addProduct() {
		$this->loadAdminModel('catalog/product');
		$language_id = $this->getLanguageId($this->config->get('config_language'));

		$entry_list = array(
			'model',
			'sku',
			'upc',
			'ean',
			'jan',
			'isbn',
			'mpn',
			'location',
			'quantity',
			'minimum',
			'subtract',
			'stock_status_id',
			'date_available',
			'manufacturer_id',
			'shipping',
			'price',
			'points',
			'weight',
			'weight_class_id',
			'length',
			'width',
			'height',
			'length_class_id',
			'status',
			'tax_class_id',
			'sort_order',
			'keyword'
		);

		$description_list = array(
			'name',
			'meta_keyword',
			'meta_description',
			'description',
			'tag',
			'seo_title',
			'seo_h1'
		);

		$data = $this->request->post;

		foreach ($entry_list as $entry) {
			if (!isset($data[$entry])) {
				$data[$entry] = '';
			}
		}

		foreach ($description_list as $entry) {
			if (!isset($data[$entry])) {
				$data['product_description'][$language_id][$entry] = '';
			} else {
				$data['product_description'][$language_id][$entry] = $data[$entry];
			}
		}

		$this->model_catalog_product->addProduct($data);
		$this->success('Product created');
	}

	public function deleteProduct() {
		$this->loadAdminModel('catalog/product');

		if (isset($this->request->get['product_id'])) {
			$product_id = $this->request->get['product_id'];
			$this->model_catalog_product->deleteProduct($product_id);

			$this->success('Product deleted');
		}
	}


	/**
	 * Generation of category tree
	 * 
	 * @param  int    $parent  Prarent category id
	 * @param  int    $level   Depth level
	 * @return array           Tree
	 */
	private function getCategoriesTree($parent = 0, $level = 1) {
		$this->load->model('catalog/category');
		$this->load->model('tool/image');
		
		$result = array();

		$categories = $this->model_catalog_category->getCategories($parent);

		if ($categories && $level > 0) {
			$level--;

			foreach ($categories as $category) {

				if ($category['image']) {
					$image = $this->model_tool_image->resize($category['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
				} else {
					$image = false;
				}

				$result[] = array(
					'category_id'   => $category['category_id'],
					'parent_id'     => $category['parent_id'],
					'name'          => $category['name'],
					'image'         => $image,
					'href'          => $this->url->link('product/category', 'category_id=' . $category['category_id']),
					'categories'    => $this->getCategoriesTree($category['category_id'], $level)
				);
			}

			return $result;
		}
	}

	/**
	 * 
	 */
	private function init($key_required = false) {

		$this->response->addHeader('Content-Type: application/json');

		if (!$this->config->get('web_api_status')) {
			$this->error(10, 'API is disabled');
		}

		if ($this->config->get('web_api_key') && (!isset($this->request->get['key']) || $this->request->get['key'] != $this->config->get('web_api_key'))) {
			$this->error(20, 'Invalid secret key');
		}

		if ($key_required && !$this->config->get('web_api_key')) {
			$this->error(30, 'This function is not public, set the API KEY');
		}
	}

	/**
	 * Loader admin models
	 * 
	 * @param string $model
	 */
	private function loadAdminModel($model) {
		$file  = DIR_APPLICATION . '../admin/model/' . $model . '.php';
		$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $model);
		
		if (file_exists($file)) {
			include_once($file);
			
			$this->registry->set('model_' . str_replace('/', '_', $model), new $class($this->registry));
		} else {
			trigger_error('Error: Could not load model ' . $model . '!');
			exit();					
		}
	}

	/**
	 * Error message responser
	 *
	 * @param string $code    Error code
	 * @param string $message Error message
	 */
	private function error($code = 0, $message = '') {

		# setOutput() is not called, set headers manually
		header('Content-Type: application/json');

		$json = array(
			'success'       => false,
			'code'          => $code,
			'message'       => $message
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			echo json_encode($json);
		}
		
		exit();
	}

	/**
	 * Success message responser
	 *
	 * @param string $message Success message
	 */
	private function success($message) {
		header('Content-Type: application/json');

		$json = array(
			'success'       => true,
			'message'       => $message
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			echo json_encode($json);
		}
		
		exit();
	}

	/**
	 * Gets language_id of the code (ru, en, etc)
	 * Oddly, the appropriate functions in the API was not found
	 *
	 * @param  string $lang Language code
	 * @return int
	 */
	private function getLanguageId($lang) {
		$query = $this->db->query('SELECT `language_id` FROM `' . DB_PREFIX . 'language` WHERE `code` = "'.$lang.'"');
		return $query->row['language_id'];
	}

}
