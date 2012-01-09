<?php
	/* Define Constants */
	if(!defined('DOCS_APP'))
	{
		define('DOCS_APP', APP . DS . 'plugins' . DS . 'docs');
	}

	if(!defined('DOCS_CONFIGS'))
	{
		define('DOCS_CONFIGS', DOCS_APP . DS . 'config');
	}

	if(!defined('DOCUMENTS'))
	{
		define('DOCUMENTS', DOCS_APP . DS . 'documents');
	}

	/* Include Config File */
	require_once(DOCS_CONFIGS . DS . 'docs.php');

	/* Setup Permissions */
	try {
		$parent = $this->addPermission('docs', 'Documents');
		$this->addPermission('docs.view', 'View Document', 1, $parent);

		$this->addPermission('docs.add', 'Add Document', 2, $parent);
		$this->addPermission('docs.edit', 'Edit Document', 4, $parent);
		$this->addPermission('docs.delete', 'Delete Document', 8, $parent);

		$this->addPermission('docs.checkin', 'Check In Document', 16, $parent);
		$this->addPermission('docs.checkout', 'Check Out Document', 32, $parent);
		$this->addPermission('docs.cancel_checkout', 'Cancel Document Check Out', 64, $parent);

		$this->addPermissionDefaults(array(
			'group' => array(
				'docs' => array(
					'Administrator' => 127,
					'Manager' => 127,
					'Member' => 51,
				),
			),
			'project' => array(
				'docs' => array(
					'Administrator' => 127,
					'Manager' => 127,
					'Member' => 51,
				),
			),
		));
	} catch(Exception $e) {
		// TODO; Do something
	}

	/* Add Listeners */
	try {
		$this->addListener('docs', 'group.add', function($id, $name) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			$data = array(
				'Doc' => array(
					'table_type' => 'group',
					'table_id' => $id,
					'title' => $name,
					'status' => 'in',
					'type' => 'folder',
				),
			);
			$doc->create();
			if(!$doc->save($data))
			{
				throw new RuntimeException('Unable to create document root');
			}
		});

		$this->addListener('docs', 'group.edit', function($id, $oldName, $newName) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			if($oldName == $newName)
			{
				return true;
			}

			$root = $doc->field('id', array(
				'Doc.table_type' => 'group',
				'Doc.table_id' => $id,
				'Doc.type' => 'folder',
				'Doc.parent_id' => null,
			));

			if(empty($root))
			{
				throw new RuntimeException('Unable to find document root');
				return false;
			}

			$doc->id = $root;
			if(!$doc->saveField('title', $newName))
			{
				throw new RuntimeException('Unable to save doc root');
			}
		});

		$this->addListener('docs', 'group.delete', function($id, $name) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			if(!$doc->deleteAll(array(
				'Doc.table_type' => 'group',
				'Doc.table_id' => $id,
			), true))
			{
				throw new RuntimeException('Unable to delete documents');
			}
		});

		$this->addListener('docs', 'project.add', function($id, $name) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			$data = array(
				'Doc' => array(
					'table_type' => 'project',
					'table_id' => $id,
					'title' => $name,
					'status' => 'in',
					'type' => 'folder',
				),
			);
			$doc->create();
			if(!$doc->save($data))
			{
				throw new RuntimeException('Unable to create document root');
			}
		});

		$this->addListener('docs', 'project.edit', function($id, $oldName, $newName) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			if($oldName == $newName)
			{
				return true;
			}

			$root = $doc->field('id', array(
				'Doc.table_type' => 'project',
				'Doc.table_id' => $id,
				'Doc.type' => 'folder',
				'Doc.parent_id' => null,
			));

			if(empty($root))
			{
				throw new RuntimeException('Unable to find document root');
				return false;
			}

			$doc->id = $root;
			if(!$doc->saveField('title', $newName))
			{
				throw new RuntimeException('Unable to save doc root');
			}
		});

		$this->addListener('docs', 'project.delete', function($id, $name) {
			App::import('Model', 'Docs.Doc');
			$doc = new Doc();

			if(!$doc->deleteAll(array(
				'Doc.table_type' => 'project',
				'Doc.table_id' => $id,
			)))
			{
				throw new RuntimeException('Unable to delete documents');
			}
		});
	} catch(Exception $e) {
		// TODO: Do something
	}
?>
