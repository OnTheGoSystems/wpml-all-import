<?php
function pmli_pmxi_template_options($template_options, $isWizard){

	if ($isWizard){

		$default = array(
			'title' => '',
			'content' => '',
			'name' => '',
			'is_keep_linebreaks' => 0,
			'is_leave_html' => 0,
			'fix_characters' => 0
		);	
		
		$parent_import = new PMXI_Import_Record();

		isset($_GET['parent_import']) and $id = $_GET['parent_import'] and $parent_import->getById($id);

		if ( ! $parent_import->isEmpty() ){			

			$template_options = $parent_import->template + $default;

		}
		else if ( ! empty($_GET['id']) ) {

			$import = new PMXI_Import_Record();

			$id = $_GET['id'] and $import->getById($id);

			$template_options['pmli'] = $import->options['pmli'];

		}
		
	}else if ( ! empty($_GET['id']) ) {

		$import = new PMXI_Import_Record();

		$id = $_GET['id'] and $import->getById($id);

		$template_options['pmli'] = $import->options['pmli'];

	}	


	return $template_options;
}
?>