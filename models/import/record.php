<?php

class PMLI_Import_Record extends PMLI_Model_Record {		

	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();

	public $parsing_data = array();

	public $parent_id;	

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) { 
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}	
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMLI_Import_Record
	 * @chainable
	 */
	public function parse($parsing_data = array()) { //$import, $count, $xml, $logger = NULL, $chunk = false, $xpath_prefix = ""

		if ( ! $parsing_data['import']->parent_import_id ) return;

		$this->parsing_data = $parsing_data;

		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content			

		$this->data = array();		
		$tmp_files  = array();

		$cxpath = $this->parsing_data['xpath_prefix'] . $this->parsing_data['import']->xpath;

		$this->parsing_data['chunk'] == 1 and $this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], __('Composing translations...', 'pmxi_plugin'));		
		$this->parsing_data['chunk'] == 1 and $this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], __('Composing unique keys...', 'pmxi_plugin'));
		$this->data['pmli_unique_keys'] = XmlImportParser::factory($this->parsing_data['xml'], $cxpath, $this->parsing_data['import']->options['pmli_unique_key'], $file)->parse(); $tmp_files[] = $file;

		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure					

		foreach ($tmp_files as $file) { // remove all temporary files created
			unlink($file);
		}

		return $this->data;
	}				

	public function import($importData = array()){ //$pid, $i, $import, $articleData, $xml, $is_cron = false, $xpath_prefix = ""

		if ( empty($importData['import']->parent_import_id) ){ 			

			$translation = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND element_id = %d AND language_code = %s", 'post_' . get_post_type($importData['pid']), $importData['pid'], $importData['import']->options['pmli']['lang_code']));				

			if ( empty($translation) ){
				
				$this->wpdb->insert( 
					$this->wpdb->prefix . 'icl_translations',
					array( 
						'element_type' => 'post_' . get_post_type($importData['pid']), 
						'element_id' => $importData['pid'],
						'trid' => $importData['pid'],
						'language_code' => $importData['import']->options['pmli']['lang_code']
					), 
					array( 
						'%s', 
						'%d',
						'%d',
						'%s'							
					) 
				);
				
			}			

			return;
		}

		extract($importData);

		$cxpath = $xpath_prefix . $import->xpath;						

		// search default translation

		$parentImport = new PMXI_Import_Record();

		$parentImport->getById($import->parent_import_id);

		$postRecord = new PMXI_Post_Record();		

		$parent_id = false;				

		// if Auto Matching re-import option selected
		if ("manual" != $import->options['pmli_duplicate_matching']){
			
			// find corresponding article among previously imported
			
			$postRecord->clear();
			$postRecord->getBy(array(
				'unique_key' => $this->data['pmli_unique_keys'][$i],
				'import_id' => $import->parent_import_id,
			));
			if ( ! $postRecord->isEmpty() ) 
				$parent_id = $postRecord->post_id;
														
		// if Manual Matching re-import option seleted
		} else {
			
			if ('custom field' == $import->options['pmli_duplicate_indicator']) {
				$tmp_files = array();
				$custom_duplicate_value = XmlImportParser::factory($xml, $cxpath, $import->options['pmli_custom_duplicate_value'], $file)->parse(); $tmp_files[] = $file;
				$custom_duplicate_name = XmlImportParser::factory($xml, $cxpath, $import->options['pmli_custom_duplicate_name'], $file)->parse(); $tmp_files[] = $file;
				foreach ($tmp_files as $file) { // remove all temporary files created
					unlink($file);
				}
			}
			else{
				count($titles) and $custom_duplicate_name = $custom_duplicate_value = array_fill(0, count($titles), '');					
			}

			// handle duplicates according to import settings
			if ($duplicates = pmxi_findDuplicates($articleData, $custom_duplicate_name[$i], $custom_duplicate_value[$i], $import->options['pmli_duplicate_indicator'])) {															
				$duplicate_id = array_shift($duplicates);
				if ($duplicate_id) {														
					$parent_id = $duplicate_id;
				}
			}

		}

		$this->parent_id = $parent_id;

		global $sitepress;

		$parent_trid = $sitepress->get_element_trid( $parent_id, 'post_' . get_post_type($parent_id) );

		if ( ! $parent_trid )
			$parent_trid = $sitepress->get_element_trid( $parent_id, 'post_' . get_post_type($pid) );
							
		// if there is no parent entry then remove translation
		if ( ! $parent_trid or ( (get_post_type($pid) != get_post_type($parent_id)) and !in_array(get_post_type($pid), array('product', 'product_variation'))) ) {

			wp_delete_post($pid, true);

			$postRecord->clear();
			$postRecord->getBy(array(
				'post_id' => $pid,
				'import_id' => $import->id,
			));

			if ( ! $postRecord->isEmpty() ) 
				$postRecord->delete();

			return;
		
		}		

		// sync translation slug
		$parent_post = get_post($parent_id);			

		if ( ! empty($parent_post) and $parent_post->post_title == $articleData['post_title']) {			

			$this->wpdb->update( $this->wpdb->posts, array( 'post_name' => $parent_post->post_name ), array( 'ID' => $pid ) );				

		}

		$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 
			'element_type' => 'post_' . get_post_type($pid), 
			'element_id' => $pid, 
			//'source_language_code' => $parentImport->options['pmli']['lang_code'] 
		), 
		array( '%s', '%d' ) );
		
		$this->wpdb->insert( 
			$this->wpdb->prefix . 'icl_translations',
			array( 
				'element_type' => 'post_' . get_post_type($pid), 
				'element_id' => $pid,
				'trid' => $parent_trid,
				'language_code' => $import->options['pmli']['lang_code'],
				'source_language_code' => $parentImport->options['pmli']['lang_code']
			), 
			array( 
				'%s', 
				'%d',
				'%d',
				'%s',
				'%s' 
			) 
		);		
		
	}	

	public function saved_post( $importData ){		

		global $sitepress;

		extract($importData);					
			
		if ( empty( $import->parent_import_id ) ){ 

			$taxonomies = array_diff_key(get_taxonomies_by_object_type(array(get_post_type($pid)), 'object'), array_flip(array('post_format', 'product_type')));

			foreach (array_keys($taxonomies) as $cname) {				

				$txes_list = get_the_terms($pid, $cname);								

				if ( ! is_wp_error($txes_list) ) {
					
					if (!empty($txes_list)):						

						foreach ($txes_list as $key => $t) {																					
							
							$translation = $this->wpdb->get_row( 
								$this->wpdb->prepare(
									"SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND element_id = %d AND language_code = %s", 
									'tax_' . $cname, 
									$t->term_taxonomy_id, 
									$import->options['pmli']['lang_code']
								) 
							);							

							if ( empty($translation) ){

								$this->wpdb->delete( 
									$this->wpdb->prefix . 'icl_translations', array( 'element_type' => 'tax_' . $cname, 'element_id' => $t->term_taxonomy_id, 'language_code' => $import->options['pmli']['lang_code'] ), 
									array( '%s', '%d' ) 
								);	

								$this->wpdb->insert(
									$this->wpdb->prefix . 'icl_translations',
									array(
										'element_type' => 'tax_' . $cname, 
										'element_id' => $t->term_taxonomy_id,
										'trid' => $t->term_taxonomy_id,
										'language_code' => $import->options['pmli']['lang_code']										
									), 
									array( 
										'%s', 
										'%d',
										'%d',
										'%s'										
									) 
								);	

							}

						}

					endif;
				}
			}

			return;
		}

		// [taxonomies]

		if ( $import->options['pmli_is_translate_taxonomies'] ){					

			$parentImport = new PMXI_Import_record();

			$parentImport->getById($import->parent_import_id);	
			
			$taxonomies = array_diff_key(get_taxonomies_by_object_type(array(get_post_type($pid)), 'object'), array_flip(array('post_format', 'product_type')));			

			foreach (array_keys($taxonomies) as $cname) {

				// if WPML setting defined current taxonomy like not translatible then skip it
				if ( ! $sitepress->is_translated_taxonomy($cname) ) continue;

				if ($import->options['pmli_translate_taxonomies_logic'] == "all_except" and !empty($import->options['pmli_taxonomies_list']) 
					and is_array($import->options['pmli_taxonomies_list']) and in_array($cname, $import->options['pmli_taxonomies_list'])) continue;
				if ($import->options['pmli_translate_taxonomies_logic'] == "only" and ((!empty($import->options['pmli_taxonomies_list']) 
					and is_array($import->options['pmli_taxonomies_list']) and ! in_array($cname, $import->options['pmli_taxonomies_list'])) or empty($import->options['pmli_taxonomies_list']))) continue;

				$txes_list = wp_get_object_terms($pid, $cname, array('orderby' => 'term_id'));				

				$parent_txes_list = wp_get_object_terms($this->parent_id, $cname, array('orderby' => 'term_id'));						

				if ($import->options['custom_type'] == 'product' and class_exists('PMWI_Plugin')){		

					if (strpos($cname, "pa_") === 0 ){								
								
						// update attribute meta key is case when it's product variation record													

						$children = get_posts( array(
							'post_parent' 	=> $pid,
							'posts_per_page'=> 1,
							'post_type' 	=> 'product_variation',
							'fields' 		=> 'ids',
							'post_status'	=> 'publish',
							'orderby' 		=> 'ID',
							'order' 		=> 'ASC'
						) );
						
						if ( $children ) {
							foreach ( $children as $child ) {																													

								$txes_list = array_merge($txes_list, wp_get_object_terms($child, $cname, array('orderby' => 'term_id')));	

							}
						}

						$children = get_posts( array(
							'post_parent' 	=> $this->parent_id,
							'posts_per_page'=> 1,
							'post_type' 	=> 'product_variation',
							'fields' 		=> 'ids',
							'post_status'	=> 'publish',
							'orderby' 		=> 'ID',
							'order' 		=> 'ASC'
						) );
						
						if ( $children ) {
							foreach ( $children as $child ) {																					

								$parent_txes_list = array_merge($parent_txes_list, wp_get_object_terms($child, $cname, array('orderby' => 'term_id')));	

							}
						}
					}					

				}

				$translations = array();

				if ( ! is_wp_error($txes_list) ) {
					
					if ( ! empty($txes_list) ):

						$assigned_txes = array();						

						foreach ($txes_list as $key => $t) {														

							if ( ! empty($parent_txes_list[$key]) ) {								

								$parent_tx_trid = $sitepress->get_element_trid( $parent_txes_list[$key]->term_taxonomy_id, 'tax_' . $cname );																								
							
								$translation = $this->wpdb->get_row( 
									$this->wpdb->prepare(
										"SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND trid = %d AND language_code = %s AND source_language_code = %s", 
										'tax_' . $cname, 
										$parent_tx_trid, 
										$import->options['pmli']['lang_code'], 
										$parentImport->options['pmli']['lang_code']
									) 
								);

								$translated_term_slug = false;

								if ( empty($translation) ){

									$el_id = $t->term_taxonomy_id;																																			

									if ($t->term_id == $parent_txes_list[$key]->term_id){
										
										$newname = $t->name . ' @' . $import->options['pmli']['lang_code'];																			

										$parent = 0;

										if ( $t->parent and $parent_txes_list[$key]->parent ){

											$parent_term = get_term_by('id', $t->parent, $cname);
											
											$parent_term_default = get_term_by('id', $parent_txes_list[$key]->parent, $cname);
											
											$parent = $this->get_wpmu_parent_id($parent_term, $parent_term_default->term_id, $cname, $import, $parentImport, $assigned_txes, $pid, $$translations);
										}
										
										$term = term_exists($newname, $cname, (int)$parent);	
										
										if ( empty($term) and !is_wp_error($term) ){																																
											$term = term_exists(htmlspecialchars($newname), $cname, (int)$parent);	
										
											if ( empty($term) and !is_wp_error($term) ){		
												$term = wp_insert_term($newname, $cname, array(
													'parent' => (int) $parent
												));																													
											}
										}															
										
										if ( ! is_wp_error($term) ){		
											$el_id = $term['term_taxonomy_id'];																					
										
											$term = get_term_by('id', $term['term_id'], $cname);
											if ($term and ! is_wp_error($term) and ! in_array($term->slug, $assigned_txes)) 
												$translated_term_slug = $term->slug;											

										}
										else continue;
										
									}
									else{									
										$translated_term_slug = $t->slug;
									}									

									$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 'element_type' => 'tax_' . $cname, 'element_id' => $el_id), array( '%s', '%d' ) );	

									$this->wpdb->insert(
										$this->wpdb->prefix . 'icl_translations',
										array(
											'element_type' => 'tax_' . $cname, 
											'element_id' => $el_id,
											'trid' => $parent_tx_trid,
											'language_code' => $import->options['pmli']['lang_code'],
											'source_language_code' => $parentImport->options['pmli']['lang_code']
										), 
										array( 
											'%s', 
											'%d',
											'%d',
											'%s',
											'%s' 
										) 
									);

								}
								else{

									$term = get_term_by('term_taxonomy_id', $translation->element_id, $cname);

									if ( empty($term) ){
										$term = get_term_by('id', $translation->element_id, $cname);
										if ( ! is_wp_error($term) and ! in_array($term->slug, $assigned_txes)) 
											$translated_term_slug = $term->slug;									
									}
									elseif ( ! is_wp_error($term) and ! in_array($term->slug, $assigned_txes)) 
										$translated_term_slug = $term->slug;									

								}	
								
								if ($translated_term_slug and ! in_array($translated_term_slug, $assigned_txes)){ 

									$assigned_txes[] = $translated_term_slug;	

									$translations[$t->slug] = $translated_term_slug;							

								}	
							}
						}
						
						wp_set_object_terms($pid, (!empty($assigned_txes)) ? $assigned_txes : NULL, $cname);																	

						if ( ! empty($assigned_txes) and $import->options['custom_type'] == 'product' and class_exists('PMWI_Plugin')){

							update_post_meta($pid, '_pmli_object_terms_' . $cname , $assigned_txes);
							
							$_default_attributes = get_post_meta($pid, '_default_attributes', true);							

							if (strpos($cname, "pa_") === 0 ){								
								
								// update attribute meta key is case when it's product variation record
								if ( ! empty($_default_attributes)){								

									$children = get_posts( array(
										'post_parent' 	=> $pid,
										'posts_per_page'=> 1,
										'post_type' 	=> 'product_variation',
										'fields' 		=> 'ids',
										'post_status'	=> 'publish',
										'orderby' 		=> 'ID',
										'order' 		=> 'ASC'
									) );
									
									if ( $children ) {
										foreach ( $children as $child ) {																					

											$attribute = get_post_meta($child, 'attribute_' . $cname, true);
											
											if ( ! empty($attribute) ){																								

												if ( ! empty($translations[$attribute])){

													update_post_meta($child, '_pmli_object_terms_' . $cname, array($translations[$attribute]));
													update_post_meta($child, 'attribute_' . $cname, $translations[$attribute]);
													if ( ! empty($_default_attributes[$cname])){
														$_default_attributes[$cname] = $translations[$attribute];
														update_post_meta($pid, '_default_attributes', $_default_attributes);
													}
													wp_set_object_terms($child, array($translations[$attribute]), $cname);	

												}																				

											}
										}
									}
								}								

								$attribute = get_post_meta($pid, 'attribute_' . $cname, true);

								// update default attributes for parent product
								foreach ($txes_list as $key => $t) {	
									if ( ! empty($_default_attributes[$cname]) and ! empty($parent_txes_list[$key]) and ! empty($translations[$t->slug]) ){
										$_default_attributes[$cname] = $translations[$t->slug];
									}
									elseif ( ! empty($attribute) and ! empty($translations[$attribute]) ){										
										update_post_meta($pid, 'attribute_' . $cname, $translations[$attribute]);										
									}
								}								
								if ( empty($_default_attributes) ) {										
									
									$pmli_parent_product = get_post_meta($pid, 'pmli_parent_product', true);

									if ( ! empty( $pmli_parent_product)){
										
										$_default_attributes = get_post_meta($pmli_parent_product, '_default_attributes', true);		

										$_pmli_object_terms = get_post_meta($pmli_parent_product, '_pmli_object_terms_' . $cname, true);

										$children = get_posts( array(
											'post_parent' 	=> $pmli_parent_product,
											'posts_per_page'=> -1,
											'post_type' 	=> 'product_variation',
											'fields' 		=> 'ids',
											'post_status'	=> 'publish',
											'orderby' 		=> 'ID',
											'order' 		=> 'ASC'
										) );		
										
										if ( $children ) {
											foreach ( $children as $child ) {
												$_child_object_terms = get_post_meta($child, '_pmli_object_terms_' . $cname, true);
												if ( ! empty($_child_object_terms)){
													$attribute = get_post_meta($child, 'attribute_' . $cname, true);
													foreach ($_child_object_terms as $t) {
														
														if ( ! empty($attribute)){ 
															update_post_meta($child, 'attribute_' . $cname, $t);
															if ( ! empty($_default_attributes[$cname])){
																$_default_attributes[$cname] = $t;
																update_post_meta($pmli_parent_product, '_default_attributes', $_default_attributes);
															}
														}

														if ( ! in_array($t, $_pmli_object_terms)) $_pmli_object_terms[] = $t;
													}
												}												
											}
										}
										
										wp_set_object_terms($pmli_parent_product, (!empty($_pmli_object_terms)) ? $_pmli_object_terms : NULL, $cname);													
																											
									}
								}
							}
						}						

					endif;
					
				}

			}	

			delete_post_meta($pid, 'pmli_parent_product');			

		} 	

		// \[taxonomies]

	}

	public function get_wpmu_parent_id( $t, $parent_id, $cname, $import, $parentImport, & $assigned_txes, $pid, & $translations ){

		global $sitepress;

		$parent_term_default = get_term_by('id', $parent_id, $cname);

		$parent_tx_trid = $sitepress->get_element_trid( $parent_term_default->term_taxonomy_id, 'tax_' . $cname );																								
							
		$translation = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND trid = %d AND language_code = %s AND source_language_code = %s", 'tax_' . $cname, $parent_tx_trid, $import->options['pmli']['lang_code'], $parentImport->options['pmli']['lang_code']) );								

		$term = false;

		if ( empty($translation) ){

			$el_id = $t->term_taxonomy_id;

			$translated_term_slug = false;

			if ( $t->term_id == $parent_term_default->term_id ){
				
				$newname = $t->name . ' @' . $import->options['pmli']['lang_code'];
				
				$parent = 0;

				if ( $t->parent ){
					
					$parent_term = get_term_by('id', $t->parent, $cname);

					if ( ! is_wp_error($parent_term) ){
						
						$parent = $this->get_wpmu_parent_id($parent_term, $parent_term_default->parent, $cname, $import, $parentImport, $assigned_txes, $pid);

					}
				}
				
				$term = term_exists($newname, $cname, (int)$parent);	
										
				if ( empty($term) and !is_wp_error($term) ){
					$term = term_exists(htmlspecialchars($newname), $cname, (int)$parent);		
					if ( empty($term) and !is_wp_error($term) ){		
						$term = wp_insert_term($newname, $cname, array(
							'parent' => (int) $parent
						));																	
					}
				}
				
				if ( ! is_wp_error($term) ){		
					$el_id = $term['term_taxonomy_id'];
					$term = get_term_by('id', $term['term_id'], $cname);
					if ( ! in_array($term->slug, $assigned_txes)) 
						$translated_term_slug = $term->slug;
				}

			}
			else{
				$translated_term_slug = $t->slug;
			}

			$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 'element_type' => 'tax_' . $cname, 'element_id' => $el_id), array( '%s', '%d' ) );	

			$this->wpdb->insert(
				$this->wpdb->prefix . 'icl_translations',
				array(
					'element_type' => 'tax_' . $cname, 
					'element_id' => $el_id,
					'trid' => $parent_tx_trid,
					'language_code' => $import->options['pmli']['lang_code'],
					'source_language_code' => $parentImport->options['pmli']['lang_code']
				), 
				array( 
					'%s', 
					'%d',
					'%d',
					'%s',
					'%s' 
				) 
			);	

			if ($translated_term_slug and ! in_array($translated_term_slug, $assigned_txes)){ 
				
				$assigned_txes[] = $translated_term_slug;				
				
				$translations[$t->slug] = $translated_term_slug;				

			}		

			return ($term and ! is_wp_error($term)) ? $term->term_id : $t->term_id;
		}
		else {

			$term = get_term_by('id', $translation->element_id, $cname);

			if ( ! is_wp_error($term) and ! in_array($term->slug, $assigned_txes)) {
				
				$assigned_txes[] = $term->slug;									
					
			}

			return ( ! is_wp_error($term) ) ? $translation->element_id : 0;

		}
	}

	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	public function filtering($var){
		return ("" == $var) ? false : true;
	}		
}
