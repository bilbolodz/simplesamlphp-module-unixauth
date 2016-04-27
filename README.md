UnixAuth module
================

The simplesamlphp UnixAuth module provides method of authentication user against Unix server (supported IMAP and SSH authentication module).

After successful login it tries to find user email and returns it as 'mail' element.
If Roundcube (webmail client) SQL database is available module can read user personal data from identity entry.

To create a ImapAuth authentication source, open `config/authsources.php` in a text editor, and add an entry for the
authentication source:

	'imap_auth' => array(
	    'imapauth:MyAuth',
	    'auth_mech' => 'imapd',
		'ssh_hostname' => 'mazurek.man.lodz.pl',
		'ssh_port' => '22',
	    'use_rc_database' => true,
	    'dsn' => 'mysql:host=localhost;dbname=roundcube',
	    'table_name' => 'simplesasl_ident',
	    'username' => 'simplesasl',
	    'password' => 'XXXXXXXXXXXXX',
	    'mail_host' => 'mail_host_name',
	    'imap_hostname' => 'imap_host_name',
	    'imap_port' => '143',
	    'imap_security' => 'tls',
	    'imap_additional_options' => '/novalidate-cert'
	)

use_rc_database (true/false) - Use RC database to get personal data
dsn - Data Source Name of RC database
table_name - name of view from RC database to read personal data:
auth_mech - autehntication method: ssh/imapd

You should create view in RC database to read identity details:

CREATE VIEW `simplesasl_ident` AS select `users`.`user_id` AS `user_id`,lcase(`users`.`username`) AS `username`,`identities`.`name` AS `name`,`identities`.`email` AS `email` from (`users` join `identities`) where ((`users`.`user_id` = `identities`.`user_id`) and (`users`.`mail_host` = 'Mail host name from RC database'))

Don't forget to allow 'username' to read these view









