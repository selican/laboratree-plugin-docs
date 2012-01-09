<?php
class SubversionComponent extends Object {
	var $components = array(
		'Session',
	);

	var $svn = '/usr/bin/svn';
	var $options = '';
	var $destination = '';
	var $logdir = '/tmp';
	var $path = '';

	function initialize(&$controller, $settings = array())
	{
		$this->Controller =& $controller;

		$this->options = Configure::read('Subversion.options');
		$this->destination = Configure::read('Subversion.destination');
		$this->logdir = Configure::read('Subversion.logdir');
		$this->path = Configure::read('Subversion.path');
	}

	function startup(&$controller) { }

	/**
	 * Does a SVN Cat on a File in Subversion
	 *
	 * @param string  $filename Filename
	 * @param integer $revision SVN Revision
	 */
	function cat($filename, $revision = '')
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename.');
		}

		if(!empty($revision) && !is_numeric($revision))
		{
			throw new InvalidArgumentException('Invalid revision.');
		}

		if(!chdir($this->destination))
		{
			throw new RuntimeException('Unable to change directory.');
		}

		$filename = escapeshellarg($filename);
		
		if(!empty($revision))
		{
			$revision = escapeshellarg($revision);
			$command = "{$this->svn} {$this->options} cat -r $revision $filename 2> {$this->logdir}" . DS . 'svn-cat.log';
		}
		else
		{
			$command = "{$this->svn} {$this->options} cat $filename 2> {$this->logdir}" . DS . 'svn-cat.log';
		}

		return shell_exec($command);
	}

	/**
	 * Adds a File to Subversion
	 *
	 * @param string $filename Filename
	 */
	function add($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		if(!chdir($this->destination))
		{
			throw new RuntimeException('Unable to change directory.');
		}

		$filename = escapeshellarg($filename);

		$command = "{$this->svn} {$this->options} add $filename 2> {$this->logdir}" . DS . 'svn-add.log';
		return shell_exec($command);
	}

	/**
	 * Deletes a File from Subversion
	 *
	 * @param string $filename Filename
	 */
	function delete($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		/*
		 * This is an essential if statement. This
		 * tests to make sure we aren't accidentally
		 * deleting the root directory.
		 */
		if(is_dir($this->destination . DS . $filename))
		{
			throw new InvalidArgumentException('Invalid filename.');
		}

		if(!chdir($this->destination))
		{
			throw new RuntimeException('Unable to change directory.');
		}

		$filename = escapeshellarg($filename);

		$command = "{$this->svn} {$this->options} delete $filename 2> {$this->logdir}" . DS . 'svn-delete.log';
		return shell_exec($command);
	}

	/**
	 * Reads the log for a file in Subversion
	 *
	 * @param string $filename Filename
	 */
	function log($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		if(!chdir($this->destination))
		{
			throw new RuntimeException('Unable to change directory.');
		}

		$filename = escapeshellarg($filename);

		$command = "{$this->svn} {$this->options} log --xml $filename 2> {$this->logdir}" . DS . 'svn-log.log';
		return shell_exec($command);
	}

	/**
	 * Commits a revision into Subversion
	 *
	 * @param string $filename Filename
	 * @param string $message  Commit Message
	 */
	function commit($filename, $message)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		if(empty($message) || !is_string($message))
		{
			throw new InvalidArgumentException('Invalid message.');
		}

		if(!chdir($this->destination))
		{
			throw new RuntimeException('Unable to change directory.');
		}

		$message = escapeshellarg($message);

		$command = "{$this->svn} {$this->options} commit -m \"$message\" $filename 2> {$this->logdir}" . DS . 'svn-commit.log';
		$output = shell_exec($command);

		$revision = 0;
		if(preg_match('/Committed revision (\d+)\./', $output, $matches))
		{
			$revision = $matches[1];
		}

		return $revision;
	}

	/**
	 * Adds a Document to Subversion and Commits it
	 *
	 * @param string $filename Filename
	 */
	function add_document($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		$this->add($filename);
		return $this->commit($filename, "ADD::$filename::" . $this->Session->read('Auth.User.id'));
	}

	/**
	 * Edits a Document in Subversion and Commits it
	 *
	 * @param string $filename Filename
	 */
	function edit_document($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		return $this->commit($filename, "EDIT::$filename::" . $this->Session->read('Auth.User.id'));
	}

	/**
	 * Removes a Document from Subversion and Commits it
	 *
	 * @param string $filename Filename
	 */
	function remove_document($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		$this->delete($filename);
		return $this->commit($filename, "REMOVE::$filename::" . $this->Session->read('Auth.User.id'));
	}

	/**
	 * Wrapper for SVN Cat
	 *
	 * @param string $filename Filename
	 * @param string $revision Subversion Revision
	 */
	function download_document($filename, $revision = '')
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		return $this->cat($filename, $revision);
	}

	/**
	 * Wrapper for SVN Cat
	 *
	 * @param string $filename Filename
	 */
	function checkout_document($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		return $this->cat($filename);
	}

	/**
	 * Wrapper for SVN Commit
	 *
	 * @param string $filename Filename
	 */
	function checkin_document($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		return $this->commit($filename, "CHECKIN::$filename::" . $this->Session->read('Auth.User.id'));
	}

	/**
	 * Returns the full path for a filename
	 *
	 * @param string $filename Filename
	 */
	function fullpath($filename)
	{
		if(empty($filename) || !is_string($filename))
		{
			throw new InvalidArgumentException('Invalid filename');
		}

		return $this->path . DS . $filename;
	}
}
?>
