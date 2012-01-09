<?php 
/* SVN FILE: $Id$ */
/* DocsVersion Test cases generated on: 2010-12-20 14:56:10 : 1292856970*/
App::import('Model', 'DocsVersion');

class DocsVersionTestCase extends CakeTestCase {
	var $DocsVersion = null;
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url');

	function startTest() {
		$this->DocsVersion =& ClassRegistry::init('DocsVersion');
	}

	function testDocsVersionInstance() {
		$this->assertTrue(is_a($this->DocsVersion, 'DocsVersion'));
	}

	function testDocsVersionFind() {
		$this->DocsVersion->recursive = -1;
		$results = $this->DocsVersion->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('DocsVersion' => array(
			'id'  => 1,
			'doc_id'  => 1,
			'author_id'  => 1,
			'date'  => '2010-12-20 14:56:10',
			'version'  => 1.0,
			'revision'  => 1,
			'mimetype'  => 'image/png',
			'size'  => 100,
			'changelog'  => 'Initial Upload',
			'checksum'  => null,
		));
		$this->assertEqual($results, $expected);
	}
}
?>
