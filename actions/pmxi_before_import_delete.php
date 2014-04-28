<?php

function pmli_pmxi_before_import_delete($import, $is_delete_posts){

	$list = new PMXI_Import_List();	
	$by = array('parent_import_id' => $import->id);		
	
	$list->setColumns($list->getTable() . '.*')->getBy($by, NULL, NULL, NULL, $list->getTable() . '.id');

	if ( ! $list->isEmpty() ):		

		foreach ($list->convertRecords() as $item):					

			$item->delete( ! $is_delete_posts );

		endforeach;

	endif;
}

?>