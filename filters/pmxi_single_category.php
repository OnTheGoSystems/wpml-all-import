<?php
function pmli_pmxi_single_category($category = array(), $cname){
	
	if ( ! empty($category) ){
		$input = new PMXI_Input();
		$import_id = $input->get('id', false);
		if ($import_id){
			$import = new PMXI_Import_Record();
			$import->getById($import_id);
			if (!$import->isEmpty()){
				if ( $import->options['pmli_is_translate_taxonomies'] ){	
					if ($import->options['pmli_translate_taxonomies_logic'] == "all_except" and !empty($import->options['pmli_taxonomies_list']) 
						and is_array($import->options['pmli_taxonomies_list']) and in_array($cname, $import->options['pmli_taxonomies_list'])) return $category;
					if ($import->options['pmli_translate_taxonomies_logic'] == "only" and ((!empty($import->options['pmli_taxonomies_list']) 
						and is_array($import->options['pmli_taxonomies_list']) and ! in_array($cname, $import->options['pmli_taxonomies_list'])) or empty($import->options['pmli_taxonomies_list']))) return $category;

					$category['name'] .= ' @' . $import->options['pmli']['lang_code'];
					return $category;
				}
			}
		}
		elseif( ! empty(PMXI_Plugin::$session->data['pmxi_import'])){
			if ( PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_is_translate_taxonomies'] ){	
				if (PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_translate_taxonomies_logic'] == "all_except" and !empty(PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list']) 
					and is_array(PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list']) and in_array($cname, PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list'])) return $category;
				if (PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_translate_taxonomies_logic'] == "only" and ((!empty(PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list']) 
					and is_array(PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list']) and ! in_array($cname, PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list'])) or empty(PMXI_Plugin::$session->data['pmxi_import']['options']['pmli_taxonomies_list']))) return $category;

				$category['name'] .= ' @' . PMXI_Plugin::$session->data['pmxi_import']['options']['pmli']['lang_code'];
				return $category;
			}
		}
	}
	return $category;
}