<?php
	$html->addCrumb('Groups', '/groups/index');
	$html->addCrumb($group['Group']['name'], '/groups/dashboard/' . $group['Group']['id']); 
	$html->addCrumb('Documents', '/docs/group/' . $group['Group']['id']); 
?>
<?php
	echo $javascript->link('extjs/ux/ColumnNodeUI.js', false);
	echo $javascript->link('extjs/ux/FileUploadField.js', false);
?>
<div id="dashboard-div"></div>
<script type="text/javascript">
	laboratree.context = <?php echo $javascript->object($context); ?>;
	laboratree.docs.makeDashboard('dashboard-div');
	laboratree.docs.makeTree('Private', 'group', '<?php echo $group['Group']['id']; ?>', 0);
	laboratree.docs.makeTree('Public', 'group', '<?php echo $group['Group']['id']; ?>', 1);
</script>
