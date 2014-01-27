<?php

function pmli_pmxi_options_validation($errors, $post, $importObj){

	if ( $importObj->parent_import_id and '' == $post['pmli_unique_key'] ) {
		$errors->add('form-validation', __('Expression for `Parent Post Unique Key` must be set, use the same expression as specified for post title if you are not sure what to put there', 'pmxi_plugin'));
	}

	return $errors;
}

?>