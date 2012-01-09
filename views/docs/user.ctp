<?php
	$html->addCrumb('Users', '/user/index');
	$html->addCrumb($user['User']['name'], '/users/dashboard/' . $user['User']['id']); 
	$html->addCrumb('Documents', '/docs/user/' . $user['User']['id']); 
?>
<?php
	echo $javascript->link('extjs/ux/ColumnNodeUI.js', false);
	echo $javascript->link('extjs/ux/FileUploadField.js', false);
?>
<div id="dashboard-div"></div>
<script type="text/javascript">
	laboratree.context = <?php echo $javascript->object($context); ?>;
	laboratree.docs.makeDashboard('dashboard-div');
	laboratree.docs.makeTree('Private', 'user', '<?php echo $user['User']['id']; ?>', 0);
	laboratree.docs.makeTree('Public', 'user', '<?php echo $user['User']['id']; ?>', 1);
</script>
