<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'		=> __('ID', 'pmxi_plugin'),
	'name'		=> __('File', 'pmxi_plugin'),
	'actions'	=> '',	
	'summary'	=> __('Summary', 'pmxi_plugin'),
	'info'		=> __('Info & Options', 'pmxi_plugin'),		
);

$columns = apply_filters('pmxi_manage_imports_columns', $columns);

?>

<?php $class = $parent_class; ?>

<?php if ( ! $list->isEmpty()): ?>
	
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
						<td style="padding-left: 30px;">
							<strong><?php echo apply_filters("pmxi_import_name", (!empty($item['friendly_name'])) ? $item['friendly_name'] : $item['name'], $item['id']); ?></strong><br>																		

							<?php if ($item['path']): ?>
								<?php if ( in_array($item['type'], array('upload'))): ?>
									<?php
									$path = $item['path'];
									$path_parts = pathinfo($item['path']);
									if ( ! empty($path_parts['dirname'])){
										$path_all_parts = explode('/', $path_parts['dirname']);
										$dirname = array_pop($path_all_parts);
										if ( pmxi_isValidMd5($dirname)){								
											$path = str_replace($dirname . '/', '', str_replace('temp/', '', $item['path']));
										}
									}
									?>
									<em><?php echo str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $path)); ?></em>
								<?php else:?>
								<em><?php echo str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $item['path'])); ?></em>
								<?php endif; ?>
							<?php endif ?>
							<div class="row-actions">

								<?php do_action('pmxi_import_menu', $item['id'], $this->baseUrl); ?>

								<?php

									$import_actions = array(
										'import_template' => array(
											'url' => ( ! $item['processing'] and ! $item['executing'] ) ? add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl) : '',
											'title' => __('Edit Import', 'pmxi_plugin'),
											'class' => 'edit'
										),
										'import_settings' => array( 
											'url' => ( ! $item['processing'] and ! $item['executing'] ) ? add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl) : '',  
											'title' => __('Import Settings', 'pmxi_plugin'), 
											'class' => 'edit'
										),						
										'delete' => array( 
											'url' => add_query_arg(array('id' => $item['id'], 'action' => 'delete'), $this->baseUrl),  
											'title' => __('Delete', 'pmxi_plugin'), 
											'class' => 'delete'
										),																												
									);
									
									$import_actions = apply_filters('pmxi_import_actions', $import_actions, $item );

									$ai = 1;
									foreach ($import_actions as $key => $action) {
										switch ($key) {
											default:
												?>
												<span class="<?php echo $action['class']; ?>">
													<?php if ( ! empty($action['url']) ): ?>
													<a class="<?php echo $action['class']; ?>" href="<?php echo esc_url($action['url']); ?>"><?php echo $action['title']; ?></a>
													<?php else: ?>
													<span class="wpallimport-disabled"><?php echo $action['title']; ?></span>
													<?php endif; ?>
												</span> <?php if ($ai != count($import_actions)): ?>|<?php endif; ?>
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
							if ($item['triggered'] and ! $item['processing']){
								_e('triggered with cron', 'pmxi_plugin');
								if ($item['last_activity'] != '0000-00-00 00:00:00'){
									$diff = ceil((time() - strtotime($item['last_activity']))/60);
									?>
									<br>
									<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
									<?php
										printf(__('last activity %s ago', 'pmxi_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
									?>
									</span>
									<?php
								}
							}
							elseif ($item['processing']){
								_e('currently processing with cron', 'pmxi_plugin'); echo '<br/>';
								printf('Records Processed %s', $item['imported']);
								if ($item['last_activity'] != '0000-00-00 00:00:00'){
									$diff = ceil((time() - strtotime($item['last_activity']))/60);
									?>
									<br>
									<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
									<?php
										printf(__('last activity %s ago', 'pmxi_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
									?>
									</span>
									<?php
								}
							}
							elseif($item['executing']){
								_e('Import currently in progress', 'pmxi_plugin');
								if ($item['last_activity'] != '0000-00-00 00:00:00'){
									$diff = ceil((time() - strtotime($item['last_activity']))/60);
									?>
									<br>
									<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
									<?php
										printf(__('last activity %s ago', 'pmxi_plugin'), human_time_diff(strtotime($item['last_activity']), time()));
									?>
									</span>
									<?php
								}
							}
							elseif($item['canceled'] and $item['canceled_on'] != '0000-00-00 00:00:00'){
								printf(__('Import Attempt at %s', 'pmxi_plugin'), get_date_from_gmt($item['canceled_on'], "m/d/Y g:i a")); echo '<br/>';
								_e('Import canceled', 'pmxi_plugin');
							}
							elseif($item['failed'] and $item['failed_on'] != '0000-00-00 00:00:00'){
								printf(__('Import Attempt at %s', 'pmxi_plugin'), get_date_from_gmt($item['failed_on'], "m/d/Y g:i a")); echo '<br/>';
								_e('Import failed, please check logs', 'pmxi_plugin');
							}
							else{
								$custom_type = get_post_type_object( $item['options']['custom_type'] );
								$cpt_name = ( ! empty($custom_type)) ? $custom_type->labels->singular_name : '';
								printf(__('Last run: %s', 'pmxi_plugin'), ($item['registered_on'] == '0000-00-00 00:00:00') ? __('never', 'pmxi_plugin') : get_date_from_gmt($item['registered_on'], "m/d/Y g:i a")); echo '<br/>';
								printf(__('%d %ss created', 'pmxi_plugin'), $item['created'], $cpt_name); echo '<br/>';
								printf(__('%d updated, %d skipped, %d deleted'), $item['updated'], $item['skipped'], $item['deleted']);
								//printf(__('%d records', 'pmxi_plugin'), $item['post_count']);
							}

							if ($item['settings_update_on'] != '0000-00-00 00:00:00' and $item['last_activity'] != '0000-00-00 00:00:00' and strtotime($item['settings_update_on']) > strtotime($item['last_activity'])){
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
									
							<a href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'scheduling'), $this->baseUrl)?>"><?php _e('Cron Scheduling', 'pmxi_plugin'); ?></a> <br>
							
							<a href="<?php echo add_query_arg(array('page' => 'pmxi-admin-history', 'id' => $item['id']), $this->baseUrl)?>"><?php _e('History Logs', 'pmxi_plugin'); ?></a>

						</td>
						<?php
						break;
					case 'actions':
						?>
						<td style="width: 130px;">
							<?php if ( ! $item['processing'] and ! $item['executing'] ): ?>
							<!--h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl); ?>"><?php _e('Edit', 'pmxi_plugin'); ?></a></h2-->
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'update'), $this->baseUrl); ?>"><?php _e('Run Import', 'pmxi_plugin'); ?></a></h2>
							<?php elseif ($item['processing']) : ?>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php _e('Cancel Cron', 'pmxi_plugin'); ?></a></h2>
							<?php elseif ($item['executing']) : ?>
							<h2 style="float:left;"><a class="add-new-h2" href="<?php echo add_query_arg(array('id' => $item['id'], 'action' => 'cancel'), $this->baseUrl); ?>"><?php _e('Cancel', 'pmxi_plugin'); ?></a></h2>
							<?php endif; ?>
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
