<?php 
function pmli_pmxi_base_url($baseUrl){
	
	if ( empty($_GET['id']) ){

		$parent_import = $_GET['parent_import'] and $baseUrl = add_query_arg('parent_import', $parent_import, $baseUrl) and $lng = $_GET['lng'] and $baseUrl = add_query_arg('lng', $lng, $baseUrl);

	}

	return $baseUrl;
}
?>