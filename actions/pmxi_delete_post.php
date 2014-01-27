<?php

function pmli_pmxi_delete_post($id){

	$ids = (!is_array($id)) ? array($id) : $id;

	global $wpdb;

	if ( ! empty($ids) ){
		foreach ($ids as $pid) {
			$wpdb->delete( $wpdb->prefix . 'icl_translations', array( 'element_type' => 'post_' . get_post_type($pid), 'element_id' => $pid ), array( '%s', '%d' ) );
		}
	}

	// Clear WPML cache
	delete_option('_icl_cache');

}

?>