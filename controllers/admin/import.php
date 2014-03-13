<?php 
/**
 * Import configuration wizard
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */

class PMLI_Admin_Import extends PMLI_Controller_Admin {		
		
	public function index( $post_type = 'post' ) {	

		$default = PMLI_Plugin::get_default_import_options();

		$this->data['id'] = $id = $this->input->get('id');

		$this->data['import'] = $import = new PMXI_Import_Record();			
		if ( ! $id or $import->getById($id)->isEmpty()) { // specified import is not found

			$this->data['parent_import'] = $parent_import = $this->input->get('parent_import');

			if ( ! $parent_import or $import->getById($parent_import)->isEmpty()) {

				$post = $this->input->post(			
					$default			
				);

			}
			else {
				$post = $this->input->post(
					$this->data['import']->options
					+ $default			
				);
				$post['pmli_unique_key'] = $post['unique_key'];	
			}
		}
		else 
			$post = $this->input->post(
				$this->data['import']->options
				+ $default			
			);		

		if (empty($this->data['import']->parent_import_id) and empty($_GET['lng'])) return;

		$this->data['is_loaded_template'] = (!empty(PMXI_Plugin::$session->data['pmxi_import']['is_loaded_template'])) ? PMXI_Plugin::$session->data['pmxi_import']['is_loaded_template'] : false;

		$load_options = $this->input->post('load_template');

		if ($load_options) { // init form with template selected
			
			$template = new PMXI_Template_Record();
			if ( ! $template->getById($this->data['is_loaded_template'])->isEmpty()) {	
				$post = (!empty($template->options) ? $template->options : array()) + $default;				
			}
			
		} elseif ($load_options == -1){
			
			$post = $default;
							
		}
		
		$this->data['post_type'] = $post_type;

		$this->data['post'] =& $post;
		
		$this->render();

	}	
		
}
