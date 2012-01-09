<?php
	$html->addCrumb('Groups', '/groups/index'); 
	$html->addCrumb($group_name, '/groups/dashboard/' . $group_id);
	$html->addCrumb('Projects', '/projects/group/' . $group_id);
	$html->addCrumb($project['Project']['name'], '/projects/dashboard/' . $project['Project']['id']);
	$html->addCrumb('Documents', '/docs/project/' . $project['Project']['id']);
?>
<?php
	echo $javascript->link('extjs/ux/ColumnNodeUI.js', false);
	echo $javascript->link('extjs/ux/FileUploadField.js', false);
?>
<div id="dashboard-div"></div>
<script type="text/javascript">
	laboratree.context = <?php echo $javascript->object($context); ?>;
	laboratree.docs.makeDashboard('dashboard-div');
	laboratree.docs.makeTree('Private', 'project', '<?php echo $project['Project']['id']; ?>', 0);
	laboratree.docs.makeTree('Public', 'project', '<?php echo $project['Project']['id']; ?>', 1);
</script>
