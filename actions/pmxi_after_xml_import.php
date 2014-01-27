<?php

function pmli_pmxi_after_xml_import( $import_id ){

	// Clear WPML cache
	delete_option('_icl_cache');

}

?>