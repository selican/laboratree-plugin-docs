<?php
class Doc extends DocsAppModel
{
	var $name   = 'Doc';
	var $actsAs = array('Tree', 'transaction');

	var $validate = array(
		'table_type' => array(
			'table_type-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Table type must not be empty.',
			),
			'table_type-2' => array(
				'rule' => array('inList', array('user', 'group', 'project')),
				'message' => 'Table type must be user, group, or project.',
			),
		),
		'table_id' => array(
			'table_id-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Table ID must not be empty.',
			),
			'table_id-2' => array(
				'rule' => 'numeric',
				'message' => 'Table ID must be a number.',
			),
			'table_id-3' => array(
				'rule' => array('maxLength', 10),
				'message' => 'Table ID must be 10 characters or less.',
			),
		),
		'parent_id' => array(
			'parent_id-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Parent ID must not be empty.',
			),
			'parent_id-2' => array(
				'rule' => 'numeric',
				'message' => 'Parent ID must be a number.',
			),
			'parent_id-3' => array(
				'rule' => array('maxLength', 10),
				'message' => 'Parent ID must be 10 characters or less.',
			),
		),
		'lft' => array(
			'lft-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Left must not be empty.',
			),
			'lft-2' => array(
				'rule' => 'numeric',
				'message' => 'Left must be a number.',
			),
			'lft-3' => array(
				'rule' => array('maxLength', 10),
				'message' => 'Left must be 10 characters or less.',
			),
		),
		'rght' => array(
			'rght-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Right must not be empty.',
			),
			'rght-2' => array(
				'rule' => 'numeric',
				'message' => 'Right must be a number.',
			),
			'rght-3' => array(
				'rule' => array('maxLength', 10),
				'message' => 'Right must be 10 characters or less.',
			),
		),
		'filename' => array(
			'filename-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Filename must not be empty.',
			),
			'filename-2' => array(
				'rule' => 'isUnique',
				'message' => 'Filename must be unique.',
			),
			'filename-3' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Filename must be 255 characters or less.',
			),
		),
		'title' => array(
			'title-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Title must not be empty.',
			),
			'title-2' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Title must be 255 characters or less.',
			),
		),
		'name' => array(
			'name-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Name must not be empty.',
			),
			'name-2' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Name must be 255 characters or less.',
			),
		),
		'author_id' => array(
			'author_id-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Author ID must not be empty.',
			),
			'author_id-2' => array(
				'rule' => 'numeric',
				'message' => 'Author ID must be a number.',
			),
			'author_id-3' => array(
				'rule' => array('maxLength', 10),
				'message' => 'Author ID must be 10 characters or less.',
			),
		),
		'created' => array(
			'rule' => 'notEmpty',
			'message' => 'Created must not be empty.',
		),
		'modified' => array(
			'rule' => 'notEmpty',
			'message' => 'Modified must not be empty.',
		),
		'status' => array(
			'status-1' => array(
				'rule' => 'notEmpty',
				'message' => 'Status must not be empty.',
			),
			'status-2' => array(
				'rule' => array('inList', array('in', 'out')),
				'message' => 'Status must be in or out.',
			),
		),
	);

	var $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'table_id',
		),
		'Group' => array(
			'className' => 'Group',
			'foreignKey' => 'table_id',
		),
		'Project' => array(
			'className' => 'Project',
			'foreignKey' => 'table_id',
		),
		'Author' => array(
			'className' => 'User',
			'foreignKey' => 'author_id',
		),
		'CurrentUser' => array(
			'className' => 'User',
			'foreignKey' => 'current_user_id',
		),
		
	);

	var $hasMany = array(
		'DocsVersion' => array(
			'className' => 'Docs.DocsVersion',
			'foreignKey' => 'doc_id',
			'order' => 'DocsVersion.version DESC',
			'dependent' => true,
			'exclusive' => true,
		),
	);

	/**
	 * Returns a document root
	 *
	 * @param string  $table_type Table Type
	 * @param integer $table_id   Table ID
	 * @param integer $recursive  Recursion Level
	 *
	 * @return array Document Root
	 */
	function root($table_type, $table_id, $recursive = -1)
	{
		if(!is_string($table_type) || !in_array($table_type, array('user', 'group', 'project')))
		{
			throw new InvalidArgumentException('Invalid table type.');
		}

		if(!is_numeric($table_id) || $table_id < 1)
		{
			throw new InvalidArgumentException('Invalid table id.');
		}

		return $this->find('first', array(
			'conditions' => array(
				$this->name . '.table_type' => $table_type,
				$this->name . '.table_id' => $table_id,
				$this->name . '.parent_id' => null,
			),
			'order' => $this->name . '.lft',
			'recursive' => $recursive,
		));
	}

	/**
	 * Converts a record to a ExtJS Store node
	 *
	 * @param array $doc    Document
	 * @param array $params Parameters
	 *
	 * @return array ExtJS Store Node
	 */
	function toNode($doc, $params = array())
	{
		if(!$doc)
		{
			throw new InvalidArgumentException('Invalid Doc');
		}

		if(!is_array($doc))
		{
			throw new InvalidArgumentException('Invalid Doc');
		}

		if(!is_array($params))
		{
			throw new InvalidArgumentException('Invalid Parameters');
		}

		if(!isset($params['model']))
		{
			$params['model'] = $this->name;
		}

		$model = $params['model'];

		if(!isset($doc[$model]))
		{
			throw new InvalidArgumentException('Invalid Model Key');
		}

		$required = array(
			'id',
			'title',
			'type',
			'table_type',
			'table_id',
			'status',
			'created',
		);

		foreach($required as $key)
		{
			if(!array_key_exists($key, $doc[$model]))
			{
				throw new InvalidArgumentException('Missing ' . strtoupper($key) . ' Key');
			}
		}

		$node = array(
			'id' => $doc[$model]['id'],
			'parent_id' => $doc[$model]['parent_id'],
			'title' => $doc[$model]['title'],
			'text' => $doc[$model]['title'],
			'author' => '',
			'status' => '',
			'size' => '',
			'description' => '',
			'created' => $doc[$model]['created'],
			'version' => '',
			'version_id' => '',
			'uiProvider' => 'col',
			'cls' => $doc[$model]['type'],
			'iconCls' => 'doc-' . $doc[$model]['type'],
			'table_type' => $doc[$model]['table_type'],
			'table_id' => $doc[$model]['table_id'],
			'expandable' => true,
			'leaf' => false,
		);

		if($doc[$model]['parent_id'] == null)
		{
			$node['draggable'] = false;
		}

		if($doc[$model]['type'] == 'file')
		{
			$node['leaf'] = true;
			
			$node['status']      = 'Checked ' . ucfirst($doc[$model]['status']);
			$node['size']        = 0;
			$node['description'] = '';
			$node['created']     = date('m/d/Y g:ia', strtotime($doc[$model]['created']));
			$node['checksum']    = '';
			$node['expandable']  = false;
			$node['version']     = 1;
			$node['version_id'] = '';
			$node['href'] = '/docs/view/' . $doc[$model]['id'];

			if(isset($doc[$model]['description']))
			{
				$node['description'] = $doc[$model]['description'];
			}

			if(isset($doc['DocsVersion']))
			{
				$version = array_shift($doc['DocsVersion']);
				if(!empty($version))
				{
					$node['version']  = $version['version'];
					$node['version_id'] = $version['id'];
					$node['size']     = $this->formatSize($version['size']);
					$node['checksum'] = $version['checksum'];
				}
			}

			if(isset($doc['Author']))
			{
				$node['author'] = $doc['Author']['name'];
			}
		}

		return $node;
	}
}
?>
