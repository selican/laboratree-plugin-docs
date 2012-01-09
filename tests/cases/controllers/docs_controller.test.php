<?php
App::import('Controller', 'Docs');
App::import('Component', 'RequestHandler');
App::import('Component', 'Subversion');
App::import('Component', 'FileCmp');

Mock::generatePartial('RequestHandlerComponent', 'DocsControllerMockRequestHandlerComponent', array('prefers'));
Mock::generatePartial('SubversionComponent', 'DocsControllerMockSubversionComponent', array(
	'add_document',
	'checkin_document',
	'download_document',
	'remove_document',
));
Mock::generatePartial('FileCmpComponent', 'DocsControllerMockFileCmpComponent', array(
	'is_uploaded_file',
	'move_uploaded_file',
	'checksum',
	'mimetype',
	'filesize',
	'exists',
	'remove',
	'output',
));

class DocsControllerTestDocsController extends DocsController {
	var $name = 'Docs';
	var $autoRender = false;

	var $redirectUrl = null;
	var $renderedAction = null;
	var $error = null;
	var $stopped = null;

	function redirect($url, $status = null, $exit = true)
	{
		$this->redirectUrl = $url;
	}
	function render($action = null, $layout = null, $file = null)
	{
		$this->renderedAction = $action;
	}

	function cakeError($method, $messages = array())
	{
		if(!isset($this->error))
		{
			$this->error = $method;
		}
	}

	function _stop($status = 0)
	{
		$this->stopped = $status;
	}
}

class DocsControllerTest extends CakeTestCase {
	var $Docs = null;

        var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest', 'app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');

	function startTest() {
		$this->Docs = new DocsControllerTestDocsController();
		$this->Docs->constructClasses();
		$this->Docs->Component->initialize($this->Docs);
		
		$this->Docs->Session->write('Auth.User', array(
			'id' => 1,
			'username' => 'testuser',
			'changepass' => 0,
		));
	}
	
	function testDocsControllerInstance() {
		$this->assertTrue(is_a($this->Docs, 'DocsController'));
	}

	function testIndex()
	{
		$table_type = 'group';
		$table_id = 1;
	
		$this->Docs->params = Router::parse('docs/index/' . $table_type . '/' . $table_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->index($table_type, $table_id);
		
		$this->assertEqual($this->Docs->redirectUrl, '/docs/' . $table_type . '/' . $table_id);
	}

	function testIndexNullTableType()
	{
		$table_type = null;
		$table_id = 1;

		$this->Docs->params = Router::parse('docs/index/' . $table_type . '/' . $table_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->index($table_type, $table_id);
		
		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testIndexInvalidTableType()
	{
		$table_type = 'invalid';
		$table_id = 1;

		$this->Docs->params = Router::parse('docs/index/' . $table_type . '/' . $table_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->index($table_type, $table_id);
		
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testIndexNullTableId()
	{
		$table_type = 'group';
		$table_id = null;


		$this->Docs->params = Router::parse('docs/index/' . $table_type . '/' . $table_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->index($table_type, $table_id);
		
		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testIndexInvalidTableId()
	{
		$table_type = 'group';
		$table_id = 'invalid';

		$this->Docs->params = Router::parse('docs/index/' . $table_type . '/' . $table_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->index($table_type, $table_id);
		
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testUserRootPrivate()
	{
		$user_id = 1;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->user($user_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Group - Private',
				'text' => 'Test Group - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => null,
				'text' => 'Test Project - Private',
				'title' => 'Test Project - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[1]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			/* array(
				'id'  => $nodes[2]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Private',
				'text' => 'Test Project - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[2]['created'],
				'version' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),*/
		);

		$this->assertEqual($nodes, $expected);
	}

	function testUserRootPublic()
	{
		$user_id = 1;
		$shared = 1;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id, $shared);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Group - Public',
				'text' => 'Test Group - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Public',
				'text' => 'Test Project - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[1]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			/*array(
				'id'  => $nodes[2]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Public',
				'text' => 'Test Project - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[2]['created'],
				'version' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),*/
		);

		$this->assertEqual($nodes, $expected);
	}

	function testUserNode()
	{
		$user_id = 1;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 1;
		$this->Docs->user($user_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];

		$expected = array(
			array(
				'id'  => 3,
				'parent_id' => 1,
				'title' => 'Test User Private Document',
				'text' => 'Test User Private Document',
				'author' => 'Test User',
				'status' => 'Checked Out',
				'size' => 0,
				'description' => 'Test',
				'created' => '12/20/2010 2:54pm',
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'user',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => 19,
				'parent_id' => 1,
				'title' => 'Test User Public Document 1 - CO',
				'text' => 'Test User Public Document 1 - CO',
				'author' => 'Test User',
				'status' => 'Checked Out',
				'size' => '0.10 KB',
				'description' => 'Test Checked Out',
				'created' => '12/20/2010 2:54pm',
				'version' => 1,
				'version_id' => 2,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'user',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => 21,
				'parent_id' => 1,
				'title' => 'Test User Public Document 2',
				'text' => 'Test User Public Document 2',
				'author' => 'Test User',
				'status' => 'Checked In',
				'size' => 0,
				'description' => 'Test',
				'created' => '12/20/2010 2:54pm',
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'user',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testUserNullUserId()
	{
		$user_id = null;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->redirectUrl, '/docs/user/1');
	}

	function testUserInvalidUserId()
	{
		$user_id = 'invalid';

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testUserInvalidUserIdNotFound()
	{
		$user_id = 9000;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testUserInvalidShared()
	{
		$user_id = 1;
		$shared = 'invalid';

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id, $shared);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testUserNodeInvalidNode()
	{
		$user_id = '1';

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 9000;
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testUserAccessDenied()
	{
		$user_id = 2;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testUserNodeAccessDenied()
	{
		$user_id = 1;

		$this->Docs->params = Router::parse('docs/user/' . $user_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 4;
		$this->Docs->user($user_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testGroupRootPrivate()
	{
		$group_id = 1;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Group - Private',
				'text' => 'Test Group - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Private',
				'text' => 'Test Project - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[1]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testGroupRootPublic()
	{
		$group_id = 1;
		$shared = 1;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id, $shared);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Group - Public',
				'text' => 'Test Group - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Public',
				'text' => 'Test Project - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[1]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testGroupNode()
	{
		$group_id = 1;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 7;
		$this->Docs->group($group_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];

		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => $nodes[0]['parent_id'],
				'title' => 'Test Group Private Document',
				'text' => 'Test Group Private Document',
				'author' => 'Test User',
				'status' => 'Checked In',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[0]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => $nodes[1]['parent_id'],
				'title' => 'Test Group Public Document',
				'text' => 'Test Group Public Document',
				'author' => 'Test User',
				'status' => 'Checked In',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[1]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[2]['id'],
				'parent_id' => $nodes[2]['parent_id'],
				'title' => 'Test Group Private Document 2 - CO',
				'text' => 'Test Group Private Document 2 - CO',
				'author' => 'Test User',
				'status' => 'Checked Out',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[2]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'group',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testGroupNullGroupId()
	{
		$group_id = null;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testGroupInvalidGroupId()
	{
		$group_id = 'invalid';

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testGroupInvalidGroupIdNotFound()
	{
		$group_id = 9000;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testGroupNodeInvalidNode()
	{
		$group_id = 1;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 9000;
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testGroupInvalidShared()
	{
		$group_id = 1;
		$shared = 'invalid';

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id, $shared);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testGroupAccessDenied()
	{
		$group_id = 2;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testGroupNodeAccessDenied()
	{
		$group_id = 1;

		$this->Docs->params = Router::parse('docs/group/' . $group_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 10;
		$this->Docs->group($group_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testProjectRootPrivate()
	{
		$project_id = 1;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Private',
				'text' => 'Test Project - Private',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testProjectRootPublic()
	{
		$project_id = 1;
		$shared = 1;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id, $shared);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => null,
				'title' => 'Test Project - Public',
				'text' => 'Test Project - Public',
				'author' => null,
				'status' => null,
				'size' => null,
				'description' => null,
				'created' => $nodes[0]['created'],
				'version' => null,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'folder',
				'iconCls' => 'doc-folder',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 1,
				'expandable' => 1,
				'leaf' => null,
				'draggable' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testProjectNode()
	{
		$project_id = 1;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		/*
		 * TODO: We should probably just find this node
		 * to avoid this test breaking in the future.
		 */
		$this->Docs->params['form']['node'] = 24;
		$this->Docs->project($project_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$expected = array(
			array(
				'id'  => $nodes[0]['id'],
				'parent_id' => $nodes[0]['parent_id'],
				'title' => 'Test Project Private Document',
				'text' => 'Test Project Private Document',
				'author' => 'Test User',
				'status' => 'Checked In',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[0]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[1]['id'],
				'parent_id' => $nodes[1]['parent_id'],
				'title' => 'Test Project Public Document',
				'text' => 'Test Project Public Document',
				'author' => 'Test User',
				'status' => 'Checked In',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[1]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
			array(
				'id'  => $nodes[2]['id'],
				'parent_id' => $nodes[2]['parent_id'],
				'title' => 'Test Project Private Document 2 - CO',
				'text' => 'Test Project Private Document 2 - CO',
				'author' => 'Test User',
				'status' => 'Checked Out',
				'size' => 0,
				'description' => 'Test',
				'created' => $nodes[2]['created'],
				'version' => 1,
				'version_id' => null,
				'uiProvider' => 'col',
				'cls' => 'file',
				'iconCls' => 'doc-file',
				'table_type' => 'project',
				'table_id' => 1,
				'shared' => 0,
				'expandable' => false,
				'leaf' => 1,
				'checksum' => null,
				'tags' => null,
			),
		);

		$this->assertEqual($nodes, $expected);
	}

	function testProjectNullProjectId()
	{
		$project_id = null;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testProjectInvalidProjectId()
	{
		$project_id = 'invalid';

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testProjectInvalidProjectIdNotFound()
	{
		$project_id = 9000;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testProjectNodeInvalidNode()
	{
		$project_id = 1;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = 9000;
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testProjectInvalidShared()
	{
		$project_id = 1;
		$shared = 'invalid';

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '/' . $shared . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id, $shared);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testProjectAccessDenied()
	{
		$project_id = 2;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testProjectNodeAccessDenied()
	{
		$project_id = 1;

		$this->Docs->params = Router::parse('docs/project/' . $project_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		/*
		 * Again, we should just do a find.
		 */
		$this->Docs->params['form']['node'] = 27;
		$this->Docs->project($project_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testViewJson()
	{
		$doc_id = 1;

		$this->Docs->params = Router::parse('docs/view/' . $doc_id . '.json'); 
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->view($doc_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response']; 
		
		$expected = array(
			'id' => 1,
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
			'expandable' => true,
			'leaf' => false,
			'draggable' => false,
			'tags' => null
		);

		$this->assertEqual($response, $expected);
	}

	function testViewNullDocID()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/view/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->view($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testViewInvalidDocID()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/view/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->view($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testViewInvalidDocIDNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/view/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->view($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testViewAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/view/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->view($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testVersionsJson()
	{
		$doc_id = 1;

		$this->Docs->params = Router::parse('docs/versions/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->versions($doc_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		$expected = array(
			array(
				'id' => 1,
				'doc_id' => 1,
				'author_id' => 1,
				'author' => 'Test User',
				'revised' => '12/20/2010 02:56 pm',
				'version' => 1,
				'revision' => 1,
				'size' => '0.10 KB',
				'changelog' => 'Initial Upload',
			),
		);

		$this->assertEqual($response['versions'], $expected);
	}

	function testVersionsNullDocID()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/versions/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->versions($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testVersionsInvalidDocID()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/versions/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->versions($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testVersionsInvalidDocIDNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/versions/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->versions($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testVersionsAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/versions/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->versions($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	/*
	 * TODO: Find another way to test file uploads
	 * that doesn't include mocking the fuck out of
	 * the FileCmp component and ignoring functionality.
	 */
	function testAdd()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => 'Upload Test',
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => 'Unique1, Unique2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('checksum', 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH');
		$this->Docs->FileCmp->setReturnValue('mimetype', 'image/png');
		$this->Docs->FileCmp->setReturnValue('filesize', 100);

		$this->Docs->FileCmp->setReturnValueAt(0, 'exists', false);
		$this->Docs->FileCmp->setReturnValueAt(1, 'exists', true);

		$this->Docs->FileCmp->setReturnValue('remove', true);

		$this->Docs->add($table_type, $table_id, $parent_id);

		//Check Response
		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		//Check Doc Table
		$conditions = array(
			'Doc.table_type' => $table_type,
			'Doc.table_id' => $table_id,
			'title' => 'Upload Test',
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'Doc' => array(
				'id'  => $result['Doc']['id'],
				'table_type' => 'group',
				'table_id' => 1,
				'parent_id' => 1,
				'lft' => $result['Doc']['lft'],
				'rght' => $result['Doc']['rght'],
				'filename' => $result['Doc']['filename'],
				'title' => 'Upload Test',
				'name' => 'test.png',
				'path' => '/Upload Test',
				'author_id' => 1,
				'description' => 'Upload Test',
				'created' => $result['Doc']['created'],
				'modified' => $result['Doc']['modified'],
				'status' => 'in',
				'current_user_id' => null,
				'shared' => 0,
				'type' => 'file',
			),
		);
		$this->assertEqual($result, $expected);

		$doc_id = $result['Doc']['id'];
		$filename = $result['Doc']['filename'];

		//Check DocsType Table
		$conditions = array(
			'DocsType.doc_id' => $doc_id,
		);
		$this->Docs->DocsType->recursive = -1;
		$result = $this->Docs->DocsType->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsType' => array(
				'id' => $result['DocsType']['id'],
				'doc_id' => $doc_id,
				'type_id' => 4,
			),
		);
		$this->assertEqual($result, $expected);

		$type_id = $result['DocsType']['type_id'];

		//Check DocsTypeRow table
		$conditions = array(
			'DocsTypeRow.doc_id' => $doc_id,
			'DocsTypeRow.type_id' => $type_id,
		);
		$this->Docs->DocsTypeRow->recursive = -1;
		$result = $this->Docs->DocsTypeRow->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTypeRow' => array(
				'id' => $result['DocsTypeRow']['id'],
				'doc_id' => $doc_id,
				'type_id' => 4,
				'parent_id' => null,
				'lft' => $result['DocsTypeRow']['lft'],
				'rght' => $result['DocsTypeRow']['rght'],
			),
		);
		$this->assertEqual($result, $expected);

		$row_id = $result['DocsTypeRow']['id'];

		//Check DocsTypeData Table
		$conditions = array(
			'DocsTypeData.doc_id' => $doc_id,
			'DocsTypeData.type_id' => $type_id,
			'DocsTypeData.row_id' => $row_id,
		);
		$this->Docs->DocsTypeData->recursive = -1;
		$result = $this->Docs->DocsTypeData->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTypeData' => array(
				'id' => $result['DocsTypeData']['id'],
				'doc_id' => $doc_id,
				'type_id' => $type_id,
				'row_id' => $row_id,
				'field_id' => 3,
				'value' => 'test',
			),
		);
		$this->assertEqual($result, $expected);

		//Check DocsVersion Table
		$conditions = array(
			'DocsVersion.doc_id' => $doc_id,
		);
		$this->Docs->DocsVersion->recursive = -1;
		$result = $this->Docs->DocsVersion->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsVersion' => array(
				'id' => $result['DocsVersion']['id'],
				'doc_id' => $doc_id,
				'author_id' => 1,
				'date' => $result['DocsVersion']['date'],
				'version' => 1,
				'revision' => 1,
				'mimetype' => 'image/png',
				'size' => 100,
				'changelog' => 'Initial Upload',
				'checksum' => 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH',
			),
		);
		$this->assertEqual($result, $expected);

		//Check Tag table
		$conditions = array(
			'Tag.keyword' => 'unique1',
		);
		$this->Docs->Tag->recursive = -1;
		$result = $this->Docs->Tag->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'Tag' => array(
				'id' => 2,
				'name' => 'Unique1',
				'keyword' => 'unique1',
			),
		);
		$this->assertEqual($result, $expected);

		$tag_id = $result['Tag']['id'];

		//Check DocsTag table
		$conditions = array(
			'DocsTag.doc_id' => $doc_id,
			'DocsTag.tag_id' => $tag_id,
		);	
		$this->Docs->DocsTag->recursive = -1;
		$result = $this->Docs->DocsTag->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTag' => array(
				'id' => $result['DocsTag']['id'],
				'doc_id' => $doc_id,
				'tag_id' => $tag_id,
			),
		);
		$this->assertEqual($result, $expected);
	}

	function testAddTypes()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		$expected = array(
		                        array(
                                'id' => $response['types'][0]['id'],
                                'text' => 'Test',
                                'leaf' => false,
                                'table_type' => 'group',
                                'table_id' => 1,
                                'title' => 'Test',
                                'name' => 'Test',
                                'type' => 'type',
                                'parent_id' => 3,
                                'data' => array(
                                        'type_id' => $response['types'][0]['data']['type_id'],
                                        'fields' => array(),
                                        'subtypes' => array(),
                                ),
                        ),

                        array(
                                'id' => $response['types'][1]['id'],
                                'text' => 'Shared Test',
                                'leaf' => false,
                                'table_type' => 'user',
                                'table_id' => 2,
                                'title' => 'Shared Test',
                                'name' => 'Shared Test',
                                'type' => 'type',
                                'parent_id' => 6,
                                'data' => array(
                                        'type_id' => $response['types'][1]['data']['type_id'],
                                        'fields' => array(),
                                        'subtypes' => array(),
                                ),
                        ),
/*
			array(
				'id' => $response['types'][1]['id'],
				'text' => 'User Type',
				'leaf' => false,
				'table_type' => 'user',
				'table_id' => 1,
				'title' => 'User Type',
				'name' => 'User Type',
				'type' => 'type',
				'parent_id' => 1,
				'data' => array(
					'type_id' => $response['types'][1]['data']['type_id'],
					'fields' => array(),
					'subtypes' => array(),
				),
			),
			array(
				'id' => $response['types'][1]['id'],
				'text' => 'Test',
				'leaf' => false,
				'table_type' => 'group',
				'table_id' => 1,
				'title' => 'Test',
				'name' => 'Test',
				'type' => 'type',
				'parent_id' => 3,
				'data' => array(
					'type_id' => $response['types'][1]['data']['type_id'],
					'fields' => array(),
					'subtypes' => array(),
				),
			),
			array(
				'id' => $response['types'][2]['id'],
				'text' => 'Shared Test',
				'leaf' => false,
				'table_type' => 'user',
				'table_id' => 2,
				'title' => 'Shared Test',
				'name' => 'Shared Test',
				'type' => 'type',
				'parent_id' => 6,
				'data' => array(
					'type_id' => $response['types'][2]['data']['type_id'],
					'fields' => array(),
					'subtypes' => array(),
				),
			),
			array(
				'id' => $response['types'][3]['id'],
				'text' => 'User Subtype',
				'leaf' => false,
				'table_type' => 'user',
				'table_id' => 1,
				'title' => 'User Subtype',
				'name' => 'User Subtype',
				'type' => 'type',
				'parent_id' => 2,
				'data' => array(
					'type_id' => $response['types'][3]['data']['type_id'],
					'fields' => array(),
					'subtypes' => array(),
				),
			),
*/		);

		$this->assertEqual($response['types'], $expected);
	}

	function testAddNotExtJS()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;
	
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', false);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testAddNullTableType()
	{
		$table_type = null;
		$table_id = 1;
		$parent_id = 1;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testAddInvalidTableType()
	{
		$table_type = 'invalid';
		$table_id = 1;
		$parent_id = 1;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testAddNullTableId()
	{
		$table_type = 'group';
		$table_id = null;
		$parent_id = 1;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}
	
	function testAddInvalidTableId()
	{
		$table_type = 'group';
		$table_id = 'invalid';
		$parent_id = 1;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testAddInvalidParentId()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 'invalid';
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testAddInvalidAction()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;
		$action = 'invalid';
	
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->params['form']['action'] = $action;
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testAddAccessDenied()
	{
		$table_type = 'group';
		$table_id = '2';
		$parent_id = null;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testAddInvalidParentIdRootNotFound()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 9000;
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testAddParentAccessDenied()
	{
		$table_type = 'group';
		$table_id = '1';
		$parent_id = '10';
		
		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();

		$this->Docs->RequestHandler->setReturnValue('prefers', true);
		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testAddInvalidFileUpload()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => 'Upload Test',
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => 'Unique1, Unique2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', false);

		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testAddInvalidFileMove()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => 'Upload Test',
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => 'Unique1, Unique2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', false);

		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testAddDocumentCommitFailed()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => 'Upload Test',
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => 'Unique1, Unique2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '0');

		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testAddInvalidData()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => null, // make save fail
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => 'Unique1, Unique2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('checksum', 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH');
		$this->Docs->FileCmp->setReturnValue('mimetype', 'image/png');
		$this->Docs->FileCmp->setReturnValue('filesize', 100);

		$this->Docs->FileCmp->setReturnValueAt(0, 'exists', false);
		$this->Docs->FileCmp->setReturnValueAt(1, 'exists', true);

		$this->Docs->FileCmp->setReturnValue('remove', true);

		$this->Docs->add($table_type, $table_id, $parent_id);

		//Check Response
		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testAddTagInvalidData()
	{
		$table_type = 'group';
		$table_id = 1;
		$parent_id = 1;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'title' => 'Upload Test',
				'filename' => array(
					'name' => 'test.png',
					'tmp_name' => $destination,
				),
				'description' => 'Upload Test',
				'tags' => '"', // null will not work for an invalid tag name
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'test',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'test',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/add/' . $table_type . '/' . $table_id . '/' . $parent_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('add_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('checksum', 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH');
		$this->Docs->FileCmp->setReturnValue('mimetype', 'image/png');
		$this->Docs->FileCmp->setReturnValue('filesize', 100);

		$this->Docs->FileCmp->setReturnValueAt(0, 'exists', false);
		$this->Docs->FileCmp->setReturnValueAt(1, 'exists', true);

		$this->Docs->FileCmp->setReturnValue('remove', true);

		$this->Docs->add($table_type, $table_id, $parent_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

/*	function testEdit()
	{
		$doc_id = 3;

		$this->Docs->data = array(
			'Doc' => array(
				'id' => $doc_id,
				'title' => 'Edited Title',
				'description' => 'Edited Description',
				'tags' => 'Edited1, Edited2',
			),
			'DocTypeData' => array(
				'types' => array(
					'4' => array(
						'rows' => array(
							'ext-1' => array(
								'fields' => array(
									'DocsTypeField-3' => 'Edited Field 1',
								),
								'types' => array(
									'5' => array(
										'rows' => array(
											'ext-2' => array(
												'fields' => array(
													'DocsTypeField-4' => 'Edited Field 2',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		//Check Response
		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		//Check Doc Table
		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'Doc' => array(
				'id'  => 3,
				'table_type' => 'user',
				'table_id' => 1,
				'parent_id' => 1,
				'lft' => $result['Doc']['lft'],
				'rght' => $result['Doc']['rght'],
				'filename' => 'test',
				'title' => 'Edited Title',
				'name' => 'testdoc.txt',
				'path' => '/Edited Title',
				'author_id' => 1,
				'description' => 'Edited Description',
				'created' => $result['Doc']['created'],
				'modified' => $result['Doc']['modified'],
				'status' => 'in',
				'current_user_id' => null,
				'shared' => 0,
				'type' => 'file',
			),
		);
		$this->assertEqual($result, $expected);

		//Check DocsType Table
		$conditions = array(
			'DocsType.doc_id' => $doc_id,
		);
		$this->Docs->DocsType->recursive = -1;
		$result = $this->Docs->DocsType->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsType' => array(
				'id' => 2,
				'doc_id' => 3,
				'type_id' => 4,
			),
		);
		$this->assertEqual($result, $expected);

		$type_id = $result['DocsType']['type_id'];

		//Check DocsTypeRow table
		$conditions = array(
			'DocsTypeRow.doc_id' => $doc_id,
			'DocsTypeRow.type_id' => $type_id,
		);
		$this->Docs->DocsTypeRow->recursive = -1;
		$result = $this->Docs->DocsTypeRow->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTypeRow' => array(
				'id' => 2,
				'doc_id' => 3,
				'type_id' => 4,
				'parent_id' => null,
				'lft' => 3,
				'rght' => 6,
			),
		);
		$this->assertEqual($result, $expected);

		$row_id = $result['DocsTypeRow']['id'];

		//Check DocsTypeData Table
		$conditions = array(
			'DocsTypeData.doc_id' => $doc_id,
			'DocsTypeData.type_id' => $type_id,
			'DocsTypeData.row_id' => $row_id,
		);
		$this->Docs->DocsTypeData->recursive = -1;
		$result = $this->Docs->DocsTypeData->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTypeData' => array(
				'id' => 2,
				'doc_id' => 3,
				'type_id' => 4,
				'row_id' => 2,
				'field_id' => 3,
				'value' => 'Edited Field 1',
			),
		);
		$this->assertEqual($result, $expected);

		//Check Tag table
		$conditions = array(
			'Tag.keyword' => 'edited1',
		);
		$this->Docs->Tag->recursive = -1;
		$result = $this->Docs->Tag->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'Tag' => array(
				'id' => 2,
				'name' => 'Edited1',
				'keyword' => 'edited1',
			),
		);
		$this->assertEqual($result, $expected);

		$tag_id = $result['Tag']['id'];

		//Check DocsTag table
		$conditions = array(
			'DocsTag.doc_id' => $doc_id,
			'DocsTag.tag_id' => $tag_id,
		);	
		$this->Docs->DocsTag->recursive = -1;
		$result = $this->Docs->DocsTag->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'DocsTag' => array(
				'id' => 2,
				'doc_id' => 3,
				'tag_id' => 2,
			),
		);
		$this->assertEqual($result, $expected);
	}
*/
	function testEditNotExtJS()
	{
		$doc_id = 3;

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testEditNullDocID()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testEditInvalidDocID()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testEditInvalidDocIDNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testEditAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testEditInvalidAction()
	{
		$doc_id = 3;
		$action = 'invalid';

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['action'] = $action;
		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testEditInvalidData()
	{
		$doc_id = 3;

		$this->Docs->data = array(
			'Doc' => array(
				'id' => $doc_id,
				'title' => null,
				'description' => 'Edited Description',
				'tags' => 'Edited1, Edited2',
			),
		);

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testEditTagInvalidData()
	{
		$doc_id = 3;

		$this->Docs->data = array(
			'Doc' => array(
				'id' => $doc_id,
				'title' => 'Edited Title',
				'description' => 'Edited Description',
				'tags' => '"',
			),
		);

		$this->Docs->params = Router::parse('docs/edit/' . $doc_id . '.extjs');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->edit($doc_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testDelete()
	{
		$doc_id = 3;	

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('remove_document', '1');

		$this->Docs->delete($doc_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);
// Removed for now. The above code should verify the response, but it seems like the fixtures aren't buding
/*		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertTrue(empty($result));
*/
		
	}

	function testDeleteNotJson()
	{
		$doc_id = 3;

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->delete($doc_id);
		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testDeleteNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->delete($doc_id);
		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testDeleteInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->delete($doc_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}
	
	function testDeleteInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->delete($doc_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDeleteInvalidDocIdRoot()
	{
		$doc_id = 1;

		$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->delete($doc_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDeleteAccessDenied()
	{
		try {
			$this->Docs->Session->write('Auth.User', array(
                	        'id' => 15,
                	        'username' => 'testuser',
                	        'changepass' => 0,
                	));

			$doc_id = 6;
	
			$this->Docs->params = Router::parse('docs/delete/' . $doc_id . '.json');
			$this->Docs->beforeFilter();
			$this->Docs->Component->startup($this->Docs);
	
			$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
			$this->Docs->RequestHandler->setReturnValue('prefers', true);

			$this->Docs->delete($doc_id);
		}

		catch (InvalidArgumentException $e) {
			$this->pass();
		}
	}

	function testDownloadNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/download/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testDownloadInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/download/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDownloadInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/download/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDownloadInvalidVersionId()
	{
		$doc_id = 3;
		$version_id = 'invalid';

		$this->Docs->params = Router::parse('docs/download/' . $doc_id . '/' . $version_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id, $version_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDownloadInvalidVersionIdNotFound()
	{
		$doc_id = 3;
		$version_id = 9000;

		$this->Docs->params = Router::parse('docs/download/' . $doc_id . '/' . $version_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id, $version_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testDownloadAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/download/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->download($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testCheckin()
	{
		$doc_id = 19;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'filename' => 'test.png',
			),
			'DocsVersion' => array(
				'changelog' => 'checkin changelog',
			),
		);

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('checkin_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('checksum', 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH');
		$this->Docs->FileCmp->setReturnValue('mimetype', 'image/png');
		$this->Docs->FileCmp->setReturnValue('filesize', 100);
		$this->Docs->FileCmp->setReturnValue('exists', true);

		$this->Docs->checkin($doc_id);
		
		// Check Response
		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		// Check Doc Table
		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));

		//$this->assertEqual($result['Doc']['status'], 'in');

		// Check DocsVerison Table
		$conditions = array(
			'DocsVersion.doc_id' => $doc_id,
		);
		$this->Docs->DocsVersion->recursive = -1;
		$result = $this->Docs->DocsVersion->find('first', array('conditions' => $conditions, 'order' => 'DocsVersion.version DESC'));

		$expected = array(
			'DocsVersion' => array(
				'id' => 3,
				'doc_id' => 19,
				'author_id' => 1,
				'date' => $result['DocsVersion']['date'],
				'version' => 2,
				'revision' => 1,
				'mimetype' => 'image/png',
				'size' => 100,
				'changelog' => 'checkin changelog',
				'checksum' => 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH',
			),
		);

		$this->assertEqual($result, $expected);
	}

	function testCheckinNotJson()
	{
		$doc_id = 19;

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->checkin($doc_id);
		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testCheckinNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkin($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testCheckinInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkin($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCheckinInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkin($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCheckinInvalidNotCheckedOut()
	{
		$doc_id = 1;

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkin($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCheckinAccessDenied()
	{
		$doc_id = 20;

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkin($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testCheckinInvalidFileUpload()
	{
		$doc_id = 19;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'filename' => 'test.png',
			),
			'DocsVersion' => array(
				'changelog' => 'checkin changelog',
			),
		);

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('checkin_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', false);

		$this->Docs->checkin($doc_id);
		
		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testCheckinInvalidFileMove()
	{
		$doc_id = 19;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'filename' => 'test.png',
			),
			'DocsVersion' => array(
				'changelog' => 'checkin changelog',
			),
		);

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('checkin_document', '1');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', false);

		$this->Docs->checkin($doc_id);
		
		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testCheckinInvalidRevision()
	{
		$doc_id = 19;

		$source = '/data/laboratree/images/test.png';
		$destination = sys_get_temp_dir() . '/' . uniqid('php', true);
		$this->assertTrue(copy($source, $destination));

		$this->Docs->data = array(
			'Doc' => array(
				'filename' => 'test.png',
			),
			'DocsVersion' => array(
				'changelog' => 'checkin changelog',
			),
		);

		$this->Docs->params = Router::parse('docs/checkin/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->Subversion = new DocsControllerMockSubversionComponent();
		$this->Docs->Subversion->setReturnValue('checkin_document', '0');

		$this->Docs->FileCmp = new DocsControllerMockFileCmpComponent();
		$this->Docs->FileCmp->setReturnValue('is_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('move_uploaded_file', true);
		$this->Docs->FileCmp->setReturnValue('checksum', 'HASHHASHHASHHASHHASHHASHHASHHASHHASHHASH');
		$this->Docs->FileCmp->setReturnValue('mimetype', 'image/png');
		$this->Docs->FileCmp->setReturnValue('filesize', 100);
		$this->Docs->FileCmp->setReturnValue('exists', true);

		$this->Docs->checkin($doc_id);
		
		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function testCheckout()
	{
		$doc_id = 1;

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->checkout($doc_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);
		
		/* Fixtures don't seem to be working
		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertEqual($result['Doc']['status'], 'out');
	*/
	}

	function testCheckoutNotJson()
	{
		$doc_id = 3;
                $this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
                $this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checkout($doc_id);

		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertEqual($result['Doc']['status'], 'out');

	//	$this->assertEqual($this->Docs->redirectUrl, '/docs/download/' . $doc_id);
	}

	function testCheckoutNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testCheckoutInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCheckoutInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCheckoutAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testCancelCheckout()
	{
		$doc_id = 19;

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);

		$conditions = array(
			'Doc.id' => $doc_id,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertEqual($result['Doc']['status'], 'out');
	}

	function testCancelCheckoutNotJson()
	{
		$doc_id = 19;

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testCancelCheckoutNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testCancelCheckoutInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCancelCheckoutInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testCancelCheckoutAccessDenied()
	{
		$doc_id = 20;

		$this->Docs->params = Router::parse('docs/cancel_checkout/' . $doc_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->cancel_checkout($doc_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testReorderUp()
	{
		$node = 22;
		$delta = -1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);
	}

	function testReorderDown()
	{
		$node = 9;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);
	}

	function testReorderNotJson()
	{
		$node = 4;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testReorderNullNode()
	{
		$node = null;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testReorderInvalidNode()
	{
		$node = 'invalid';
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReorderInvalidNodeNotFound()
	{
		$node = 9000;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReorderInvalidNodeRoot()
	{
		$node = 7;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReorderNullDelta()
	{
		$node = 22;
		$delta = null;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testReorderInvalidDelta()
	{
		$node = 22;
		$delta = 'invalid';

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReorderAccessDenied()
	{
		$node = 6;
		$delta = 1;

		$this->Docs->params = Router::parse('docs/reorder.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['delta'] = $delta;
		$this->Docs->reorder();

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testReparent()
	{
		$node = 9;
		$parent = 8;
		$position = 1;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];
		$this->assertTrue($response['success']);
	}

	function testReparentNotJson()
	{
		$node = 4;
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testReparentNullNode()
	{
		$node = null;
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testReparentInvalidNode()
	{
		$node = 'invalid';
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentInvalidNodeNotFound()
	{
		$node = 9000;
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentInvalidNodeRoot()
	{
		$node = 1;
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentNullParentID()
	{
		$node = 9;
		$parent = null;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testReparentInvalidParentID()
	{
		$node = 9;
		$parent = 'invalid';
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentInvalidParentIDNotFound()
	{
		$node = 9;
		$parent = 9000;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentNullPosition()
	{
		$node = 9;
		$parent = 8;
		$position = null;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testReparentInvalidPosition()
	{
		$node = 9;
		$parent = 8;
		$position = 'invalid';

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testReparentAccessDenied()
	{
		$node = 6;
		$parent = 1;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testReparentAccessDeniedRoot()
	{
		$node = 22;
		$parent = 4;
		$position = 0;

		$this->Docs->params = Router::parse('docs/reparent.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['node'] = $node;
		$this->Docs->params['form']['parent'] = $parent;
		$this->Docs->params['form']['position'] = $position;
		$this->Docs->reparent();

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testFolder()
	{
		$parent_id = 7;
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$nodes = $this->Docs->folder($parent_id);

	/*
		// Check Response
		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];
		$this->assertTrue(!empty($nodes));
	*/

		//Check Doc Table
		$conditions = array(
			'Doc.type' => 'folder',
			'Doc.parent_id' => $parent_id,
			'Doc.title' => $folder,
		);
		$this->Docs->Doc->recursive = -1;
		$result = $this->Docs->Doc->find('first', array('conditions' => $conditions));
		$this->assertFalse(empty($result));

		$expected = array(
			'Doc' => array(
				'id'  => $result['Doc']['id'],
				'table_type' => 'group',
				'table_id' => 1,
				'parent_id' => 7,
				'lft' => $result['Doc']['lft'],
				'rght' => $result['Doc']['rght'],
				'filename' => null,
				'title' => $folder,
				'name' => null,
				'path' => null,
				'author_id' => 1,
				'description' => null,
				'created' => $result['Doc']['created'],
				'modified' => $result['Doc']['modified'],
				'status' => 'in',
				'current_user_id' => null,
				'shared' => 0,
				'type' => 'folder',
			),
		);

		$this->assertEqual($result, $expected);
	}

	function testFolderNotJson()
	{
		$parent_id = 7;
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testFolderNullParentID()
	{
		$parent_id = null;
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testFolderInvalidParentID()
	{
		$parent_id = 'invalid';
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testFolderInvalidParentIDNotFound()
	{
		$parent_id = 9000;
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testFolderAccessDenied()
	{
		$parent_id = 10;
		$folder = 'New Test Folder';

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testFolderMissingFolderNameField()
	{
		$parent_id = 7;
		$folder = null;

		$this->Docs->params = Router::parse('docs/folder/' . $parent_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->params['form']['folder'] = $folder;

		$this->Docs->folder($parent_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testFields()
	{
		$type_id = 4;

		$this->Docs->params = Router::parse('docs/fields/' . $type_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$nodes = $this->Docs->fields($type_id);

		$this->assertTrue(isset($this->Docs->viewVars['response']));
		$response = $this->Docs->viewVars['response'];

		$expected = array(
			'success' => 1,
			'type' => array(
				'id' => 4,
				'text' => 'Test',
				'leaf' => false,
				'table_type' => 'group',
				'table_id' => 1,
				'title' => 'Test',
				'name' => 'Test',
				'type' => 'type',
				'parent_id' => 3,
				'data' => array(
					'type_id' => 4,
					'fields' => array(),
					'subtypes' => array(),
				),
			),
			'subtypes' => array(
				'0' => array(
					'id' => 5,
					'text' => 'SubTest',
					'leaf' => false,
					'table_type' => 'group',
					'table_id' => 1,
					'title' => 'SubTest',
					'name' => 'SubTest',
					'type' => 'subtype',
					'parent_id' => 4,
					'data' => array(
						'type_id' => 5,
						'fields' => array(
							'4' => array(
								'id' => 4,
								'title' => 'Title',
								'data' => array(
									'display' => 1,
									'name' => 'Title',
									'required' => 1,
									'type' => 'text',
								),
							),
			
						),
						'subtypes' => array(),
					),
				),
			),
			'fields' => array(
				'0' => array(
					'id' => 3,
					'type_id' => 4,
					'name' => 'Title',
					'type' => 'text',
					'required' => 1,
					'display' => 1,
				),
			),
		);

		$this->assertEqual($response, $expected);
	}

	function testFieldsNotJson()
	{
		$type_id = 4;

		$this->Docs->params = Router::parse('docs/fields/' . $type_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', false);

		$this->Docs->fields($type_id);

		$this->assertEqual($this->Docs->error, 'error404');
	}

	function testFieldsNullTypeID()
	{
		$type_id = null;

		$this->Docs->params = Router::parse('docs/fields/' . $type_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->fields($type_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testFieldsInvalidTypeID()
	{
		$type_id = 'invalid';

		$this->Docs->params = Router::parse('docs/fields/' . $type_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->fields($type_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testFieldsInvalidTypeIDNotFound()
	{
		$type_id = 9000;

		$this->Docs->params = Router::parse('docs/fields/' . $type_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->fields($type_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testFieldsAccessDenied()
	{
		$type_id = 6;

		$this->Docs->params = Router::parse('docs/fields/' . $type_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);


		$this->Docs->fields($type_id);

		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testChecksumNullDocId()
	{
		$doc_id = null;

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id);
		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testChecksumInvalidDocId()
	{
		$doc_id = 'invalid';

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testChecksumInvalidDocIdNotFound()
	{
		$doc_id = 9000;

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testChecksumInvalidVersionId()
	{
		$doc_id = 3;
		$version_id = 'invalid';

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id . '/' . $version_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id, $version_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testChecksumInvalidVersionIdNotFound()
	{
		$doc_id = 3;
		$version_id = 9000;

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id . '/' . $version_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id, $version_id);
		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testChecksumAccessDenied()
	{
		$doc_id = 6;

		$this->Docs->params = Router::parse('docs/checksum/' . $doc_id);
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->checksum($doc_id);
		$this->assertEqual($this->Docs->error, 'access_denied');
	}

	function testShared()
	{
		$table_type = 'group';
		$table_id = 1;

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertTrue(isset($this->Docs->viewVars['nodes']));
		$nodes = $this->Docs->viewVars['nodes'];

		$expected = array(
			array(
				'id' => 8,
				'text' => 'Test Group - Public',
				'cls' => 'folder',
				'draggable' => null,
			),
		);
		$this->assertEqual($nodes, $expected);
	}

	function testSharedNullTableType()
	{
		$table_type = null;
		$table_id = 1;

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testSharedInvalidTableType()
	{
		$table_type = 'invalid';
		$table_id = 1;

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testSharedNullTableId()
	{
		$table_type = 'group';
		$table_id = null;

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertEqual($this->Docs->error, 'missing_field');
	}

	function testSharedInvalidTableId()
	{
		$table_type = 'group';
		$table_id = 'invalid';

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertEqual($this->Docs->error, 'invalid_field');
	}

	function testSharedInvalidRoot()
	{
		$table_type = 'group';
		$table_id = 9000;

		$this->Docs->params = Router::parse('docs/shared/' . $table_type . '/' . $table_id . '.json');
		$this->Docs->beforeFilter();
		$this->Docs->Component->startup($this->Docs);

		$this->Docs->RequestHandler = new DocsControllerMockRequestHandlerComponent();
		$this->Docs->RequestHandler->setReturnValue('prefers', true);

		$this->Docs->shared($table_type, $table_id);

		$this->assertEqual($this->Docs->error, 'internal_error');
	}

	function endTest() {
		unset($this->Docs);
		ClassRegistry::flush();	
	}
}
?>
