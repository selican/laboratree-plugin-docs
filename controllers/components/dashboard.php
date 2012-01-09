<?php
class DashboardComponent extends Object
{
	var $uses = array(
		'Docs.Doc',
		'GroupsUsers',
		'ProjectsUsers',
	);

	function _loadModels(&$object)
	{
		foreach($object->uses as $modelClass)
		{
			$plugin = null;

			if(strpos($modelClass, '.') !== false)
			{
				list($plugin, $modelClass) = explode('.', $modelClass);
				$plugin = $plugin . '.';
			}

			App::import('Model', $plugin . $modelClass);
			$this->{$modelClass} = new $modelClass();

			if(!$this->{$modelClass})
			{
				return false;
			}
		}
	}

	function initialize(&$controller, $settings = array())
	{
		$this->Controller =& $controller;
		$this->_loadModels($this);
	}

	function startup(&$controller) {}

	function process($table_type, $table_id, $params = array())
	{
		$docs = array();

		if($table_type == 'user')
		{
			if(isset($params['form']['node']) && !preg_match('/xnode-/', $params['form']['node']))
			{
				if(preg_match('/^root-(group|project)-(\d+)$/', $params['form']['node'], $matches))
				{
					switch($matches[1])
					{
						case 'group':
							try {
								$root = $this->Doc->root('group', $matches[2], 1);
							} catch(Exception $e) {
								throw new RuntimeException('Unable to retrieve document root');
							}

							if(empty($root))
							{
								throw new RuntimeException('Unable to retrieve document root');
							}

							$docs = $this->Doc->children($root['Doc']['id'], true, null, 'lft ASC');
							break;
						case 'project':
							try {
								$root = $this->Doc->root('project', $matches[2], 1);
							} catch(Exception $e) {
								throw new RuntimeException('Unable to retrieve document root');
							}

							if(empty($root))
							{
								throw new RuntimeException('Unable to retrieve document root');
							}

							$docs = $this->Doc->children($root['Doc']['id'], true, null, 'lft ASC');
							break;
					}
				}
				else
				{
					$parent_id = $params['form']['node'];

					$this->Doc->id = $parent_id;
					if(!$this->Doc->exists())
					{
						throw new RuntimeException('Unable to retrieve document');
					}

					$docs = $this->Doc->children($parent_id, true, null, 'lft ASC');
				}
			}
			else
			{
				try {
					$groups = $this->GroupsUsers->groups($table_id);
				} catch(Exception $e) {
					throw new RuntimeException('Unable to retrieve groups');
				}

				foreach($groups as $group)
				{
					$docs[] = array(
						'Doc' => array(
							'id' => 'root-group-' . $group['Group']['id'],
							'title' => 'Group: ' . $group['Group']['name'],
							'type' => 'root',
							'table_type' => 'group',
							'table_id' => $group['Group']['id'],
							'status' => 'in',
							'created' => date('Y-m-d H:i:s'),
							'parent_id' => null,
						),
					);
				}

				try {
					$projects = $this->ProjectsUsers->projects($table_id);
				} catch(Exception $e) {
					throw new RuntimeException('Unable to retrieve projects');
				}
				foreach($projects as $project)
				{
					$docs[] = array(
						'Doc' => array(
							'id' => 'root-project-' . $project['Project']['id'],
							'title' => 'Project: ' . $project['Project']['name'],
							'type' => 'root',
							'table_type' => 'project',
							'table_id' => $project['Project']['id'],
							'status' => 'in',
							'created' => date('Y-m-d H:i:s'),
							'parent_id' => null,
						),
					);
				}
			}
		}
		else
		{
			if(isset($params['form']['node']) && !preg_match('/xnode-/', $params['form']['node']))
			{
				$parent_id = $params['form']['node'];

				$this->Doc->id = $parent_id;
				if(!$this->Doc->exists())
				{
					throw new Exception('Invalid Field: Parent');
				}

				$docs = $this->Doc->children($parent_id, true);
			}
			else
			{
				try {
					$docs = $this->Doc->root($table_type, $table_id, 0, 1);
				} catch(Exception $e) {
					throw new Exception($e);
				}
			}
		}

		try {
			$list = $this->Doc->toNodes($docs);
		} catch(Exception $e) {
			throw new Exception($e);
		}

		return $list;
	}
}
?>
