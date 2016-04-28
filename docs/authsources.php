<?php

$config = array(

	'unix_auth_mbox' => array(
	    'unixauth:MyAuth',
		'auth_mech' => 'imapd',//imapd/ssh
		'ssh_hostname' => 'mazurek.man.lodz.pl',
		'ssh_port' => '22',
	    'use_rc_database' => true,
	    'dsn' => 'mysql:host=localhost;dbname=roundcube',
	    'table_name' => 'simplesasl_ident_mbox',
	    'username' => 'simplesasl',
	    'password' => 'XXXXXXXXXXXXXXXXXXX',
	    'mail_host' => 'mbox.man.lodz.pl',
	    'imap_hostname' => 'mbox.man.lodz.pl',
	    'imap_port' => '143',
	    'imap_security' => 'tls',
	    'imap_additional_options' => '/novalidate-cert'
	),
);
