<?php 
/* SVN FILE: $Id$ */
/* Doc Test cases generated on: 2010-12-20 14:54:37 : 1292856877*/
App::import('Model', 'Doc');

class DocTestCase extends CakeTestCase {
	var $Doc = null;
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url');

	function startTest() {
		$this->Doc =& ClassRegistry::init('Doc');
	}

	function testDocInstance() {
		$this->assertTrue(is_a($this->Doc, 'Doc'));
	}

	function testDocFind() {
		$this->Doc->recursive = -1;
		$results = $this->Doc->find('first');
		$this->assertTrue(!empty($results));

		$expected = array(
			'Doc' => array(
				'id'  => 1,
				'table_type' => 'user',
				'table_id'  => 1,
				'parent_id'  => null,
				'lft'  => $results['Doc']['lft'],
				'rght'  => $results['Doc']['rght'],
				'filename'  => null,
				'title'  => 'Test User - Private',
				'name'  => null,
				'path'  => '/Test User - Private',
				'author_id'  => 1,
				'description'  => null,
				'created'  => $results['Doc']['created'],
				'modified'  => $results['Doc']['modified'],
				'status' => 'in',
				'current_user_id'  => null,
				'shared'  => 0,
				'type' => 'folder'
			),
		);
		$this->assertEqual($results, $expected);
	}

	function testRootPrivate()
	{
		$table_type = 'user';
		$table_id = 1;
		$shared = 0;

		$results = $this->Doc->root($table_type, $table_id, $shared);

		$expected = array(
			'Doc' => array(
				'id' => 1,
				'table_type' => 'user',
				'table_id' => 1,
				'parent_id' => null,
				'lft' => $results['Doc']['lft'],
				'rght' => $results['Doc']['rght'],
				'filename' => null,
				'title' => 'Test User - Private',
				'name' => null,
				'path' => '/Test User - Private',
				'author_id' => 1,
				'description' => null,
				'created' => $results['Doc']['created'],
				'modified' => $results['Doc']['modified'],
				'status' => 'in',
				'current_user_id' => null,
				'shared' => 0,
				'type' => 'folder',
			),
		);

		$this->assertEqual($results, $expected);
	}

	function testRootPublic()
	{
		$table_type = 'user';
		$table_id = 1;
		$shared = 1;

		$root = $this->Doc->root($table_type, $table_id, $shared);
		$expected = array(
			'Doc' => array(
				'id' => 2,
				'table_type' => 'user',
				'table_id' => 1,
				'parent_id' => null,
				'lft' => $root['Doc']['lft'],
				'rght' => $root['Doc']['rght'],
				'filename' => null,
				'title' => 'Test User - Public',
				'name' => null,
				'path' => '/Test User - Public',
				'author_id' => 1,
				'description' => null,
				'created' => $root['Doc']['created'],
				'modified' => $root['Doc']['modified'],
				'status' => 'in',
				'current_user_id' => null,
				'shared' => 1,
				'type' => 'folder',
			),
		);

		$this->assertEqual($root, $expected);
	}

	function testRootNullTableType()
	{
		$table_type = null;
		$table_id = 1;
		$shared = 0;

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRootInvalidTableType()
	{
		$table_type = 'invalid';
		$table_id = 1;
		$shared = 0;

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRootNullTableId()
	{
		$table_type = 'user';
		$table_id = null;
		$shared = 0;

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRootInvalidTableId()
	{
		$table_type = 'user';
		$table_id = 'invalid';
		$shared = 0;

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRootNullShared()
	{
		$table_type = 'user';
		$table_id = 1;
		$shared = null;

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRootInvalidShared()
	{
		$table_type = 'user';
		$table_id = 1;
		$shared = 'invalid';

		try
		{
			$node = $this->Doc->root($table_type, $table_id, $shared);
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testToNode() {
		$this->Doc->recursive = -1;
		$results = $this->Doc->find('first');
		$node = $this->Doc->toNode($results);

		$expected = array(
			'id'  => 1,
			'parent_id' => null,
			'title' => 'Test User - Private',
			'text' => 'Test User - Private',
			'author' => null,
			'status' => null,
			'size' => null,
			'description' => null,
			'created' => '2010-12-20 14:54:37',
			'version' => null,
			'version_id' => null,
			'uiProvider' => 'col',
			'cls' => 'folder',
			'iconCls' => 'doc-folder',
			'table_type' => 'user',
			'table_id' => 1,
			'shared' => 0,
			'expandable' => 1,
			'leaf' => null,
			'draggable' => null,
		);

		$this->assertEqual($node, $expected);
	}

	function testToNodeNull() {
		try
		{
			$node = $this->Doc->toNode(null);
			$this->fail('InvalidArgumentException was expected');
		}
		catch (InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testToNodeNotArray() {
		try
		{
			$node = $this->Doc->toNode('string');
			$this->fail('InvalidArgumentException was expected');
		}
		catch (InvalidArgumentException $e)
		{
			$this->pass();
		}	
	}

	function testToNodeMissingModel() {
		try
		{
			$node = $this->Doc->toNode(array('id' => 1));
			$this->fail('InvalidArgumentException was expected');
		}
		catch (InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testToNodeMissingKey() {
		try
		{
			$node = $this->Doc->toNode(array('Doc' => array('test' => 1)));
			$this->fail('InvalidArgumentException was expected');
		}
		catch (InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFolders()
	{
		$table_type = 'user';
		$table_id = 1;

		$node = $this->Doc->folders($table_type, $table_id);
		$expected = array(
			array(
				'id' => 1,
				'title' => ' Test User - Private',
			),
			array(
				'id' => 2,
				'title' => ' Test User - Public',
			),
		);
		
		$this->assertEqual($node, $expected);
	}

	function testFoldersNullTableType()
	{
		$table_type = null;
		$table_id = 1;

		try
		{
			$node = $this->Doc->folders($table_type, $table_id);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFoldersInvalidTableType()
	{
		$table_type = 'invalid';
		$table_id = 1;

		try
		{
			$node = $this->Doc->folders($table_type, $table_id);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFoldersNullTableId()
	{
		$table_type = 'user';
		$table_id = null;

		try
		{
			$node = $this->Doc->folders($table_type, $table_id);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFoldersInvalidTableId()
	{
		$table_type = 'user';
		$table_id = 'invalid';

		try
		{
			$node = $this->Doc->folders($table_type, $table_id);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}
	
	function testCascade() {
		$this->Doc->cascade(null, null);
	}

}
?>
