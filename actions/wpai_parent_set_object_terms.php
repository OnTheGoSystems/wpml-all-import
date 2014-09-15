<?php
function pmli_wpai_parent_set_object_terms($object_id, $taxonomy){

	$_pmli_object_terms = get_post_meta($object_id, '_pmli_object_terms_' . $taxonomy, true);		

	$children = get_posts( array(
		'post_parent' 	=> $object_id,
		'posts_per_page'=> -1,
		'post_type' 	=> 'product_variation',
		'fields' 		=> 'ids',
		'post_status'	=> 'publish',
		'orderby' 		=> 'ID',
		'order' 		=> 'ASC'
	) );		
	
	if ( $children ) {
		foreach ( $children as $child ) {
			$_child_object_terms = get_post_meta($child, '_pmli_object_terms_' . $taxonomy, true);
			if ( ! empty($_child_object_terms)){
				$attribute = get_post_meta($child, 'attribute_' . $taxonomy, true);
				foreach ($_child_object_terms as $t) {	
					if ( ! in_array($t, $_pmli_object_terms)) $_pmli_object_terms[] = $t;
				}
			}
			else{
				$attribute = get_post_meta($child, 'attribute_' . $taxonomy, true);
				if ( ! empty($attribute) ){										
					update_post_meta($child, 'pmli_parent_product', $object_id);										
				}
			}
		}
	}
	
}