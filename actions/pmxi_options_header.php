<?php
function pmli_pmxi_options_header($isWizard, $post){
		
	global $sitepress;
    $langs = $sitepress->get_active_languages(); 
	$lng_code = (!empty($_GET['lng'])) ? $_GET['lng'] : ((!empty($post['pmli']['lang_code'])) ? $post['pmli']['lang_code'] : $sitepress->get_current_language());	

	//if (! empty($_GET['lng'])):
	?>

	<div class="updated inline">
		<p><?php _e('<strong>WPML addon</strong>: options for '. $langs[$lng_code]['display_name'] .' language', 'pmxi_plugin' ); ?>  <img width="18" height="12" src="<?php echo ICL_PLUGIN_URL . '/res/flags/' . $langs[$lng_code]['code'] . '.png'; ?>" style="position:relative; top: 2px;"/></p>
	</div>	
	<?php
	/*elseif ( ! empty($post['pmli']) ):
	?>
	<h4><?php _e('WPML addon: options for '. $post['pmli']['lang_name_en'] .' language', 'pmxi_plugin' ); ?>  <img width="18" height="12" src="<?php echo ICL_PLUGIN_URL . '/res/flags/' . $post['pmli']['lang_code'] . '.png'; ?>" style="position:relative; top: 2px;"/> </h4>
	<?php
	endif;*/
}
?>