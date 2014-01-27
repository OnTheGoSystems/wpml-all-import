<?php 
/**
 * Manage Translations Imports
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
class PMLI_Admin_Manage extends PMLI_Controller_Admin {		
	
	/**
	 * Previous Imports list
	 */
	public function index($parent_import_id, $class) {
						
		$list = new PMXI_Import_List();
		$post = new PMXI_Post_Record();
		$by = array('parent_import_id' => $parent_import_id);		
		
		$this->data['list'] = $list->join($post->getTable(), $list->getTable() . '.id = ' . $post->getTable() . '.import_id', 'LEFT')
			->setColumns(
				$list->getTable() . '.*',
				'COUNT(' . $post->getTable() . '.post_id' . ') AS post_count'
			)
			->getBy($by, NULL, NULL, NULL, $list->getTable() . '.id');

		$this->data['parent_class'] = $class;
			
		$this->render();
	}
	
}