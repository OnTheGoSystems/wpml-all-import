<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'			=> __('ID', 'pmxi_plugin'),
	'name'			=> __('File', 'pmxi_plugin'),
	'xpath'			=> __('XPath', 'pmxi_plugin'),
	'post_count'	=> __('Records', 'pmxi_plugin'),
	'first_import'	=> __('First Import', 'pmxi_plugin'),
	'registered_on'	=> __('Last Import', 'pmxi_plugin')	
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
					case 'next_import':
						?>
						<td>
							<?php if ('0000-00-00 00:00:00' == $item['registered_on'] or empty($item['scheduled'])): ?>
								<em>never</em>
							<?php
							else:
								$task = new _PMXI_Import_Record_Cron_Parser($item['scheduled']);
								$task_date = $task->getNextRunDate();
								echo mysql2date(__('Y/m/d g:i a', 'pmxi_plugin'), $task_date->format('Y-m-d H:i:s'));
							endif;
							?>
						</td>
						<?php
						break;
					case 'name':
						?>
						<td style="padding-left:25px;">
							<strong><?php echo apply_filters("pmxi_import_name", (!empty($item['friendly_name'])) ? $item['friendly_name'] : $item['name'], $item['id']); ?></strong> <?php if ( (int) $item['triggered']) _e("<i> -> Import triggered...</i>"); if ( (int) $item['processing']) _e("<i> -> Import currently in progress....</i>");  ?><br>
							<?php if ($item['path']): ?>
								<em><?php echo str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $item['path'])); ?></em>
							<?php endif ?>
							<div class="row-actions">

								<?php do_action('pmxi_import_menu', $item['id'], $this->baseUrl); ?>

								<span class="edit"><a class="edit" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl)) ?>"><?php _e('Edit Template', 'pmxi_plugin') ?></a></span> |
								<span class="edit"><a class="edit" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) ?>"><?php _e('Edit Options', 'pmxi_plugin') ?></a></span> |
								<span class="update"><a class="update" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'update'), $this->baseUrl)) ?>"><?php _e('Re-Run Import', 'pmxi_plugin') ?></a></span> |

								<?php if ( in_array($item['type'], array('url', 'ftp', 'file'))): ?>
									<!--span class="edit get_cron_url"><a class="edit" href="javascript:void(0);" rel='<?php echo "wget -q -O /dev/null \"".home_url()."?import_key=".PMXI_Plugin::getInstance()->getOption('cron_job_key')."&import_id=".$item['id']."&action=processing\"\n" . "wget -q -O /dev/null "."\"".home_url()."?import_key=".PMXI_Plugin::getInstance()->getOption('cron_job_key')."&import_id=".$item['id']."&action=trigger"."\"";?>'><?php _e('Get Cron URL', 'pmxi_plugin') ?></a></span> |-->
									<span class="edit"><a class="edit" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'scheduling'), $this->baseUrl)) ?>"><?php _e('Cron Scheduling', 'pmxi_plugin') ?></a></span> |
								<?php endif; ?>
								<span class="update"><a class="update" href="<?php echo esc_url(add_query_arg(array('page' => 'pmxi-admin-import', 'id' => $item['id']), admin_url('admin.php'))) ?>"><?php _e('Re-Run With New File', 'pmxi_plugin') ?></a></span> |
								<span class="update"><a class="update" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'log'), $this->baseUrl)) ?>"><?php _e('Download Log', 'pmxi_plugin') ?></a></span> |
								<span class="delete"><a class="delete" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'delete'), $this->baseUrl)) ?>"><?php _e('Delete', 'pmxi_plugin') ?></a></span>
								<?php if ( ($item['imported'] + $item['skipped']) < $item['count'] and ! $item['options']['is_import_specified'] and ! (int) $item['triggered'] ):?>
								| <span class="update"><a class="update" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'update', 'type' => 'continue'), $this->baseUrl)) ?>"><?php _e('Continue import', 'pmxi_plugin') ?></a></span>
								<?php endif; ?>
							</div>
						</td>
						<?php
						break;
					case 'xpath':
						?>
						<td>
							<?php echo $item['xpath'];?>
						</td>
						<?php
						break;
					case 'post_count':
						?>
						<td>
							<strong><?php echo $item['post_count'] ?></strong>
						</td>
						<?php
						break;
					default:
						?>
						<td>
							<?php //do_action('pmxi_manage_imports_column', $column_id, $item); ?>
						</td>
						<?php
						break;
				endswitch;
				?>
			<?php endforeach; ?>
		</tr>		
	<?php endforeach; ?>
<?php endif ?>
