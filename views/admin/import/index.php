<div class="pmxi_collapsed closed">
	<div class="pmxi_content_section">
		<div class="pmxi_collapsed_header">
			<h3><?php _e('WPML Add-On','pmxi_plugin');?></h3>	
		</div>
		<div class="pmxi_collapsed_content">
			<table class="form-table" style="max-width:none;">
				<tr>
					<td colspan="3" style="padding-top:20px;">					
						<div class="input" style="margin-bottom:15px; position:relative;">				
							<input type="radio" id="pmli_auto_matching_<?php echo $post_type; ?>" class="switcher" name="pmli_duplicate_matching" value="auto" <?php echo 'manual' != $post['pmli_duplicate_matching'] ? 'checked="checked"': '' ?>/>
							<label for="pmli_auto_matching_<?php echo $post_type; ?>"><?php _e('Automatic Parent Record Matching', 'pmxi_plugin' )?></label><br>
							<div class="switcher-target-pmli_auto_matching_<?php echo $post_type; ?>"  style="padding-left:17px;">
								<div class="input">
									<label><?php _e("Unique key"); ?></label>						
									<input type="text" class="smaller-text" name="pmli_unique_key" style="width:300px;" value="<?php echo esc_attr($post['pmli_unique_key']) ?>"/>
									<a href="#help" class="help" title="<?php _e('Plugin will going across parent import to find record for translation. In case, when plugin will not found the parent record, then translation will not created.', 'pmxi_plugin') ?>">?</a>
								</div>
							</div>
							<input type="radio" id="pmli_manual_matching_<?php echo $post_type; ?>" class="switcher" name="pmli_duplicate_matching" value="manual" <?php echo 'manual' == $post['pmli_duplicate_matching'] ? 'checked="checked"': '' ?>/>
							<label for="pmli_manual_matching_<?php echo $post_type; ?>"><?php _e('Manual Parent Record Matching', 'pmxi_plugin' )?></label>
							<div class="switcher-target-pmli_manual_matching_<?php echo $post_type; ?>" style="padding-left:17px;">
								<div class="input">						
									<input type="radio" id="pmli_duplicate_indicator_title_<?php echo $post_type; ?>" class="switcher" name="pmli_duplicate_indicator" value="title" <?php echo 'title' == $post['pmli_duplicate_indicator'] ? 'checked="checked"': '' ?>/>
									<label for="pmli_duplicate_indicator_title_<?php echo $post_type; ?>"><?php _e('match by Title', 'pmxi_plugin' )?></label><br>
									<input type="radio" id="pmli_duplicate_indicator_content_<?php echo $post_type; ?>" class="switcher" name="pmli_duplicate_indicator" value="content" <?php echo 'content' == $post['pmli_duplicate_indicator'] ? 'checked="checked"': '' ?>/>
									<label for="pmli_duplicate_indicator_content_<?php echo $post_type; ?>"><?php _e('match by Content', 'pmxi_plugin' )?></label><br>
									<input type="radio" id="pmli_duplicate_indicator_custom_field_<?php echo $post_type; ?>" class="switcher" name="pmli_duplicate_indicator" value="custom field" <?php echo 'custom field' == $post['pmli_duplicate_indicator'] ? 'checked="checked"': '' ?>/>
									<label for="pmli_duplicate_indicator_custom_field_<?php echo $post_type; ?>"><?php _e('match by Custom field', 'pmxi_plugin' )?></label><br>
									<span class="switcher-target-pmli_duplicate_indicator_custom_field_<?php echo $post_type; ?>" style="vertical-align:middle; padding-left:17px;">
										<?php _e('Name', 'pmxi_plugin') ?>
										<input type="text" name="pmli_custom_duplicate_name" value="<?php echo esc_attr($post['pmli_custom_duplicate_name']) ?>" />
										<?php _e('Value', 'pmxi_plugin') ?>
										<input type="text" name="pmli_custom_duplicate_value" value="<?php echo esc_attr($post['pmli_custom_duplicate_value']) ?>" />
									</span>
								</div>
							</div>
						</div>
						<hr/>
						<div class="input">
							<input type="hidden" name="pmli_taxonomies_list" value="0" />
							<input type="hidden" name="pmli_is_translate_taxonomies" value="0" />
							<input type="checkbox" id="pmli_is_translate_taxonomies_<?php echo $post_type; ?>" name="pmli_is_translate_taxonomies" value="1" class="switcher" <?php echo $post['pmli_is_translate_taxonomies'] ? 'checked="checked"': '' ?> />
							<label for="pmli_is_translate_taxonomies_<?php echo $post_type; ?>"><?php _e('Translate Taxonomies (incl. Categories and Tags)', 'pmxi_plugin') ?></label>
							<div class="switcher-target-pmli_is_translate_taxonomies_<?php echo $post_type; ?>" style="padding-left:17px;">
								<?php
								$existing_taxonomies = array();
								$hide_taxonomies = (class_exists('PMWI_Plugin')) ? array('product_type', 'post_format') : array('post_format');
								$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array($post_type), 'object'), array_flip($hide_taxonomies));
								if (!empty($post_taxonomies)): 
									foreach ($post_taxonomies as $ctx):  if ( "" == $ctx->labels->name ) continue;
										$existing_taxonomies[] = $ctx->name;
									endforeach;
								endif;
								?>
								<div class="input" style="margin-bottom:3px;">								
									<input type="radio" id="pmli_translate_taxonomies_logic_full_update_<?php echo $post_type; ?>" name="pmli_translate_taxonomies_logic" value="full_translate" <?php echo ( "full_translate" == $post['pmli_translate_taxonomies_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="pmli_translate_taxonomies_logic_full_update_<?php echo $post_type; ?>" style="position:relative; top:1px;"><?php _e('Translate all taxonomies', 'pmxi_plugin') ?></label>
								</div>
								<div class="input" style="margin-bottom:3px;">								
									<input type="radio" id="pmli_translate_taxonomies_logic_all_except_<?php echo $post_type; ?>" name="pmli_translate_taxonomies_logic" value="all_except" <?php echo ( "all_except" == $post['pmli_translate_taxonomies_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="pmli_translate_taxonomies_logic_all_except_<?php echo $post_type; ?>" style="position:relative; top:1px;"><?php _e('Leave these taxonomies alone, translate all others', 'pmxi_plugin') ?></label>
									<div class="switcher-target-pmli_translate_taxonomies_logic_all_except_<?php echo $post_type; ?> pmxi_choosen" style="padding-left:17px;">							
										<span class="hidden choosen_values"><?php if (!empty($existing_taxonomies)) echo implode(',', $existing_taxonomies);?></span>
										<input class="choosen_input" value="<?php if (!empty($post['pmli_taxonomies_list']) and "all_except" == $post['pmli_translate_taxonomies_logic']) echo implode(',', $post['pmli_taxonomies_list']); ?>" type="hidden" name="pmli_taxonomies_except_list"/>																				
									</div>
								</div>
								<div class="input" style="margin-bottom:3px;">								
									<input type="radio" id="pmli_translate_taxonomies_logic_only_<?php echo $post_type; ?>" name="pmli_translate_taxonomies_logic" value="only" <?php echo ( "only" == $post['pmli_translate_taxonomies_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="pmli_translate_taxonomies_logic_only_<?php echo $post_type; ?>" style="position:relative; top:1px;"><?php _e('Translate only these taxonomies, leave the rest alone', 'pmxi_plugin') ?></label>
									<div class="switcher-target-pmli_translate_taxonomies_logic_only_<?php echo $post_type; ?> pmxi_choosen" style="padding-left:17px;">							
										<span class="hidden choosen_values"><?php if (!empty($existing_taxonomies)) echo implode(',', $existing_taxonomies);?></span>
										<input class="choosen_input" value="<?php if (!empty($post['pmli_taxonomies_list']) and "only" == $post['pmli_translate_taxonomies_logic']) echo implode(',', $post['pmli_taxonomies_list']); ?>" type="hidden" name="pmli_taxonomies_only_list"/>										
									</div>
								</div>					
							</div>
						</div>
						<?php
							global $sitepress;
							$langs = $sitepress->get_active_languages();  
							$lng_code = ($_GET['lng']) ? $_GET['lng'] : $sitepress->get_default_language();	
						?>
						<input type="hidden" name="pmli[lang_code]" value="<?php echo (!empty($_GET['lng'])) ? $lng_code : $post['pmli']['lang_code']; ?>"/>
						<input type="hidden" name="pmli[lang_name]" value="<?php echo (!empty($_GET['lng'])) ? $langs[$lng_code]['native_name'] : $post['pmli']['lang_name']; ?>"/>
						<input type="hidden" name="pmli[lang_name_en]" value="<?php echo (!empty($_GET['lng'])) ? $langs[$lng_code]['english_name'] : $post['pmli']['lang_name_en']; ?>"/>							
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>