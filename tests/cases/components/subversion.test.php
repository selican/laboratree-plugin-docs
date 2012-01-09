<?php
App::import('Controller', 'App');
App::import('Component', 'Subversion');

class SubversionComponentTestController extends AppController {
	var $name = 'Test';
	var $uses = array();
	var $components = array(
		'Subversion',
	);
}

class SubversionTest extends CakeTestCase
{
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest','app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');

	function startTest()
	{
		$this->Controller = new SubversionComponentTestController();
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
	}

	function testSubversionInstance() {
		$this->assertTrue(is_a($this->Controller->Subversion, 'SubversionComponent'));
	}

	function testStartup() {
		$this->Controller->Subversion->startup(&$controller);
	}

	function testCat()
	{
		$filename = 'testfile';

		$content = $this->Controller->Subversion->cat($filename);

		$expected = "Version 2\n";
		$this->assertEqual($content, $expected);
	}

	function testCatRevision()
	{
		$filename = 'testfile';
		$revision = 1469;

		$content = $this->Controller->Subversion->cat($filename, $revision);

		$expected = "Version 1\n";
		$this->assertEqual($content, $expected);
	}

	function testCatNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->Subversion->cat($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCatInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Subversion->cat($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCatInvalidRevision()
	{
		$filename = 'testfile';
		$revision = 'invalid';

		try
		{
			$this->Controller->Subversion->cat($filename, $revision);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddDelete()
	{
		$destination = $this->Controller->Subversion->destination;
		$filename = md5(uniqid('', true));
		file_put_contents($filename, 'Add Test');

		$result = $this->Controller->Subversion->add($filename);
		$expected = 'A         ' . $filename . "\n";
		$this->assertEqual($result, $expected);

		$result = $this->Controller->Subversion->delete($filename);
		$expected = 'D         ' . $filename . "\n";
	}

	function testAddNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->Subversion->add($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Subversion->add($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->Subversion->delete($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Subversion->delete($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}
/*
	function testDeleteInvalidFilenameDirectory()
	{
		$filename = $this->Controller->Subversion->destination;
		debug($filename);
		try
		{
			Configure::write('Subversion.destination', 'test');
			$this->Controller->Subversion->delete($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}
*/
	function testLog()
	{
		$filename = 'testfile';

		$results = $this->Controller->Subversion->log($filename);
		$expected = <<<XML
<?xml version="1.0"?>
<log>
<logentry
   revision="1470">
<author>brandon</author>
<date>2011-01-20T20:26:33.551845Z</date>
<msg>Version 2 of Testfile.</msg>
</logentry>
<logentry
   revision="1469">
<author>brandon</author>
<date>2011-01-20T20:25:56.805692Z</date>
<msg>File for Unit Testing.
</msg>
</logentry>
</log>

XML;
		$this->assertEqual($results, $expected);
	}

	function testLogNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->Subversion->log($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testLogInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Subversion->log($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	/*
	 * TODO: We don't want this test function
	 * cluttering up the subversion revision
	 * history. We should find a way to test
	 * this. Perhaps with a separate SVN repo.
	 *
	function testCommit()
	{
		$destination = $this->Controller->Subversion->destination;
		$filename = md5(uniqid('', true));
		file_put_contents($filename, 'Add Test');

		$message = 'Commit Test';

		$this->Controller->Subversion->add($filename);
		$result = $this->Controller->Subversion->commit($filename, $message);
		$this->assertTrue(is_numeric($result));

		$this->Controller->Subversion->delete($filename);
	}
	*/

	function testCommitNullFilename()
	{
		$filename = null;
		$message = 'Commit Test';

		try
		{
			$this->Controller->Subversion->commit($filename, $message);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCommitInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);
		$message = 'Commit Test';

		try
		{
			$this->Controller->Subversion->commit($filename, $message);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCommitNullMessage()
	{
		$filename = 'testfile';
		$message = null;

		try
		{
			$this->Controller->Subversion->commit($filename, $message);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCommitInvalidMessage()
	{
		$filename = 'testfile';
		$message = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Subversion->commit($filename, $message);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	/* TODO: Whenever we figure out how to test
	 * commit, we need to test the remainder of
	 * the functions in the subversion component.
	 *
	 * We can probably just override the commit
	 * function in a custom version of the
	 * Subversion component and that would take
	 * care of it.
	 */

	function testFullpath()
	{
		$filename = 'testfile';

		$fullpath = $this->Controller->Subversion->fullpath($filename);
		$expected = $this->Controller->Subversion->path . DS . $filename;

		$this->assertEqual($fullpath, $expected);
	}

	function testFullpathNullFilename()
	{
		$filename = null;
		$message = 'Fullpath Test';

		try
		{
			$this->Controller->Subversion->fullpath($filename, $message);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFullpathInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);
		$message = 'Fullpath Test';

		try
		{
			$this->Controller->Subversion->fullpath($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddDocumentEmptyFilename() {
		try {
			$this->Controller->Subversion->add_document(null);
			$this->fail();
		}

		catch(InvalidArgumentException $e) {
			$this->pass();
		}
	}

	function testAddDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->add_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testAddDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->add_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testAddDocumentValid() {
                try {
                        $this->Controller->Subversion->add_document('test.jpg');
                }

                catch(InvalidArgumentException $e) {
                        $this->fail();
                }
        }

	function testEditDocumentEmptyFilename() {
                try {
                        $this->Controller->Subversion->edit_document(null);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testEditDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->edit_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testEditDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->edit_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testEditDocumentValid() {
                        $this->Controller->Subversion->edit_document('test.jpg');
        }

	function testRemoveDocumentEmptyFilename() {
                try {
                        $this->Controller->Subversion->remove_document(null);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testRemoveDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->remove_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testRemoveDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->remove_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testRemoveDocumentValid() {
                        $this->Controller->Subversion->remove_document('test.jpg');
        }

	function testDownloadDocumentEmptyFilename() {
                try {
                        $this->Controller->Subversion->download_document(null);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testDownloadDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->download_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testDownloadDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->download_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testDownloadDocumentValid() {
                        $this->Controller->Subversion->download_document('test.jpg');
        }

	function testCheckoutDocumentEmptyFilename() {
                try {
                        $this->Controller->Subversion->checkout_document(null);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckoutDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->checkout_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckoutDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->checkout_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckoutDocumentValid() {
                        $this->Controller->Subversion->checkout_document('test.jpg');
        }

	function testCheckinDocumentEmptyFilename() {
                try {
                        $this->Controller->Subversion->checkin_document(null);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckinDocumentIntFilename() {
                try {
                        $this->Controller->Subversion->checkin_document(1);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckinDocumentBoolFilename() {
                try {
                        $this->Controller->Subversion->checkin_document(true);
                        $this->fail();
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testCheckinDocumentValid() {
                        $this->Controller->Subversion->checkin_document('test.jpg');
        }

	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();	
	}
}
?>
