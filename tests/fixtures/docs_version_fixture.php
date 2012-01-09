<?php 
/* SVN FILE: $Id$ */
/* DocsVersion Fixture generated on: 2010-12-20 14:56:10 : 1292856970*/

class DocsVersionFixture extends CakeTestFixture {
	var $name = 'DocsVersion';
	var $table = 'docs_versions';
	var $fields = array(
		'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'doc_id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'index'),
		'author_id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'index'),
		'date' => array('type'=>'datetime', 'null' => false, 'default' => NULL),
		'version' => array('type'=>'float', 'null' => false, 'default' => NULL),
		'revision' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10),
		'mimetype' => array('type'=>'string', 'null' => false, 'default' => NULL),
		'size' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10),
		'changelog' => array('type'=>'text', 'null' => true, 'default' => NULL),
		'checksum' => array('type'=>'string', 'null' => false, 'default' => NULL, 'length' => 40, 'key' => 'index'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'doc_id' => array('column' => 'doc_id', 'unique' => 0), 'author_id' => array('column' => 'author_id', 'unique' => 0), 'checksum' => array('column' => 'checksum', 'unique' => 0))
	);
	var $records = array(
		array(
			'id'  => 1,
			'doc_id'  => 1,
			'author_id'  => 1,
			'date'  => '2010-12-20 14:56:10',
			'version'  => 1,
			'revision'  => 1,
			'mimetype'  => 'image/png',
			'size'  => 100,
			'changelog'  => 'Initial Upload',
			'checksum'  => ''
		),
		array(
			'id'  => 2,
			'doc_id'  => 19,
			'author_id'  => 1,
			'date'  => '2010-12-20 14:56:10',
			'version'  => 1,
			'revision'  => 1,
			'mimetype'  => 'image/png',
			'size'  => 100,
			'changelog'  => 'Initial Upload',
			'checksum'  => ''
		),
	);
}
?>
