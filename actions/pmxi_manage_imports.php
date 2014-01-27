<?php

function pmli_pmxi_manage_imports($import, $class){

	$wpml_manage = new PMLI_Admin_Manage();										
	$wpml_manage->index($import['id'], $class);

}

?>