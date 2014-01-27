<?php
function pmli_pmxi_options_options($options, $isWizard){

	if ($isWizard){

		$default = PMXI_Plugin::get_default_import_options();
		
		$parent_import = new PMXI_Import_Record();

		$id = $_GET['parent_import'] and $parent_import->getById($id);

		if ( ! $parent_import->isEmpty() ){			

			$options = $parent_import->options + $default;					

		}

	}

	return $options;
}
?>