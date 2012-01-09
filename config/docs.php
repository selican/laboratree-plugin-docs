<?php
	Configure::write('Subversion.options', '--config-dir /data/laboratree/.subversion --username laboratree --password 86h32njf0H32jheeh20asdfUDJk');
	Configure::write('Subversion.destination', APP . DS . 'documents');
	Configure::write('Subversion.logdir', LOGS);
	Configure::write('Subversion.path', 'https://hoth/svn');
?>
