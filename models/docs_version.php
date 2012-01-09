<?php
class DocsVersion extends DocsAppModel
{
	var $name = 'DocsVersion';

	var $validate = array(
		'doc_id' => array(
			'doc_id-1' => array(
				'rule'    => 'notEmpty',
				'message' => 'Doc ID must not be empty.',
			),
			'doc_id-2' => array(
				'rule'    => 'numeric',
				'message' => 'Doc ID must be a number.',
			),
			'doc_id-3' => array(
				'rule'    => array('maxLength', 10),
				'message' => 'Doc ID must be 10 characters or less.',
			),
		),
		'author_id' => array(
			'author_id-1' => array(
				'rule'    => 'notEmpty',
				'message' => 'Author ID must not be empty.',
			),
			'author_id-2' => array(
				'rule'    => 'numeric',
				'message' => 'Author ID must be a number.',
			),
			'author_id-3' => array(
				'rule'    => array('maxLength', 10),
				'message' => 'Author ID must be 10 characters or less.',
			),
		),
		'date' => array(
			'rule'    => 'notEmpty',
			'message' => 'Date must not be empty.',
		),
		'version' => array(
			'version-1' => array(
				'rule'    => 'notEmpty',
				'message' => 'Version must not be empty.',
			),
			'version-2' => array(
				'rule'    => 'numeric',
				'message' => 'Version must be a number.',
			),
		),
		'revision' => array(
			'revision-1' => array(
				'rule'    => 'notEmpty',
				'message' => 'Revision must not be empty.',
			),
			'revision-2' => array(
				'rule'    => 'numeric',
				'message' => 'Revision must be a number.',
			),
			'revision-3' => array(
				'rule'    => array('maxLength', 10),
				'message' => 'Revision must be 10 characters or less.',
			),
		),
		'mimetype' => array(
			'mimetype-1' => array(
				'rule'    => 'notEmpty',
				'message' => 'Mimetype must not be empty.',
			),
			'mimetype-2' => array(
				'rule'    => array('maxLength', 255),
				'message' => 'Mimetype must be 255 characters or less.',
			),
		),
	);

	var $belongsTo = array(
		'Doc' => array(
			'className'  => 'Doc',
			'foreignKey' => 'doc_id',
		),
		'Author' => array(
			'className' => 'User',
			'foreignKey' => 'author_id',
		),
	);

	/**
	 * Converts a record to a ExtJS Store node
	 *
	 * @param array $version Version
	 * @param array $params  Parameters
	 *
	 * @return array ExtJS Store Node
	 */
	function toNode($version, $params = array())
	{
		if(!$version)
		{
			throw new InvalidArgumentException('Invalid Version');
		}

		if(!is_array($version))
		{
			throw new InvalidArgumentException('Invalid Version');
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

		if(!isset($version[$model]))
		{
			throw new InvalidArgumentException('Invalid Model Key');
		}

		$required = array(
			'id',
			'doc_id',
			'date',
			'version',
			'revision',
			'size',
			'changelog',
		);

		foreach($required as $key)
		{
			if(!array_key_exists($key, $version[$model]))
			{
				throw new InvalidArgumentException('Missing ' . strtoupper($key) . ' Key');
			}
		}

		$node = array(
			'id' => $version[$model]['id'],
			'doc_id' => $version[$model]['doc_id'],
			'author_id' => $version[$model]['author_id'],
			'author' => 'Unknown',
			'revised' => date('m/d/Y h:i a', strtotime($version[$model]['date'])),
			'version' => $version[$model]['version'],
			'revision' => $version[$model]['revision'],
			'size' => $this->formatSize($version[$model]['size']),
			'changelog' => $version[$model]['changelog'],
		);

		if(isset($version['Author']))
		{
			$node['author'] = $version['Author']['name'];
		}

		return $node;
	}
}
?>
