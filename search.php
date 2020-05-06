<?php

require_once(DIR_SYSTEM . 'adsmart_search.php');
            
class ControllerProductSearch extends Controller {


            
                public function autocomplete() {

                    $json = array();

                
                    if (isset($this->request->get['filter_name'])) {
                        
                        $this->load->model('extension/module/adsmart_search');
                        

                        $filter_name = $this->request->get['filter_name']; // posted by the ajax request
                        
                        $filter_description = 'false'; // used a string instead of a pure boolean to avoid 
                                                       // converting this var into boolean in all the other scripts 
                                                       // that make use of it
                        $adsmart_search_relevance = $this->config->get('adsmart_search_relevance');
                        if (isset($adsmart_search_relevance['description'])){
                            $filter_description = 'true';
                        }
                        
                        
                        // Set the current customer group id
                        if ($this->customer->isLogged()) {
        
                            if ( version_compare(VERSION, '2.0.0.0', '>=') ) {
                                $customer_group_id = $this->customer->getGroupId(); // Oc >= 2.0.0.0
                            } else {
                                $customer_group_id = $this->customer->getCustomerGroupId(); // Oc <= 1.5.6.4
                            }
                            
                        } else {
                            $customer_group_id = $this->config->get('config_customer_group_id');
                        }
                        

                        // Get the sort order:
                        
                        $sort_order = explode('-', $this->config->get('adsmart_search_sort_order'));
                        $sort       = isset($sort_order[0]) ? $sort_order[0] : '';
                        $order      = isset($sort_order[1]) ? $sort_order[1] : '';

                        $start  = 0;
                        
                        
                        // The maximum number of displayed results can be dynamically modified from the admin control panel,
                        // in that case the parameter 'admin_dropdown_limit' is posted to this function, otherwise the limit
                        // is read from the database.
                        
                        if (isset ($this->request->get['admin_dropdown_limit'])) {
                            $limit = $this->request->get['admin_dropdown_limit'];
                        }
                        else {
                            $limit = $this->config->get('adsmart_search_dropdown_max_num_results');
                        }
                        
                        $filter_name = $this->request->get['filter_name']; // posted by the ajax request
                        $filter_data = array(
                        
                            'live_search'           => true, // this flag tells if the request comes from the Live Search
                            'store_id'              => $this->config->get('config_store_id'), // Multi Store Support: get where the current search is coming from (added in v4.0)
                            'language_id'           => $this->config->get('config_language_id'), // Multi Language Support (added in v4.0)
                            'customer_group_id'     => $customer_group_id,
                            'filter_name'           => $filter_name,
                            'filter_tag'            => '',
                            'filter_description'    => $filter_description,
                            'filter_category_id'    => 0,
                            'filter_sub_category'   => '',
                        //  'filter_manufacturer_id'=> '',
                            'sort'                  => $sort,
                            'order'                 => $order,
                            'start'                 => $start,
                            'limit'                 => $limit
                        );
                        
                        
                        $products = $this->model_extension_module_adsmart_search->getProducts($filter_data);
                        
                        $this->load->model('tool/image');

            
            $filters= array(
            
                'filter_name'           =>  'search',
                'filter_tag'            =>  'tag',
                'filter_description'    =>  'description',
                'filter_category_id'    =>  'category_id',
                'filter_sub_category'   =>  'sub_category'
            );

            foreach ($filters as $old => $new){

                if (isset($this->request->get[$old])) {
                    $this->request->get[$new] = $this->request->get[$old];
                } 
                if (isset($this->request->get[$new])) {
                    $this->request->get[$old] = $this->request->get[$new];
                } 
            }

            
                        
                        if ($this->config->get('adsmart_search_dropdown_img_size') !=''){
                            $img_width = $this->config->get('adsmart_search_dropdown_img_size');
                        }
                        else {
                            $img_width = 38;
                        }
                        
                        
                        foreach ($products as $product) {

                            if ($product) {
                                if ($product['image']) {

					if($product['prohibit']){
						$image = $this->model_catalog_product->checkProhibitImage($product['image'], $img_width, $img_width);
					}else{
            
                                    

                                //Enable this code if you want a perfect resizing, without white spaces:
                                /*          
                                    $img_info = getimagesize(DIR_IMAGE.$product['image']); // returns an array of values, $img_info[0] is the width, $img_info[1] is the height
                                    $h_w_ratio = $img_info[1] / $img_info[0];
                                    $img_height = round($img_width * $h_w_ratio);
                                    $image = $this->model_tool_image->resize($product['image'], $img_width, $img_height);
                                */
                                
                                //  Comment this line if the above code is not commented:
                                    $image = $this->model_tool_image->resize($product['image'], $img_width, $img_width);

					}
            
                                    
                                } else {
                                
                                    $image = $this->model_tool_image->resize('img_not_found.gif', $img_width, $img_width);
                                }

                                
                                // $this->config->get('config_customer_price') is the flag under System -> Settings -> Tab Options -> Account
                                // (Only show prices when a customer is logged in)
                                
                                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                                    $price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                                } else {
                                    $price = '';
                                }
                                
                                if ( (float)$product['special'] && (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ) {
                                    $special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                                } else {
                                    $special = '';
                                }
                                
                                if ($this->config->get('config_review_status')) {
                                    $rating = $product['rating'];
                                } else {
                                    $rating = false;
                                }
                                
                                $item_price = $this->model_catalog_product->getProductPriceInfo( $product['product_id'], $product['price'], $product['tax_class_id'], '1');

                                if ($price && $item_price['lowest_price']) {
                                    $price = $item_price['lowest_price'];
                                    if ($item_price['lowest_price_previous']) {
                                        $price = $item_price['lowest_price_previous'];
                                        $special = $item_price['lowest_price'];
                                    }
                                }

                                $json[] = array(
                                    'product_id' => $product['product_id'],
                                    'image'      => $image,
                                    'name'       => strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')),  
                                    'model'      => $product['model'],
                                    'price'      => $price,
                                    'special'    => $special,
                                    'option'     => '',
                                    'rating'     => $rating,
                                    'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product['reviews']),
                                    'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id']) 
                                );
                            }
                        }
                    }
    
    
                    // Add debug info
                    if (ADSMART_SRC_DEBUG || ADSMART_SRC_DEBUG_SHOW_SQL || ADSMART_SRC_SPEED_TEST ) {
                        
                        $json[] = array(
                            'debug' => $_SESSION['adsmart_src_debug'],
                        );                      
                    }                   
                    $this->response->setOutput(json_encode($json));
                }

            
	public function index() {

            
                // Get the sort order:      
                $sort_order = explode('-', $this->config->get('adsmart_search_sort_order'));
                
                // Add the model adsmart_search
                $this->load->model('extension/module/adsmart_search');

            

                
                            $this->document->addStyle('catalog/view/theme/' . $this->config->get($this->config->get('config_theme') . '_directory') . '/css/bestseller.css');

                            // "search" (OC >= 1.5.5) / "filter_name" (OC < 1.5.5)
                            
                            if ( isset($this->request->get['search']) ) {
                            
                                $search = $this->request->get['search']; 
                                
                            } elseif ( isset($this->request->get['filter_name']) ) {
                            
                                $search = $this->request->get['filter_name'];
                                
                            } else {
                            
                                $search = '';
                            }
                            
                            $customer_id    = $this->customer->getId(); // 0 if Guest
                            
                            $this->load->model('catalog/product'); 
                            $this->model_catalog_product->addSearch($search, $customer_id);
            
		$this->load->language('product/search');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

            
            $filters= array(
            
                'filter_name'           =>  'search',
                'filter_tag'            =>  'tag',
                'filter_description'    =>  'description',
                'filter_category_id'    =>  'category_id',
                'filter_sub_category'   =>  'sub_category'
            );

            foreach ($filters as $old => $new){

                if (isset($this->request->get[$old])) {
                    $this->request->get[$new] = $this->request->get[$old];
                } 
                if (isset($this->request->get[$new])) {
                    $this->request->get[$old] = $this->request->get[$new];
                } 
            }

            

		if (isset($this->request->get['search'])) {
			$search = $this->request->get['search'];
		} else {
			$search = '';
		}

		if (isset($this->request->get['tag'])) {
			$tag = $this->request->get['tag'];
		} elseif (isset($this->request->get['search'])) {
			$tag = $this->request->get['search'];
		} else {
			$tag = '';
		}

		if (isset($this->request->get['description'])) {
			$description = $this->request->get['description'];
		} else {
			$description = '';
		}

		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		if (isset($this->request->get['sub_category'])) {
			$sub_category = $this->request->get['sub_category'];
		} else {
			$sub_category = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			
            
                $sort       = isset($sort_order[0]) ? $sort_order[0] : 'p.sort_order';
            
            
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			
            
                $order      = isset($sort_order[1]) ? $sort_order[1] : 'ASC';
            
            
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get($this->config->get('config_theme') . '_product_limit');
		}

		if (isset($this->request->get['search'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $this->request->get['search']);
		} elseif (isset($this->request->get['tag'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['tag'])) {
			$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['description'])) {
			$url .= '&description=' . $this->request->get['description'];
		}

		if (isset($this->request->get['category_id'])) {
			$url .= '&category_id=' . $this->request->get['category_id'];
		}

		if (isset($this->request->get['sub_category'])) {
			$url .= '&sub_category=' . $this->request->get['sub_category'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/search', $url)
		);

		if (isset($this->request->get['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') .  ' - ' . $this->request->get['search'];
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}

		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_search'] = $this->language->get('text_search');
		$data['text_keyword'] = $this->language->get('text_keyword');
		$data['text_category'] = $this->language->get('text_category');
		$data['text_sub_category'] = $this->language->get('text_sub_category');
		$data['text_quantity'] = $this->language->get('text_quantity');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_model'] = $this->language->get('text_model');
		$data['text_price'] = $this->language->get('text_price');
		$data['text_tax'] = $this->language->get('text_tax');
		$data['text_points'] = $this->language->get('text_points');
		$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
		$data['text_sort'] = $this->language->get('text_sort');
		$data['text_limit'] = $this->language->get('text_limit');

		$data['entry_search'] = $this->language->get('entry_search');
		$data['entry_description'] = $this->language->get('entry_description');

		$data['button_search'] = $this->language->get('button_search');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
		$data['button_list'] = $this->language->get('button_list');
		$data['button_grid'] = $this->language->get('button_grid');

		$data['compare'] = $this->url->link('product/compare');

		$this->load->model('catalog/category');

		// 3 Level Category Search
		$data['categories'] = array();

		$categories_1 = $this->model_catalog_category->getCategories(0);

		foreach ($categories_1 as $category_1) {
			$level_2_data = array();

			$categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

			foreach ($categories_2 as $category_2) {
				$level_3_data = array();

				$categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);

				foreach ($categories_3 as $category_3) {
					$level_3_data[] = array(
						'category_id' => $category_3['category_id'],
						'name'        => $category_3['name'],
					);
				}

				$level_2_data[] = array(
					'category_id' => $category_2['category_id'],
					'name'        => $category_2['name'],
					'children'    => $level_3_data
				);
			}

			$data['categories'][] = array(
				'category_id' => $category_1['category_id'],
				'name'        => $category_1['name'],
				'children'    => $level_2_data
			);
		}

		$data['products'] = array();

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$filter_data = array(
				'filter_name'         => $search,
				'filter_tag'          => $tag,
				'filter_description'  => $description,
				'filter_category_id'  => $category_id,
				'filter_sub_category' => $sub_category,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			);

			

			
            
            // Set the current customer group id
            if ($this->customer->isLogged()) {

                if ( version_compare(VERSION, '2.0.0.0', '>=') ) {
                    $customer_group_id = $this->customer->getGroupId(); // Oc >= 2.0.0.0
                } else {
                    $customer_group_id = $this->customer->getCustomerGroupId(); // Oc <= 1.5.6.4
                }
                
            } else {
                $customer_group_id = $this->config->get('config_customer_group_id');
            }
        
            $filter_data['customer_group_id'] = $customer_group_id;
                 
            
            // 1)   All the searches (like "search by tag") that don't involve the variables "filter_name" (OC < 1.5.5) / "search" (OC >= 1.5.5) 
            //      must be performed with the default method. Note: I have modified the script search.php to include both the variables
            //      get['search'] and get['filter_name'] (they contain the same value).
            //
            // 2)   The variable mfp is set by the extension Mega Filter Pro. If that variable is present, we use the standard Opencart search.
            
            
			$limit_product_total = 0;
            if ((ISSET($this->request->get['elk']) && $this->request->get['elk']) || ELASTICSEARCH_ON_FRONTEND && !(ISSET($this->request->get['tag']))
            ) { 
                $this->load->model('extension/module/elasticsearch');
                $return = $this->model_extension_module_elasticsearch->searchElkProducts($filter_data);
                $results = $return['arr_values'];
				$product_total = $return['arr_total'];
				$limit_product_total = $return['total_count'];
                $data['product_total'] = $limit_product_total;
            } else {
            
            if ($this->config->get('adsmart_search_status') == 1 && isset($this->request->get['search']) && !isset($this->request->get['mfp']) ) {
                $filter_name = $filter_data['filter_name']; // posted by the ajax request
                $filter_data['filter_name'] = $filter_name;
                $results = array();
                if ($filter_name) {
                    $results = $this->model_extension_module_adsmart_search->getProducts($filter_data);  // change the default method                                                                                // with $this->model_extension_module_adsmart_search->getProducts                    
                }
            }
            else {
                $results = $this->model_catalog_product->getProducts($filter_data);
            }
            


                $product_total = $this->registry->get('product_total')? $this->registry->get('product_total') : $this->model_catalog_product->getTotalProducts($filter_data);

                if (count($results)) {
                    $data['product_total'] = $product_total;
                } else {
                    $data['product_total'] = count($results);   
                }

            }
            

                

			foreach ($results as $result) {
				if ($result['image']) {

					if($result['prohibit']){
						$image = $this->model_catalog_product->checkProhibitImage($result['image'], $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height') );
					}else{
            
					$image = $this->model_tool_image->resize($result['image'], $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));

					}
            
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}


        $image_attribute = array( 'width' =>  $this->config->get('config_image_product_width'),  'height' => $this->config->get('config_image_product_height') ,'image' => false );
        $product = $this->model_catalog_product->getProductDisplay( $result['product_id'],$image_attribute,'product' );

              

        if ($this->customer->isLogged()) {
          $row_wishlist['wishlist_status'] = true ;
          // check for record
          $this->load->model('account/wishlist');
          $check_wishlist = $this->model_account_wishlist->checkWishlist($result['product_id']);
          if ($check_wishlist) {
            $row_wishlist['wishlist_product_status'] = true;
          } else {
            $row_wishlist['wishlist_product_status'] = false;
          }       
        } else {
          $this->session->data['redirect'] = $this->url->link('product/search', $url);
          $row_wishlist['wishlist_status'] = false ;
        }  
              
				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],

				'last_purchase_date'    => $result['last_purchase_date'],			
            
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],

        'wishlist'    => $row_wishlist,
              

'short_sales_pitch'     => $result['short_sales_pitch'], // Added by Elue Ticket - TKT#54354193    
            

            'lowest_price'     		=> $product['lowest_price'],
            'lowest_price_previous' => $product['lowest_price_previous'],
            'display_cart_button' 	=> $result['display_cart_button'],
            'total_review'    		=> $result['total_review'],
            'quantity'      	 	=> $result['quantity'],
            'back_order' 			=> $result['back_order'],
            'short_description' 	=> $result['short_description'],
            'disc_off' 				=> $product['disc_off'],
            'new_tag' 				=> ISSET($result['new']) ? $result['new'] : '',
              
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();


                /*
            
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.sort_order&order=ASC' . $url)
			);

                */
            $data['sorts'][] = array(
                'text'  => $this->language->get('text_popular'),
                'value' => 'p.sold-DESC',
                'href'  => $this->url->link('product/search', 'sort=p.sold&order=DESC' . $url)
            );
            



    // Advanced Smart Search - Relevance
            
            // Get the texts for the new types of sort orders 
            //$text_relevance = $this->config->get('adsmart_search_translation_txt_relevance');
            
            //$data['sorts'][] = array(
            //    'text'  =>  $text_relevance[(int)$this->config->get('config_language_id')],
            //    'value' =>  'relevance-DESC',
            //    'href'  =>  $this->url->link('product/search', 'sort=relevance' . $url)
            //);
            
    // End
            
            

                /*
            
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=DESC' . $url)
			);

                */
            

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=DESC' . $url)
			);

    // Advanced Smart Search 
    
        // Date Added Asc/Desc
            
            // Get the texts for the new types of sort orders 
            //$text_date_desc = $this->config->get('adsmart_search_translation_txt_date_desc');
            //$text_date_asc  = $this->config->get('adsmart_search_translation_txt_date_asc');
            

            //$data['sorts'][] = array(
            //    'text'  =>  $text_date_desc[(int)$this->config->get('config_language_id')],
            //    'value' =>  'p.date_added-DESC',
            //    'href'  =>  $this->url->link('product/search', 'sort=p.date_added&order=DESC' . $url)   
            //);
                
            //$data['sorts'][] = array(
            //    'text'  =>  $text_date_asc[(int)$this->config->get('config_language_id')],
            //    'value' =>  'p.date_added-ASC',
            //    'href'  =>  $this->url->link('product/search', 'sort=p.date_added&order=ASC' . $url)    
            //);
            
            // On Oc < 2 the array $data is $this->data
            //if (version_compare(VERSION, '1.5.6.4', '<=')) {
        
              //  $this->data['sorts'] = array_merge($this->data['sorts'], $data['sorts']);  
            //}
        
        
        // Relevance    
        
            if (version_compare(VERSION, '1.5.6.4', '<=')) {
        
                $this->data['adsmart_search_relevance'] = $this->config->get('adsmart_search_relevance');  
            } else {
                $data['adsmart_search_relevance'] = $this->config->get('adsmart_search_relevance');
            }
            
    // End
        
            

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=DESC' . $url)
				);


                /*
            
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=ASC' . $url)
				);

                */
            
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_stock'),
				'value' => 'p.quantity-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.quantity&order=DESC' . $url)
			);


            $data['sorts'][] = array(
                'text'  => $this->language->get('text_new_arrival'),
                'value' => 'p.product_id',
                'href'  => $this->url->link('product/search',  'sort=p.product_id&order=DESC' . $url)
            );

                /*
            
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.model&order=DESC' . $url)
			);

                */
            

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get($this->config->get('config_theme') . '_product_limit'), 60, 120));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/search', $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $limit_product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/search', $url . '&page={page}');					

			$data['pagination'] = $pagination->render();
			
			$data['results'] = sprintf($this->language->get('text_pagination'), ($limit_product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($limit_product_total - $limit)) ? $limit_product_total : ((($page - 1) * $limit) + $limit), $limit_product_total, ceil($limit_product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/search', '', true), 'canonical');
			} elseif ($page == 2) {
			    $this->document->addLink($this->url->link('product/search', '', true), 'prev');
			} else {
			    $this->document->addLink($this->url->link('product/search', $url . '&page='. ($page - 1), true), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/search', $url . '&page='. ($page + 1), true), 'next');
			}

			if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
				$this->load->model('account/search');

				if ($this->customer->isLogged()) {
					$customer_id = $this->customer->getId();
				} else {
					$customer_id = 0;
				}

				if (isset($this->request->server['REMOTE_ADDR'])) {
					$ip = $this->request->server['REMOTE_ADDR'];
				} else {
					$ip = '';
				}

				$search_data = array(
					'keyword'       => $search,
					'category_id'   => $category_id,
					'sub_category'  => $sub_category,
					'description'   => $description,
					'products'      => $product_total,
					'customer_id'   => $customer_id,
					'ip'            => $ip
				);

				$this->model_account_search->addSearch($search_data);
			}
		}
		
		$data['tag'] = $tag;
		$data['search'] = $search;
		$data['description'] = $description;
		$data['category_id'] = $category_id;
		$data['sub_category'] = $sub_category;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

         if(empty($data['products'])){
            $this->session->data['miner_type'] = "search empty";
            $this->load->controller('startup/data_miner');
          }
		
		$data['header'] = $this->load->controller('common/header');

        $group_category_1 = $this->model_catalog_category->getCategoryBestsellerByGroup(1);
        $group_category_2 = $this->model_catalog_category->getCategoryBestsellerByGroup(2);
        $group_category_3 = $this->model_catalog_category->getCategoryBestsellerByGroup(3);
        $data['data']['category'][1] = $group_category_1;
        $data['data']['category'][2] = $group_category_2;
        $data['data']['category'][3] = $group_category_3;    
                

		$this->response->setOutput($this->load->view('product/search', $data));
	}
}
