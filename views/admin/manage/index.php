<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'		=> __('ID', 'pmxi_plugin'),
	'name'		=> __('File', 'pmxi_plugin'),
	'actions'	=> '',	
	'summary'	=> __('Summary', 'pmxi_plugin'),
	'info'		=> __('Info & Options', 'pmxi_plugin'),	
	'delete'	=> '',
);

$columns = apply_filters('pmxi_manage_imports_columns', $columns);

?>

<?php $class = $parent_class; ?>

<?php if ( ! $list->isEmpty()): ?>
	<?php			
	//$class = $parent_class;
	?>
	<?php foreach ($list as $item): ?>
		
		<tr class="<?php echo $class; ?>" valign="middle">					
			<th scope="row" class="check-column" style="text-align:center;">
				<input type="checkbox" id="item_<?php echo $item['id'] ?>" name="items[]" value="<?php echo esc_attr($item['id']) ?>" />
			</th>
			<?php foreach ($columns as $column_id => $column_display_name): ?>
				<?php
				switch ($column_id):
					case 'id':
						?>
						<th valign="top" scope="row">
							<?php echo $item['id'] ?>
						</th>
						<?php
						break;
					case 'first_import':
						?>
						<td>
							<?php if ('0000-00-00 00:00:00' == $item['first_import']): ?>
								<em>never</em>
							<?php else: ?>
								<?php echo mysql2date(__('Y/m/d g:i a', 'pmxi_plugin'), $item['first_import']) ?>
							<?php endif ?>
						</td>
						<?php
						break;
					case 'registered_on':
						?>
						<td>
							<?php if ('0000-00-00 00:00:00' == $item['registered_on']): ?>
								<em>never</em>
							<?php else: ?>
								<?php echo mysql2date(__('Y/m/d g:i a', 'pmxi_plugin'), $item['registered_on']) ?>
							<?php endif ?>
						</td>
						<?php
						break;
					case 'name':
						?>
						<td style="padding-left:20px;">
							<strong><?php echo apply_filters("pmxi_import_name", (!empty($item['friendly_name'])) ? $item['friendly_name'] : $item['name'], $item['id']); ?></strong> <?php if ( (int) $item['triggered']) _e("<i> -> Import triggered...</i>"); if ( (int) $item['processing']) _e("<i> -> Import currently in progress....</i>");  ?><br>
							<?php if ($item['path']): ?>
								<em><?php echo str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $item['path'])); ?></em>
							<?php endif ?>
							<div class="row-actions">

								<?php do_action('pmxi_import_menu', $item['id'], $this->baseUrl); ?>

								<?php

									$import_actions = array(
										'import_settings' => array( 
											'url' => ( ! $item['processing'] and ! $item['executing'] ) ? add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl) : 'javascript:void(0);',  
											'title' => __('Change File / Import Settings', 'pmxi_plugin'), 
											'class' => 'edit'
										),																																	
									);
									
									$import_actions = apply_filters('pmxi_import_actions', $import_actions, $item );

									$ai = 1;
									foreach ($import_actions as $key => $action) {
										switch ($key) {
											default:
												?>
												<span class="<?php echo $action['class']; ?>"><a class="<?php echo $action['class']; ?>" href="<?php echo esc_url($action['url']); ?>"><?php echo $action['title']; ?></a></span> <?php if ($ai != count($import_actions)): ?>|<?php endif; ?>
												<?php
												break;
										}												
										$ai++;		
									}	

								?>																			

							</div>
						</td>
						<?php
						break;
					case 'summary':
						?>
						<td>
							<?php 

							if ($item['processing']){
								_e('currently processing via cron', 'pmxi_plugin');
							}
							elseif($item['executing']){
								_e('Import currently in progress', 'pmxi_plugin');
							}
							elseif($item['canceled'] and $item['canceled_on'] != '0000-00-00 00:00:00'){
								printf(__('Import Attempt at %s', 'pmxi_plugin'), mysql2date("m/d/Y g:i a", $item['canceled_on'])); echo '<br/>';
								_e('Import canceled', 'pmxi_plugin');
							}
							else{
								$custom_type = get_post_type_object( $item['options']['custom_type'] );
								printf(__('Last run: %s', 'pmxi_plugin'), ($item['registered_on'] == '0000-00-00 00:00:00') ? __('never', 'pmxi_plugin') : mysql2date("m/d/Y g:i a", $item['registered_on'])); echo '<br/>';
								printf(__('%d %ss created', 'pmxi_plugin'), $item['created'], $custom_type->labels->singular_name); echo '<br/>';
								printf(__('%d updated, %d skipped, %d deleted'), $item['updated'], $item['skipped'], $item['deleted']);
							}

							if ($item['settings_update_on'] != '0000-00-00 00:00:00' and strtotime($item['settings_update_on']) > strtotime($item['registered_on'])){
								echo '<br/>';
								?>
								<strong><?php _e('settings edited since last run', 'pmxi_plugin'); ?></strong>																				
								<?php
							}

							?>
						</td>
						<?php
						break;
					case 'info':
						?>
						<td>
							<?php if ( in_array($item['type'], array('url', 'ftp', 'file'))):?>
							<a href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'scheduling'), $this->baseUrl)?>"><?php _e('Cron Scheduling', 'pmxi_plugin'); ?></a> <br>
							<?php endif; ?>
							<a href="<?php echo add_query_arg(array('page' => 'pmxi-admin-history', 'id' => $item['id']), $this->baseUrl)?>"><?php _e('Import History Logs', 'pmxi_plugin'); ?></a>
						</td>
						<?php
						break;
					case 'actions':
						?>
						<td>
							<?php if ( ! $item['processing'] and ! $item['executing'] ): ?>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl); ?>"><?php _e('Edit', 'pmxi_plugin'); ?></a></h2>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'update'), $this->baseUrl); ?>"><?php _e('Run Import', 'pmxi_plugin'); ?></a></h2>
							<?php elseif ($item['processing']) : ?>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php _e('Cancel Cron Processing', 'pmxi_plugin'); ?></a></h2>
							<?php elseif ($item['executing']) : ?>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php _e('Cancel', 'pmxi_plugin'); ?></a></h2>
							<?php endif; ?>
						</td>
						<?php
						break;
					case 'delete':
						?>
						<td>
							<span class="delete"><a href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'delete'), $this->baseUrl)?>" class="delete">X</a></span>
						</td>
						<?php
						break;
					default:
						?>
						<td>
							&nbsp;
						</td>
						<?php
						break;
				endswitch;
				?>
			<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
<?php endif ?>
