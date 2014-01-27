<?php
function pmli_pmxi_import_name($name, $import_id){

	$import = new PMXI_Import_Record();

	if ( ! $import->getById($import_id)->isEmpty() and !empty($import['options']['pmli']['lang_code'])) {

		global $sitepress;

		$name =  "<img width='18' height='12' src='". ICL_PLUGIN_URL . "/res/flags/" . ( (!empty($import['options']['pmli']['lang_code'])) ? $import['options']['pmli']['lang_code'] : $sitepress->get_default_language() ) . ".png' style='position:relative; top: 2px; margin-right:10px;'/>" . $name;

	}

	return $name;
}
?>