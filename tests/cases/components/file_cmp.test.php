<?php
App::import('Controller', 'App');
App::import('Component', 'FileCmp');

class FileCmpComponentTestController extends AppController {
	var $name = 'Test';
	var $uses = array();
	var $components = array(
		'FileCmp',
	);
}

class FileCmpTest extends CakeTestCase
{
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest', 'app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');

	function startTest()
	{
		$this->Controller = new FileCmpComponentTestController();
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
	}

	function testFileCmpInstance() {
		$this->assertTrue(is_a($this->Controller->FileCmp, 'FileCmpComponent'));
	}

	/*
	 * I have no idea how to fake a file
	 * upload in a way that will fool
	 * is_uploaded_file().
	 */
	function testIsUploadedFileTrue()
	{
	}

	function testIsUploadedFileFalse()
	{
	}

	function testIsUploadedFileNullFilename()
	{
		$filename = null;
			$this->Controller->FileCmp->is_uploaded_file($filename);
	}

	function testIsUploadedFileInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->is_uploaded_file($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	/*
	 * I have no idea how to fake a file
	 * upload in a way that will fool
	 * move_uploaded_file().
	 */
	function testMoveUploadedFile()
	{
	}

	function testMoveUploadedFileNullFilename()
	{
		$filename = null;
		$destination = TMP;

		try
		{
			$this->Controller->FileCmp->move_uploaded_file($filename, $destination);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testMoveUploadedFileInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);
		$destination = TMP;

		try
		{
			$this->Controller->FileCmp->move_uploaded_file($filename, $destination);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testMoveUploadedFileNullDestination()
	{
		$filename = '/data/laboratree/images/test.png';
		$destination = null;

		try
		{
			$this->Controller->FileCmp->move_uploaded_file($filename, $destination);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testMoveUploadedFileInvalidDestination()
	{
		$filename = '/data/laboratree/images/test.png';
		$destination = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->move_uploaded_file($filename, $destination);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChecksum()
	{
		$filename = '/data/laboratree/images/test.png';

		$checksum = $this->Controller->FileCmp->checksum($filename);

		$expected = 'b0a92c7e6d6b512ac5d62e877bdd41c752a0f25a';
		$this->assertEqual($checksum, $expected);
	}

	function testChecksumNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->FileCmp->checksum($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChecksumInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->checksum($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testMimetype()
	{
		$filename = '/data/laboratree/images/test.png';

		$mimetype = $this->Controller->FileCmp->mimetype($filename);

		$expected = 'image/png; charset=binary';
		$this->assertEqual($mimetype, $expected);
	}

	function testMimetypeNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->FileCmp->mimetype($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testMimetypeInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->mimetype($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFilesize()
	{
		$filename = '/data/laboratree/images/test.png';

		$filesize = $this->Controller->FileCmp->filesize($filename);

		$expected = 207;
		$this->assertEqual($filesize, $expected);
	}

	function testFilesizeNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->FileCmp->filesize($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testFilesizeInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->filesize($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testExistsTrue()
	{
		$filename = '/data/laboratree/images/test.png';

		$exists = $this->Controller->FileCmp->exists($filename);
		$this->assertTrue($exists);
	}

	function testExistsFalse()
	{
		$filename = '/data/laboratree/images/does-not-exist.png';

		$exists = $this->Controller->FileCmp->exists($filename);
		$this->assertFalse($exists);
	}

	function testExistsNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->FileCmp->exists($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testExistsInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->exists($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRemove()
	{
		$filename = TMP . DS . md5(uniqid('', true));
		file_put_contents($filename, 'Remove Test');

		$remove = $this->Controller->FileCmp->remove($filename);
		$this->assertTrue($remove);
	}

	function testRemoveNullFilename()
	{
		$filename = null;

		try
		{
			$this->Controller->FileCmp->remove($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRemoveInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->remove($filename);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	/*
	 * I have no idea how to test a
	 * function that uses header().
	 */
	function testOutput()
	{
	}

	function testOutputNullFilename()
	{
		$filename = null;
		$mimetype = 'image/png';
		$size = 120;
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);
		$mimetype = 'image/png';
		$size = 120;
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputNullMimetype()
	{
		$filename = '/data/laboratree/images/test.png';
		$mimetype = null;
		$size = 120;
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputInvalidMimetype()
	{
		$filename = '/data/laboratree/images/test.png';
		$mimetype = array(
			'invalid' => 'invalid',
		);
		$size = 120;
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputNullSize()
	{
		$filename = '/data/laboratree/images/test.png';
		$mimetype = null;
		$size = null;
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputInvalidSize()
	{
		$filename = '/data/laboratree/images/test.png';
		$mimetype = array(
			'invalid' => 'invalid',
		);
		$size = 'invalid';
		$data = 'Output Test';

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testOutputInvalidData()
	{
		$filename = '/data/laboratree/images/test.png';
		$mimetype = array(
			'invalid' => 'invalid',
		);
		$size = 'invalid';
		$data = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->output($filename, $mimetype, $size, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSave()
	{
		$filename = TMP . DS . md5(uniqid('', true));
		$data = 'Save Test';

		$size = $this->Controller->FileCmp->save($filename, $data);

		$expected = 9;
		$this->assertEqual($size, $expected);
		$this->assertTrue(file_exists($filename));

		unlink($filename);
	}

	function testSaveNullFilename()
	{
		$filename = null;
		$data = 'Save Test';

		try
		{
			$this->Controller->FileCmp->save($filename, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSaveInvalidFilename()
	{
		$filename = array(
			'invalid' => 'invalid',
		);
		$data = 'Save Test';

		try
		{
			$this->Controller->FileCmp->save($filename, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSaveInvalidData()
	{
		$filename = '/data/laboratree/images/test.png';
		$data = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->FileCmp->save($filename, $data);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();	
	}
}
?>
