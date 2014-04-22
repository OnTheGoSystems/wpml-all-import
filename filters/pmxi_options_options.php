<?php
function pmli_pmxi_options_options($options, $isWizard){

	if ($isWizard){		
		
		$parent_import = new PMXI_Import_Record();

		isset($_GET['parent_import']) and $id = $_GET['parent_import'] and $parent_import->getById($id);

		if ( ! $parent_import->isEmpty() ){			

			$options = $parent_import->options + $options;					
			
		}

	}

	return $options;
}
?>