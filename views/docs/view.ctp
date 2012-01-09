<?php 
	$type = Inflector::pluralize(Inflector::humanize($doc['Doc']['table_type']));
	$controller = Inflector::pluralize($doc['Doc']['table_type']);
	if(isset($group_id) && !empty($group_id) && $doc['Doc']['table_type'] == 'project')
	{
		$html->addCrumb('Groups', '/groups/index'); 
		$html->addCrumb($group_name, '/groups/dashboard/' . $group_id);
	}

	$html->addCrumb($type, '/' . $controller . '/index');
	$html->addCrumb(addslashes($name), '/' . $controller  . '/dashboard/' . $doc['Doc']['table_id']); 
	$html->addCrumb('Documents', '/docs/' . $doc['Doc']['table_type'] . '/' . $doc['Doc']['table_id']); 
	$html->addCrumb($doc['Doc']['title'], '/docs/view/' . $doc['Doc']['id']); 
?>
<div id="docs-view-div"></div>
<script type="text/javascript">
	laboratree.context = <?php echo $javascript->object($context); ?>;
	laboratree.docs.makeView('docs-view-div', '<?php echo $doc['Doc']['id']; ?>', '<?php echo $html->url('/docs/view/' . $doc['Doc']['id'] . '.json'); ?>');
</script>
