<?php

function pmli_pmxi_manage_imports_column($column, $import){
	
	if ($column == "langs"){	

		global $sitepress;
		$langs = $sitepress->get_active_languages();	
		?>

		<div style="width:100%; text-align:center;">

		<?php		

		if ( empty($langs) ) { 
			?>
			<a href="#help" class="help" title="<?php _e('Add Languages at WPML setting page.', 'pmxi_plugin') ?>">?</a>
			<?php			
		}
		elseif ( ! empty( $import['options']['pmli']['lang_code'] ) ) {
		    
		    $lang_codes = array();

		    $list = new PMXI_Import_List();
			$post = new PMXI_Post_Record();
			$list->setColumns($list->getTable() . '.*')->getBy('parent_import_id', $import['id']);
			if ( ! $list->isEmpty()) {
				foreach ($list as $item){
					if ( ! empty($item['options']['pmli']['lang_code']) ) $lang_codes[] = $item['options']['pmli']['lang_code'];
				}	
			}
			?>			
		   	<?php
			if ( ! empty($langs) ){	
				?>
				<table style="margin: 0 auto;">					
				<?php
				foreach ($langs as $code => $langInfo) { 
					if ($code == $import['options']['pmli']['lang_code']) continue;				
					?>
					<tr>
						<td style="padding:2px;">
							<img width='18' height='12' src='<?php echo ICL_PLUGIN_URL . "/res/flags/" . $code . ".png"; ?>' style="position:relative; top: 2px;"/>
						</td>
						<td style="padding:5px;">
							<?php
								echo (in_array($code, $lang_codes)) ? __("Done", "pmxi_plugin") : "<a href='" . esc_url(add_query_arg(array('page' => 'pmxi-admin-import', 'parent_import' => $import['id'], 'lng' => $code)), $_SERVER['REQUEST_URI']) . "'><img src='" . ICL_PLUGIN_URL . "/res/img/add_translation.png'/></a>";				
							?>
						</td>
					</tr>
					<?php					
				}
				?>
				</table>
				<?php
			}

		}			
		else {
			?>			
			<a href="#help" class="help" title="<?php _e('To add translations import please re-save the import options.', 'pmxi_plugin') ?>">?</a>			
			<?php
		}
		?>
		</div>
		<?php
	}
}

?>