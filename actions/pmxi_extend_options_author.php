<?php
function pmli_pmxi_extend_options_author($post_type){
	$wpml_controller = new PMLI_Admin_Import();										
	$wpml_controller->index($post_type);
}
?>