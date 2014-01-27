<?php

function pmli_pmxi_save_options($options){

	// Translate taxonomies options
	if ($options['pmli_translate_taxonomies_logic'] == 'only'){
		$options['pmli_taxonomies_list'] = explode(",", $options['pmli_taxonomies_only_list']); 
	}
	elseif ($options['pmli_translate_taxonomies_logic'] == 'all_except'){
		$options['pmli_taxonomies_list'] = explode(",", $options['pmli_taxonomies_except_list']); 	
	}

	return $options;

}

?>