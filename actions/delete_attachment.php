<?php
function pmli_delete_attachment($pid){
	
	global $wpdb, $sitepress;

	if (get_post_type($pid) == 'attachment'){

		$parent_trid = $sitepress->get_element_trid( $pid, 'post_' . get_post_type($pid) );

		$translations = $wpdb->get_results($wpdb->prepare("SELECT * FROM ". $wpdb->prefix . "icl_translations WHERE element_type = %s AND trid = %d", 'post_' . get_post_type($pid), $parent_trid));						
		
		if (!empty($translations)){

			foreach ( $translations as $translation ) {
				
				if ($translation->element_id != $pid){
					$wpdb->delete( $wpdb->prefix . 'icl_translations', array( 'element_type' => 'post_' . get_post_type($pid), 'element_id' => $pid ), array( '%s', '%d' ) );
					$result = wp_delete_attachment($translation->element_id, true);
				}				

			}
		}
	}
}