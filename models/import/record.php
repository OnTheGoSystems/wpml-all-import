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

		if ( ! $importData['import']->parent_import_id ){ 			

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

		$parentImport = new PMXI_Import_record();

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
			
			$postRecord->clear();
			// find corresponding article among previously imported
			$postRecord->getBy(array(
				'unique_key' => $this->data['pmli_unique_keys'][$i],
				'import_id' => $import->parent_import_id,
			));
			
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

		$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 'element_type' => 'post_' . get_post_type($pid), 'element_id' => $pid ), array( '%s', '%d' ) );					

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

		if ( ! $import->parent_import_id ){ 

			$taxonomies = array_diff_key(get_taxonomies_by_object_type(array(get_post_type($pid)), 'object'), array_flip(array('post_format', 'product_type')));

			foreach (array_keys($taxonomies) as $cname) {				

				$txes_list = get_the_terms($pid, $cname);								

				if ( ! is_wp_error($txes_list) ) {
					
					if (!empty($txes_list)):						

						foreach ($txes_list as $key => $t) {																					
							
							$translation = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND element_id = %d AND language_code = %s", 'tax_' . $cname, $t->term_taxonomy_id, $import->options['pmli']['lang_code']) );							

							if ( empty($translation) ){

								$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 'element_type' => 'tax_' . $cname, 'element_id' => $t->term_taxonomy_id, 'language_code' => $import->options['pmli']['lang_code'] ), array( '%s', '%d' ) );	

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

				if ($import->options['pmli_translate_taxonomies_logic'] == "all_except" and !empty($import->options['pmli_taxonomies_list']) 
					and is_array($import->options['pmli_taxonomies_list']) and in_array($cname, $import->options['pmli_taxonomies_list'])) continue;
				if ($import->options['pmli_translate_taxonomies_logic'] == "only" and ((!empty($import->options['pmli_taxonomies_list']) 
					and is_array($import->options['pmli_taxonomies_list']) and ! in_array($cname, $import->options['pmli_taxonomies_list'])) or empty($import->options['pmli_taxonomies_list']))) continue;

				$txes_list = wp_get_object_terms($pid, $cname, array('orderby' => 'term_id'));				

				$parent_txes_list = wp_get_object_terms($this->parent_id, $cname, array('orderby' => 'term_id'));				

				if ( ! is_wp_error($txes_list) ) {
					
					if (!empty($txes_list)):						

						$assigned_txes = array();

						foreach ($txes_list as $key => $t) {														

							if ( ! empty($parent_txes_list[$key]) ) {

								$parent_tx_trid = $sitepress->get_element_trid( $parent_txes_list[$key]->term_taxonomy_id, 'tax_' . $cname );																								
							
								$translation = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND trid = %d AND language_code = %s AND source_language_code = %s", 'tax_' . $cname, $parent_tx_trid, $import->options['pmli']['lang_code'], $parentImport->options['pmli']['lang_code']) );								

								if ( empty($translation) ){

									$el_id = $t->term_taxonomy_id;

									if ($t->term_id == $parent_txes_list[$key]->term_id){
										$newname = $t->name;
										if ( $t->name == $parent_txes_list[$key]->name ){
											$newname = $t->name . ' @' . $import->options['pmli']['lang_code'];
										}
										$parent = 0;
										if ($t->parent){
											$parent_term = get_term_by('id', $t->parent, $cname);
											if (!is_wp_error($parent_term)){
												$parent_parent_tx_trid = $sitepress->get_element_trid( $parent_term->term_taxonomy_id, 'tax_' . $cname );																								
												$parent_translation = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM ". $this->wpdb->prefix . "icl_translations WHERE element_type = %s AND trid = %d AND language_code = %s AND source_language_code = %s", 'tax_' . $cname, $parent_parent_tx_trid, $import->options['pmli']['lang_code'], $parentImport->options['pmli']['lang_code']) );
												if (!empty($parent_translation) and !empty($parent_translation->element_id)){
													$parent = $parent_translation->element_id;
												}
											}
										}
										
										$term = wp_insert_term($newname, $cname, array(
											'parent' => (int) $parent
										));
										if (! is_wp_error($term) ){		
											$el_id = $term['term_taxonomy_id'];
											$term = get_term_by('id', $term['term_id'], $cname);
											if (!in_array($term->slug, $assigned_txes)) $assigned_txes[] = $term->slug;
										}
										else continue;
									}
								    // if translation is equal to default value
									elseif ( $t->name == $parent_txes_list[$key]->name ) {
										wp_update_term($t->term_id, $cname, array(
										  'name' => $t->name . ' @' . $import->options['pmli']['lang_code'],
										));
										if (!in_array($t->slug, $assigned_txes)) $assigned_txes[] = $t->slug;
									}
									else{
										$assigned_txes[] = $t->slug;
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

									$term = get_term_by('id', $translation->element_id, $cname);

									if ( !is_wp_error($term) and !in_array($term->slug, $assigned_txes)) $assigned_txes[] = $term->slug;

								}	
								//$assigned_txes[] = $parent_txes_list[$key]->slug;							
							}
						}

						wp_set_object_terms($pid, $assigned_txes, $cname);

					endif;
					
				}

			}				

		} else {



		}

		// \[taxonomies]

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
