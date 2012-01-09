<?php
class DocsController extends DocsAppController {
	var $name = 'Docs';

	var $uses = array(
		'Docs.Doc',
		'Docs.DocsVersion',
		'User',
		'Group',
		'Project',
		'GroupsUsers',
		'ProjectsUsers',
	);

	var $components = array(
		'Auth',
		'Security',
		'Session',
		'RequestHandler',
		'FileCmp',
		'Subversion',
		'Plugin',
	);

	function beforeFilter()
	{
		$this->Security->validatePost = false;

		parent::beforeFilter();
	}

	/**
	 * Redirects to action based on Table Type
	 *
	 * @param string $table_type Table Type
	 * @param string $table_id   Table ID
	 */
	function index($table_type = '', $table_id = '')
	{
		if(empty($table_type))
		{
			$this->cakeError('missing_field', array('field' => 'Table Type'));
			return;
		}

		if(empty($table_id))
		{
			$this->cakeError('missing_field', array('field' => 'Table ID'));
			return;
		}

		if(!is_numeric($table_id) || $table_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Table ID'));
			return;
		}

		if(method_exists($this, $table_type))
		{
			$this->redirect("/docs/$table_type/$table_id");
			return;
		}

		$this->cakeError('invalid_field', array('field' => 'Table Type'));
		return;
	}

	/**
	 * Group Documents
	 *
	 * @param integer $group_id Group ID
	 */
	function group($group_id = '')
	{
		if(empty($group_id))
		{
			$this->cakeError('missing_field', array('field' => 'Group ID'));
			return;
		}

		if(!is_numeric($group_id) || $group_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Group ID'));
			return;
		}

		$group = $this->Group->find('first', array(
			'conditions' => array(
				'Group.id' => $group_id,
			),
			'recursive' => -1,
		));
		if(empty($group))
		{
			$this->cakeError('invalid_field', array('field' => 'Group ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.view', 'group', $group_id);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Document Tree'));
			return;
		}

		$this->pageTitle = 'Documents - ' . $group['Group']['name'];
		$this->set('pageName', $group['Group']['name'] . ' - Documents');	

		$this->set('group_id', $group_id);
		$this->set('group', $group);

		$context = array(
			'table_type' => 'group',
			'table_id' => $group_id,

			'group_id' => $group_id,

			'permissions' => array(
				'document' => $permission['mask'],
			),
		);
		$this->set('context', $context);

		if($this->RequestHandler->prefers('json'))
		{
			$parent_id = null;
			if(isset($this->params['form']['node']))
			{
				$parent_id = $this->params['form']['node'];
			}

			$documents = array();

			if(!empty($parent_id) && is_numeric($parent_id) && $parent_id > 0)
			{
				$parent_id = $this->params['form']['node'];

				$parent = $this->Doc->find('first', array(
					'conditions' => array(
						'Doc.id' => $parent_id,
					),
					'recursive' => -1,
				));
				if(empty($parent))
				{
					$this->cakeError('invalid_field', array('field' => 'Node'));
					return;
				}

				if(!$this->PermissionCmp->check('document.view', $parent['Doc']['table_type'], $parent['Doc']['table_id']))
				{
					$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Documents'));
					return;
				}

				$documents = $this->Doc->find('all', array(
					'conditions' => array(
						'Doc.parent_id' => $parent_id,
					),
					'order' => 'Doc.lft',
					'recursive' => 1,
				));
			}	
			else
			{
				$docs = $this->Doc->find('all', array(
					'conditions' => array(
						'Doc.table_type' => 'group',
						'Doc.table_id' => $group_id,
						'Doc.parent_id' => null,
					),
					'order' => 'Doc.lft',
					'recursive' => 1,
				));

				$documents = array_merge($documents, $docs);

				$projects = $this->Project->find('all', array(
					'conditions' => array(
						'Project.group_id' => $group_id,
					),
					'recursive' => -1,
				));
				foreach($projects as $project)
				{
					$docs = $this->Doc->find('all', array(
						'conditions' => array(
							'Doc.table_type' => 'project',
							'Doc.table_id' => $project['Project']['id'],
							'Doc.parent_id' => null,
						),
						'order' => 'Doc.lft',
						'recursive' => 1,
					));
					$documents = array_merge($documents, $docs);
				}
			}

			try  {
				$nodes = $this->Doc->toNodes($documents);
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Convert', 'resource' => 'Documents'));
				return;
			}

			$this->set('nodes', $nodes);
		}
	}

	/**
	 * Project Documents
	 *
	 * @param integer $project_id Project ID
	 */
	function project($project_id = '')
	{
		if(empty($project_id))
		{
			$this->cakeError('missing_field', array('field' => 'Project ID'));
			return;
		}

		if(!is_numeric($project_id) || $project_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Project ID'));
			return;
		}

		$project = $this->Project->find('first', array(
			'conditions' => array(
				'Project.id' => $project_id,
			),
			'recursive' => -1,
		));
		if(empty($project))
		{
			$this->cakeError('invalid_field', array('field' => 'Project ID'));
			return;
		}

		$group = $this->Group->find('first', array(
			'conditions' => array(
				'Group.id' => $project['Project']['group_id'],
			),
			'recursive' => -1,
		));
		if(empty($group))
		{
			$this->cakeError('internal_error', array('action' => 'View', 'resource' => 'Project Documents'));
			return;
		}
		
		$permission = $this->PermissionCmp->check('document.view', 'project', $project_id);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Document Tree'));
			return;
		}

		$this->pageTitle = 'Documents - ' . $project['Project']['name'];
		$this->set('pageName', $project['Project']['name'] . ' - Documents');	

		$this->set('project_id', $project_id);
		$this->set('project', $project);

		$this->set('group_name', $group['Group']['name']);
		$this->set('group_id', $group['Group']['id']);

		$context = array(
			'table_type' => 'project',
			'table_id' => $project_id,

			'project_id' => $project_id,
			'group_id' => $group_id,

			'permissions' => array(
				'document' => $permission['mask'],
			),
		);
		$this->set('context', $context);

		if($this->RequestHandler->prefers('json'))
		{
			$parent_id = null;
			if(isset($this->params['form']['node']))
			{
				$parent_id = $this->params['form']['node'];
			}

			$documents = array();

			if(!empty($parent_id) && is_numeric($parent_id) && $parent_id > 0)
			{
				$parent_id = $this->params['form']['node'];

				$parent = $this->Doc->find('first', array(
					'conditions' => array(
						'Doc.id' => $parent_id,
					),
					'recursive' => -1,
				));
				if(empty($parent))
				{
					$this->cakeError('invalid_field', array('field' => 'Node'));
					return;
				}

				if(!$this->PermissionCmp->check('document.view', $parent['Doc']['table_type'], $parent['Doc']['table_id']))
				{
					$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Documents'));
					return;
				}

				$documents = $this->Doc->find('all', array(
					'conditions' => array(
						'Doc.parent_id' => $parent_id,
					),
					'order' => 'Doc.lft',
					'recursive' => 1,
				));
			}	
			else
			{
				$documents = $this->Doc->find('all', array(
					'conditions' => array(
						'Doc.table_type' => 'project',
						'Doc.table_id' => $project_id,
						'Doc.parent_id' => null,
					),
					'order' => 'Doc.lft',
					'recursive' => 1,
				));
			}

			try {
				$nodes = $this->Doc->toNodes($documents);
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Convert', 'resource' => 'Documents'));
				return;
			}

			$this->set('nodes', $nodes);
		}
	}

	/**
	 * View a Group or Project Document
	 *
	 * @param integer $doc_id Document ID
	 */
	function view($doc_id = '')
	{
		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'recursive' => 1,
		));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.view', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Document'));
			return;
		}

		if($this->RequestHandler->prefers('json'))
		{
			/* TODO: Simply this. We no longer supply types. */
			$action = 'doc';
			if(isset($this->params['form']['action']))
			{
				$action = $this->params['form']['action'];
			}

			switch($action)
			{
				case 'doc':
					try {
						$response = $this->Doc->toNode($doc);
					} catch(Exception $e) {
						$this->cakeError('internal_error', array('action' => 'Convert', 'resource' => 'Documents'));
						return;
					}
					break;
			}

			$this->set('response', $response);
		}
		else
		{
			$name = null;
			switch($doc['Doc']['table_type'])
			{
				case 'user':
					$this->User->id = $doc['Doc']['table_id'];
					$name = $this->User->field('name');
					break;
				case 'group':
					$this->Group->id = $doc['Doc']['table_id'];
					$name = $this->Group->field('name');
					$this->set('group_id', $doc['Doc']['table_id']);
					break;
				case 'project':
					$this->Project->id = $doc['Doc']['table_id'];
					$name = $this->Project->field('name');
					$group_id = $this->Project->field('group_id');

					$this->set('project_id', $doc['Doc']['table_id']);

					$group = $this->Group->find('first', array(
						'conditions' => array(
							'Group.id' => $group_id,
						),
						'recursive' => -1,
					));
					if(empty($group))
					{
						$this->cakeError('internal_error', array('action' => 'View', 'resource' => 'Document'));
						return;
					}

					$this->set('group_name', $group['Group']['name']);
					$this->set('group_id', $group['Group']['id']);
					break;
				default:
					$this->cakeError('invalid_field', array('field' => 'Table Type'));
					return;
			}

			$this->pageTitle = "View Documents - {$doc['Doc']['title']} - $name";
			$this->set('pageName', $name . ' - View Document - ' . $doc['Doc']['title']);

			$this->set('doc_id', $doc_id);
			$this->set('doc', $doc);
			$this->set('name', $name);

			$context = array(
				'table_type' => $doc['Doc']['table_type'],
				'table_id' => $doc['Doc']['table_id'],

				'doc_id' => $doc_id,

				'permissions' => array(
					'document' => $permission['mask'],
				),
			);
			$this->set('context', $context);
		}
	}

	/**
	 * List Docment Versions
	 *
	 * @param integer $doc_id Document ID
	 */
	function versions($doc_id = '')
	{
		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array('conditions' => array('Doc.id' => $doc_id), 'recursive' => 1));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.view', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'View', 'resource' => 'Document Versions'));
			return;
		}

		$name = null;
		switch($doc['Doc']['table_type'])
		{
			case 'user':
				$this->User->id = $doc['Doc']['table_id'];
				$name = $this->User->field('name');
				break;
			case 'group':
				$this->Group->id = $doc['Doc']['table_id'];
				$name = $this->Group->field('name');
				$this->set('group_id', $doc['Doc']['table_id']);
				break;
			case 'project':
				$this->Project->id = $doc['Doc']['table_id'];
				$name = $this->Project->field('name');
				$group_id = $this->Project->field('group_id');

				$this->set('project_id', $doc['Doc']['table_id']);

				$group = $this->Group->find('first', array(
					'conditions' => array(
						'Group.id' => $group_id,
					),
					'recursive' => -1,
				));
				if(empty($group))
				{
					$this->cakeError('internal_error', array('action' => 'View', 'resource' => 'Document Versions'));
					return;
				}

				$this->set('group_name', $group['Group']['name']);
				$this->set('group_id', $group['Group']['id']);
				break;
			default:
				$this->cakeError('invalid_field', array('field' => 'Table Type'));
				return;
		}

		$this->pageTitle = 'Documents Version - ' . $doc['Doc']['title'] . ' - ' . $name;
		$this->set('pageName', $name . ' - Document Version - ' . $doc['Doc']['title']);

		$this->set('doc_id', $doc_id);
		$this->set('doc', $doc);
		$this->set('name', $name);

		$context = array(
			'table_type' => $doc['Doc']['table_type'],
			'table_id' => $doc['Doc']['table_id'],

			'document_id' => $doc_id,

			'permissions' => array(
				'document' => $permission['mask'],
			),
		);
		$this->set('context', $context);

		if($this->RequestHandler->prefers('json'))
		{
			$versions = $this->DocsVersion->find('all', array(
				'conditions' => array(
					'DocsVersion.doc_id' => $doc_id,
				),
				'order' => 'DocsVersion.version DESC',
				'recursive' => 1,
			));

			try {
				$nodes = $this->DocsVersion->toNodes($versions);
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Convert', 'resource' => 'Document Versions'));
				return;
			}

			$response = array(
				'success' => true,
				'versions' => $nodes,
			);

			$this->set('response', $response);
		}
	}

	/**
	 * Adds a Document to a Group or Project
	 *
	 * @param string  $table_type Table Type
	 * @param integer $table_id   Table ID
	 * @param integer $parent_id  Folder ID
	 */
	function add($table_type = '', $table_id = '', $parent_id = '')
	{
		if(!$this->RequestHandler->prefers('extjs'))
		{
			$this->cakeError('error404');
			return;
		}
		
		if(empty($table_type))
		{
			$this->cakeError('missing_field', array('field' => 'Table Type'));
			return;
		}

		if(!is_string($table_type) || !in_array($table_type, array('user', 'group', 'project')))
		{
			$this->cakeError('invalid_field', array('field' => 'Table Type'));
			return;
		}

		if(empty($table_id))
		{
			$this->cakeError('missing_field', array('field' => 'Table ID'));
			return;
		}

		if(!is_numeric($table_id) || $table_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Table ID'));
			return;
		}

		if(!empty($parent_id) && (!is_numeric($parent_id) || $parent_id < 1))
		{
			$this->cakeError('invalid_field', array('field' => 'Table ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.add', $table_type, $table_id);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Upload', 'resource' => 'Document'));
			return;
		}

		$conditions = array(
			'Doc.table_type' => $table_type,
			'Doc.table_id' => $table_id,
			'Doc.parent_id' => null,
		);

		if(!empty($parent_id))
		{
			$conditions = array(
				'Doc.id' => $parent_id,
			);
		}

		$root = $this->Doc->find('first', array(
			'conditions' => $conditions,
			'order' => 'Doc.lft',
			'recursive' => -1,
		));
		if(empty($root))
		{
			$this->cakeError('internal_error', array('action' => 'Upload', 'resource' => 'Document Tree'));
			return;
		}

		if(!$this->PermissionCmp->check('document.add', $root['Doc']['table_type'], $root['Doc']['table_id']))
		{
			$this->cakeError('access_denied', array('action' => 'Upload', 'resource' => 'Document Tree'));
			return;
		}

		if(empty($this->data))
		{
			$this->cakeError('missing_field', array('field' => 'Form Data'));
			return;
		}
		
		$response = array(
			'success' => false,
		);

		$parent_id = $root['Doc']['id'];
		if(!empty($this->data['Doc']['parent_id']))
		{
			$parent = $this->Doc->find('first', array(
				'conditions' => array(
					'Doc.id' => $this->data['Doc']['parent_id'],
					'Doc.table_type' => $table_type,
					'Doc.table_id' => $table_id,
				),
				'order' => 'Doc.lft',
				'recursive' => -1,
			));

			if(!empty($parent))
			{
				$parent_id = $parent['Doc']['id'];
			}
		}
		
		$data = array(
			'Doc' => array(
				'table_type'  => $table_type,
				'table_id'    => $table_id,
				'title'	      => $this->data['Doc']['title'],
				'name'        => $this->data['Doc']['filename']['name'],
				'author_id'   => $this->Session->read('Auth.User.id'),
				'parent_id'   => $parent_id,
				'created'     => date('Y-m-d H:i:s'),
				'modified'    => date('Y-m-d H:i:s'),
				'description' => $this->data['Doc']['description'],
			),
		);
			
		try {
			if(!$this->FileCmp->is_uploaded_file($this->data['Doc']['filename']['tmp_name']))
			{
				$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
				return;
			}

			$filename = sha1(uniqid(rand(1000, 9000), true));
			while($this->FileCmp->exists(APP . DS . 'documents' . DS . $filename))
			{
				$filename = sha1(uniqid(rand(1000, 9000), true));
			}

			if(!$this->FileCmp->move_uploaded_file($this->data['Doc']['filename']['tmp_name'], APP . DS . 'documents' . DS . $filename))
			{
				$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
				return;
			}

			$mimetype = $this->FileCmp->mimetype(APP . DS . 'documents' . DS . $filename);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
			return;
		}

		$filesize = 0;
		$checksum = null;

		try {
			$revision = $this->Subversion->add_document(APP . DS . 'documents' . DS . $filename);
			if($this->FileCmp->exists(APP . DS . 'documents' . DS . $filename))
			{
				$filesize = $this->FileCmp->filesize(APP . DS . 'documents' . DS . $filename);
				$checksum = $this->FileCmp->checksum(APP . DS . 'documents' . DS . $filename);

				$this->FileCmp->remove(APP . DS . 'documents' . DS . $filename);
			}
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
			return;
		}

		if(!$revision)
		{
			$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
			return;
		}
		
		$data['Doc']['filename'] = $filename;
		$this->Doc->create();
		if(!$this->Doc->save($data))
		{
			$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
			return;

		}		
		
		$doc_id = $this->Doc->id;

		$data = array(
			'DocsVersion' => array(
				'doc_id' => $doc_id,
				'author_id' => $this->Session->read('Auth.User.id'),
				'date' => date('Y-m-d H:i:s'),
				'version' => 1,
				'revision' => $revision,
				'name' => $this->data['Doc']['filename']['name'],
				'mimetype' => $mimetype,
				'size' => $filesize,
				'changelog' => 'Initial Upload',
				'checksum' => $checksum,
			),
		);
		$this->DocsVersion->create();
		if(!$this->DocsVersion->save($data))
		{
			$this->cakeError('internal_error', array('action' => 'Add Version', 'resource' => 'Docs'));
			return;
		}

		try {
			$this->Plugin->broadcastListeners('docs.add', array(
				$doc_id,
				$table_type,
				$table_id,
				$this->data['Doc']['title'],
			));
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Document'));
			return;
		}
		
		$response = array(
			'success' => true,
		);

		$this->set('response', $response);
	}

	/**
	 * Edits a Document
	 *
	 * @param interger $doc_id Document ID
	 */
	function edit($doc_id = '')
	{
		if(!$this->RequestHandler->prefers('extjs'))
		{
			$this->cakeError('error404');
			return;
		}

		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'recursive' => -1,
		));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.edit', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Edit', 'resource' => 'Document'));
			return;
		}

		if(empty($this->data))
		{
			$this->cakeError('missing_field', array('field' => 'Form Data'));
			return;
		}

		$response = array(
			'success' => false,
		);

		$this->data['Doc']['id'] = $doc_id;

		if(!$this->Doc->save($this->data))
		{
			$this->cakeError('internal_error', array('action' => 'Edit', 'resource' => 'Document'));
			return;
		}

		try {
			$this->Plugin->broadcastListeners('docs.edit', array(
				$doc_id,
				$doc['Doc']['table_type'],
				$doc['Doc']['table_id'],
				$this->data['Doc']['title'],
			));
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Edit', 'resource' => 'Document'));
		}

		$response = array(
			'success' => true,
		);

		$this->set('response', $response);
	}

	/** 
	 * Deletes a Document
	 *
	 * @param integer $doc_id Document ID
	 */
	function delete($doc_id = '')
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array('conditions' => array('Doc.id' => $doc_id), 'order' => 'Doc.lft', 'recursive' => -1));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		if(empty($doc['Doc']['parent_id']))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.delete', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Delete', 'resource' => 'Document'));
			return;
		}

		$children = $this->Doc->children($doc_id);

		/*
		 * TODO: Delete children
		 * We need to recurively delete the 
		 * document, and if it's a folder
		 * then delete it's children, and their
		 * children, etc.
		 */

		if(!$doc['Doc']['type'] === 'folder')
		{
			try {
				$this->Subversion->remove_document($doc['Doc']['filename']);
			} catch(Exception $e) {
				// Ignore
			}
		}

		$this->Doc->delete($doc_id, true);

		try {
			$this->Plugin->broadcastListeners('docs.delete', array(
				$doc_id,
				$doc['Doc']['table_type'],
				$doc['Doc']['table_id'],
				$doc['Doc']['title'],
			));
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Delete', 'resource' => 'Document'));
		}

		$response = array(
			'success' => true,
		);

		$this->set('response', $response);
	}

	/**
	 * Downloads a Document
	 *
	 * @param integer $doc_id     Document ID
	 * @param integer $version_id Document Version ID
	 */
	function download($doc_id = '', $version_id = '')
	{
		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'recursive' => -1,
		));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.view', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Download', 'resource' => 'Document'));
			return;
		}

		if(!empty($version_id) && (!is_numeric($version_id) || $version_id < 1))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$conditions = array('DocsVersion.doc_id' => $doc_id);
		if(!empty($version_id))
		{
			$conditions = array('DocsVersion.id' => $version_id);
		}

		$version = $this->DocsVersion->find('first', array(
			'conditions' => $conditions,
			'order' => 'DocsVersion.version DESC',
			'fields' => array(
				'DocsVersion.revision',
				'DocsVersion.mimetype',
				'DocsVersion.version',
				'DocsVersion.size',
			),
			'recursive' => -1,
		));
		if(empty($version))
		{
			$this->cakeError('invalid_field', array('field' => 'Version ID'));
			return;
		}

		try {
			$data = $this->Subversion->download_document($doc['Doc']['filename'], $version['DocsVersion']['revision']);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Download', 'resource' => 'Document'));
			return;
		}

		$extension = '';
		if(preg_match('/\.(\S+)$/', $doc['Doc']['name'], $matches))
		{
			$extension = $matches[1];
		}

		$filename = addslashes($doc['Doc']['title']);
		if(!empty($version_id))
		{
			$filename .= " - v{$version['DocsVersion']['version']}";
		}
		$filename .= ".$extension";

		try {
			$this->FileCmp->output($filename, $version['DocsVersion']['mimetype'], $version['DocsVersion']['size'], $data);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Download', 'resource' => 'Document'));
			return;
		}

		/*
		 * This is necessary to stop the rendering of the
		 * default layout while still allowing tests
		 * to work.
		 */
		$this->_stop();
		return;
	}

	/**
	 * Checks in a Document
	 *
	 * @param integer $doc_id Document ID
	 */
	function checkin($doc_id = '')
	{
		if(!$this->RequestHandler->prefers('extjs'))
		{
			$this->cakeError('error404');
			return;
		}

		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'contain' => array(
				'DocsVersion',
			),
			'recursive' => 1,
		));
		if(empty($doc) || $doc['Doc']['status'] == 'in')
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}	

		$permission = $this->PermissionCmp->check('document.checkin', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Check In', 'resource' => 'Document'));
			return;
		}

		$response = array(
			'success' => false,
		);

		if(!empty($this->data))
		{
			$this->data['Doc']['id'] = $doc_id;

			try {
				if(!$this->FileCmp->is_uploaded_file($this->data['Doc']['filename']['tmp_name']))
				{
					$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
					return;
				}

				$filename = $doc['Doc']['filename'];

				if(!$this->FileCmp->move_uploaded_file($this->data['Doc']['filename']['tmp_name'], APP . DS . 'documents' . DS . $filename))
				{
					$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
					return;
				}

				$name = $this->data['Doc']['filename']['name'];

				unset($this->data['Doc']['filename']);

				//TODO: Check output of this function
				$mimetype = $this->FileCmp->mimetype(APP . DS . 'documents' . DS . $filename);
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
				return;
			}

			/* Generate Checksum/Determine File Size */
			$filesize = 0;
			$checksum = null;

			try {
				$filesize = $this->FileCmp->filesize(APP . DS . 'documents' . DS . $filename);
				$checksum = $this->FileCmp->checksum(APP . DS . 'documents' . DS . $filename);
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
				return;
			}

			/* Get Previous Version of File */
			$version = array_shift($doc['DocsVersion']);
			if(empty($version))
			{
				$this->cakeError('internal_error', array('action' => 'Determine', 'resource' => 'Document Version'));
				return;
			}

			/* Compare Checksums */
			if($version['checksum'] == $checksum)
			{
				$revision = $version['revision'];
			}
			else
			{
				try {
					$revision = $this->Subversion->checkin_document(APP . DS . 'documents' . DS . $filename);
				} catch(Exception $e) {
					$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
					return;
				}
			}

			try {
				$this->FileCmp->remove(APP . DS . 'documents' . DS . $filename);
			} catch(Exception $e) {
				// Ignore
			}

			if(!$revision)
			{
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
				return;
			}

			$this->data['Doc']['status'] = 'in';
			if(!$this->Doc->save($this->data))
			{
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
				return;
			}

			$data = array(
				'DocsVersion' => array(
					'doc_id' => $doc_id,
					'author_id' => $this->Session->read('Auth.User.id'),
					'date' => date('Y-m-d H:i:s'),
					'version' => $version['version'] + 1,
					'revision' => $revision,
					'name' => $name,
					'mimetype' => $mimetype,
					'size' => $filesize,
					'checksum' => $checksum,
					'changelog' => $this->data['DocsVersion']['changelog'],
				),
			);

			$this->DocsVersion->create();
			if(!$this->DocsVersion->save($data))
			{
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
				return;
			}

			try {
				$this->Plugin->broadcastListeners('docs.checkin', array(
					$doc_id,
					$doc['Doc']['table_type'],
					$doc['Doc']['table_id'],
					$doc['Doc']['title'],
				));
			} catch(Exception $e) {
				$this->cakeError('internal_error', array('action' => 'Check In', 'resource' => 'Document'));
			}

			$response = array(
				'success' => true,
			);
		}

		$this->set('response', $response);
	}

	/**
	 * Checks out a Document
	 *
	 * @param integer $doc_id Document ID
	 */
	function checkout($doc_id = '')
	{
		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'recursive' => -1,
		));
		if(empty($doc) || $doc['Doc']['status'] == 'out')
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.checkout', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Check Out', 'resource' => 'Document'));
			return;
		}

		$data = array(
			'Doc' => array(
				'id' => $doc_id,
				'status' => 'out',
				'current_user_id' => $this->Session->read('Auth.User.id'),
			),
		);
		$this->Doc->save($data);

		if(!$this->RequestHandler->prefers('json'))
		{
			$this->redirect('/docs/download/' . $doc_id);
			return;
		}

		try {
			$this->Plugin->broadcastListeners('docs.checkout', array(
				$doc_id,
				$doc['Doc']['table_type'],
				$doc['Doc']['table_id'],
				$doc['Doc']['title'],
			));
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Check Out', 'resource' => 'Document'));
		}

		$response = array(
			'success' => true,
		);

		$this->set('response', $response);
	}

	/**
	 * Cancels a Document Checkout
	 *
	 * @param integer $doc_id Document ID
	 */
	function cancel_checkout($doc_id = '')
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$doc = $this->Doc->find('first', array(
			'conditions' => array(
				'Doc.id' => $doc_id,
			),
			'recursive' => -1,
		));
		if(empty($doc) || $doc['Doc']['status'] == 'in')
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.cancel_checkout', $doc['Doc']['table_type'], $doc['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Cancel Checkout', 'resource' => 'Document'));
			return;
		}

		$data = array(
			'Doc' => array(
				'id' => $doc_id,
				'status' => 'in',
				'current_user_id' => null,
			),
		);
		if(!$this->Doc->save($data))
		{
			$this->cakeError('internal_error', array('action' => 'Cancel Checkout', 'resource' => 'Document'));
			return;
		}

		try {
			$this->Plugin->broadcastListeners('docs.cancel_checkout', array(
				$doc_id,
				$doc['Doc']['table_type'],
				$doc['Doc']['table_id'],
				$doc['Doc']['title'],
			));
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Cancel CheckOut', 'resource' => 'Document'));
		}

		$response = array(
			'success' => true,
		);

		$this->set('response', $response);
	}

	/**
	 * Reorders a Document in the Document Tree
	 */
	function reorder()
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		if(!isset($this->params['form']['node']) || empty($this->params['form']['node']))
		{
			$this->cakeError('missing_field', array('field' => 'Node'));
			return;
		}

		if(!isset($this->params['form']['delta']) || empty($this->params['form']['delta']))
		{
			$this->cakeError('missing_field', array('field' => 'Delta'));
			return;
		}

		$node_id = $this->params['form']['node'];
		$delta = $this->params['form']['delta'];

		if(!is_numeric($node_id) || $node_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Node ID'));
			return;
		}

		if(!is_numeric($delta))
		{
			$this->cakeError('invalid_field', array('field' => 'Node ID'));
			return;
		}

		$node = $this->Doc->find('first', array('conditions' => array('Doc.id' => $node_id), 'recursive' => -1));
		if(empty($node))
		{
			$this->cakeError('invalid_field', array('field' => 'Node'));
			return;
		}

		if(empty($node['Doc']['parent_id']))
		{
			$this->cakeError('invalid_field', array('field' => 'Node'));
			return;
		}

		if(!$this->PermissionCmp->check('document.view', $node['Doc']['table_type'], $node['Doc']['table_id']))
		{
			$this->cakeError('access_denied', array('action' => 'Reorder', 'resource' => 'Document'));
			return;
		}

		if($delta > 0)
		{
			if($this->Doc->moveDown($node_id, abs($delta)))
			{
				$response = array('success' => 1);
			}
			else
			{
				$this->cakeError('internal_error', array('action' => 'Reorder', 'resource' => 'Document'));
				return;
			}
		}
		else
		{
			if($this->Doc->moveUp($node_id, abs($delta)))
			{
				$response = array('success' => 1);
			}
			else
			{
				$this->cakeError('internal_error', array('action' => 'Reorder', 'resource' => 'Document'));
				return;
			}
		}

		$this->set('response', $response);
	}

	/**
	 * Reparents a document in the Document Tree
	 */
	function reparent()
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		if(!isset($this->params['form']['node']))
		{
			$this->cakeError('missing_field', array('field' => 'Node'));
			return;
		}

		if(!isset($this->params['form']['parent']))
		{
			$this->cakeError('missing_field', array('field' => 'Parent'));
			return;
		}

		if(!isset($this->params['form']['position']))
		{
			$this->cakeError('missing_field', array('field' => 'Position'));
			return;
		}

		$node_id = $this->params['form']['node'];
		$parent_id = $this->params['form']['parent'];
		$position = $this->params['form']['position'];

		if(!is_numeric($node_id) || $node_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Node ID'));
			return;
		}

		if(!is_numeric($parent_id) || $parent_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Node ID'));
			return;
		}

		if(!is_numeric($position) || $position < 0)
		{
			$this->cakeError('invalid_field', array('field' => 'Node ID'));
			return;
		}

		$node = $this->Doc->find('first', array('conditions' => array('Doc.id' => $node_id), 'recursive' => -1));
		if(empty($node))
		{
			$this->cakeError('invalid_field', array('field' => 'Node'));
			return;
		}

		if(empty($node['Doc']['parent_id']))
		{
			$this->cakeError('invalid_field', array('field' => 'Node'));
			return;
		}

		if(!$this->PermissionCmp->check('document.view', $node['Doc']['table_type'], $node['Doc']['table_id']))
		{
			$this->cakeError('access_denied', array('action' => 'Reparent', 'resource' => 'Document'));
			return;
		}

		$parent = $this->Doc->find('first', array('conditions' => array('Doc.id' => $parent_id), 'recursive' => -1));
		if(empty($parent))
		{
			$this->cakeError('invalid_field', array('field' => 'Parent'));
			return;
		}

		if(!$this->PermissionCmp->check('document.view', $parent['Doc']['table_type'], $parent['Doc']['table_id']))
		{
			$this->cakeError('access_denied', array('action' => 'Reparent', 'resource' => 'Document'));
			return;
		}

		$data = array(
			'Doc' => array(
				'id' => $node_id,
				'table_type' => $parent['Doc']['table_type'],
				'table_id' => $parent['Doc']['table_id'],
				'parent_id' => $parent_id,
			),
		);

		if(!$this->Doc->save($data))
		{
			$this->cakeError('internal_error', array('action' => 'Reparent', 'resource' => 'Document'));
			return;
		}

		if($position == 0)
		{
			$this->Doc->moveUp($node_id, true);
		}
		else
		{
			$count = $this->Doc->childCount($parent_id, true);
			$delta = $count - $position - 1;
			if($delta > 0)
			{
				$this->Doc->moveUp($node_id, abs($delta));
			}
		}

		/*
		 * TODO: We need to make this recursive
		 * so that if this document is a folder,
		 * all of the children of that document
		 * are also moved.
		 */

		$this->set('response', array('success' => 1));
	}

	/**
	 * Creates a Folder in the Document Tree
	 *
	 * @param integer $parent_id Folder ID
	 */
	function folder($parent_id = '')
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		if(empty($parent_id))
		{
			$this->cakeError('missing_field', array('field' => 'Parent ID'));
			return;
		}

		if(!is_numeric($parent_id) || $parent_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Parent ID'));
			return;
		}

		$parent = $this->Doc->find('first', array('conditions' => array('Doc.id' => $parent_id), 'recursive' => -1));
		if(empty($parent))
		{
			$this->cakeError('invalid_field', array('field' => 'Parent ID'));
			return;
		}

		$permission = $this->PermissionCmp->check('document.add', $parent['Doc']['table_type'], $parent['Doc']['table_id']);
		if(!$permission)
		{
			$this->cakeError('access_denied', array('action' => 'Add', 'resource' => 'Folder'));
			return;
		}

		if(!isset($this->params['form']['folder']) || empty($this->params['form']['folder']))
		{
			$this->cakeError('missing_field', array('field' => 'Folder'));
			return;
		}

		if(!is_string($this->params['form']['folder']))
		{
			$this->cakeError('invalid_field', array('field' => 'Folder'));
			return;
		}

		$title = $this->params['form']['folder'];

		$response = array(
			'success' => false,
		);

		if(isset($this->params['form']['id']))
		{
			if(empty($this->params['form']['id']))
			{
				$this->cakeError('missing_field', array('field' => 'Folder ID'));
				return;
			}

			if(!is_numeric($this->params['form']['id']) || $this->params['form']['id'] < 1)
			{
				$this->cakeError('invalid_field', array('field' => 'Folder ID'));
				return;
			}

			$folder_id = $this->params['form']['id'];

			$folder = $this->Doc->find('first', array('conditions' => array('Doc.id' => $folder_id), 'recursive' => -1));
			if(empty($folder))
			{
				$this->cakeError('invalid_field', array('field' => 'Folder ID'));
				return;
			}

			$this->Doc->id = $folder_id;
			if(!$this->Doc->saveField('title', $title))
			{
				$this->cakeError('internal_error', array('action' => 'Edit', 'resource' => 'Folder'));
				return;
			}

			$response = array(
				'success' => true,
			);
		}
		else
		{
			$data = array(
				'Doc' => array(
					'table_type' => $parent['Doc']['table_type'],
					'table_id' => $parent['Doc']['table_id'],
					'parent_id' => $parent_id,
					'title' => $title,
					'author_id' => $this->Session->read('Auth.User.id'),
					'created' => date('Y-m-d H:i:s'),
					'modified' => date('Y-m-d H:i:s'),
					'status' => 'in',
					'type' => 'folder',
				),
			);
			
			if(!$this->Doc->save($data))
			{
				$this->cakeError('internal_error', array('action' => 'Add', 'resource' => 'Folder'));
				return;
			}

			$response = array(
				'success' => true,
			);
		}

		$this->set('response', $response);
	}

	/**
	 * Generates a Document Checksum Document
	 *
	 * @param integer $doc_id     Document ID
	 * @param integer $version_id Document Version ID
	 */
	function checksum($doc_id = '', $version_id = '')
	{
		if(empty($doc_id))
		{
			$this->cakeError('missing_field', array('field' => 'Document ID'));
			return;
		}

		if(!is_numeric($doc_id) || $doc_id < 1)
		{
			$this->cakeError('invalid_field', array('field' => 'Docuemnt ID'));
			return;
		}	

		$doc = $this->Doc->find('first', array('conditions' => array('Doc.id' => $doc_id), 'recursive' => -1));
		if(empty($doc))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		if(!$this->PermissionCmp->check('document.view', $doc['Doc']['table_type'], $doc['Doc']['table_id']))
		{
			$this->cakeError('access_denied', array('action' => 'Download', 'resource' => 'Document'));
			return;
		}

		if(!empty($version_id) && (!is_numeric($version_id) || $version_id < 1))
		{
			$this->cakeError('invalid_field', array('field' => 'Document ID'));
			return;
		}

		$conditions = array(
			'DocsVersion.doc_id' => $doc_id,
		);
		if(!empty($version_id))
		{
			$conditions = array(
				'DocsVersion.id' => $version_id,
			);
		}

		$version = $this->DocsVersion->find('first', array(
			'conditions' => $conditions,
			'order' => 'DocsVersion.version DESC',
			'fields' => array(
				'DocsVersion.revision',
				'DocsVersion.mimetype',
				'DocsVersion.version',
				'DocsVersion.size',
				'DocsVersion.checksum',
			),
			'recursive' => -1,
		));
		if(empty($version))
		{
			$this->cakeError('invalid_field', array('field' => 'Version ID'));
			return;
		}

		$extension = '';
		if(preg_match('/\.(\S+)$/', $doc['Doc']['name'], $matches))
		{
			$extension = $matches[1];
		}

		$filename = addslashes($doc['Doc']['title']);
		$filename .= '.' . $extension;

		$data = $version['DocsVersion']['checksum'] . "\t" . $filename;

		try {
			$this->FileCmp->output($filename . '.sha1.sum', 'text/plain', strlen($data), $data);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Generate', 'resource' => 'Document Checksum'));
			return;
		}

		$this->_stop();
		return;
	}
	
	/**
	 * Help for Documents
	 */
	function help_index()
	{
		$this->pageTitle = 'Help - Index - Docs';
		$this->set('pageName', 'Docs - Index - Help');
	}

	/**
	 * Help for Group
	 */
	function help_group()
	{
		$this->pageTitle = 'Help - Group - Docs';
		$this->set('pageName', 'Docs - Group - Help');
	}

	/**
	 * Help for Project
	 */
	function help_project()
	{
		$this->pageTitle = 'Help - Project - Docs';
		$this->set('pageName', 'Docs - Project - Help');
	}

	/**
	 * Help for View
	 */
	function help_view()
	{
		$this->pageTitle = 'Help - View - Docs';
		$this->set('pageName', 'Docs - View - Help');
	}

	/**
	 * Help for Versions
	 */
	function help_versions()
	{
		$this->pageTitle = 'Help - Versions - Docs';
		$this->set('pageName', 'Docs - Versions - Help');
	}

	/**
	 * Help for Add
	 */
	function help_add()
	{
		$this->pageTitle = 'Help - Add - Docs';
		$this->set('pageName', 'Docs - Add - Help');
	}

	/**
	 * Help for Edit
	 */
	function help_edit()
	{
		$this->pageTitle = 'Help - Edit - Docs';
		$this->set('pageName', 'Docs - Edit - Help');
	}
}
?>
