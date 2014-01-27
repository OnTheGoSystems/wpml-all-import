<?php
function pmli_pmxi_choose_file_header(){

	if ( ! empty($_GET['parent_import']) and ! empty($_GET['lng']) and empty($_GET['id'])){

		global $sitepress;
	    $langs = $sitepress->get_active_languages();      
	    if ( ! empty($langs[$_GET['lng']])){
	    	$import = new PMXI_Import_Record();
	    	if ( ! $import->getById($_GET['parent_import'])->isEmpty() ){
				?>
				<div class="reimported_notify">					
					<p><?php printf( __('You are importing a <b>%s</b> <img width="18" height="12" src="%s" style="position:relative; top:2px;"/> translation for import: <b>%s</b>' , 'pmxi_plugin' ), $langs[$_GET['lng']]['display_name'], ICL_PLUGIN_URL . '/res/flags/' . $langs[$_GET['lng']]['code'] . '.png' , (($import->friendly_name) ? $import->friendly_name : $import->name));?></p>
				</div>
				<?php
			}
		}
	}
}
?>