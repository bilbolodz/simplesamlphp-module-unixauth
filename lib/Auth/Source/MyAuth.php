<?php

// namespace SimpleSAML\Module\unixauth\Auth\Source;

// class unixauth extends \sspmod_core_Auth_UserPassBase {
class sspmod_unixauth_Auth_Source_MyAuth extends sspmod_core_Auth_UserPassBase {
	
	/*
	 * The database DSN.
	 * See the documentation for the various database drivers for information about the syntax:
	 * http://www.php.net/manual/en/pdo.drivers.php
	 */
	private $dsn;
	
	/* The database username & password. */
	private $username;
	private $password;
	private $auth_mech;
	private $use_rc_database;
	private $table_name;
	private $mail_host;
	private $imap_hostname;
	private $imap_port;
	private $imap_security;
	private $imap_additional_options;
	private $ssh_hostname;
	private $ssh_port;
	public function __construct($info, $config) {
		parent::__construct ( $info, $config );
		
		// Authentication mechanism. Supported: ssh and imapd
		if (! is_string ( $config ['auth_mech'] )) {
			throw new Exception ( 'Missing auth_mech option in config.' );
		} else {
			switch ($config ['auth_mech']) {
				
				case "ssh" :
					// ssh host
					if (! is_string ( $config ['ssh_hostname'] )) {
						throw new Exception ( 'Missing or invalid ssh_hostname option in config.' );
					}
					$this->ssh_hostname = $config ['ssh_hostname'];
					
					// ssh port
					if (! is_string ( $config ['ssh_port'] )) {
						throw new Exception ( 'Missing or invalid ssh_port option in config.' );
					}
					$this->ssh_port = $config ['ssh_port'];
					
					$this->auth_mech = $config ['auth_mech'];
					break;
				
				case "imapd" :
					// imap host
					if (! is_string ( $config ['imap_hostname'] )) {
						throw new Exception ( 'Missing or invalid imap_hostname option in config.' );
					}
					$this->imap_hostname = $config ['imap_hostname'];
					
					// imap port
					if (! is_string ( $config ['imap_port'] )) {
						throw new Exception ( 'Missing or invalid imap_port option in config.' );
					}
					$this->imap_port = $config ['imap_port'];
					
					// imap securyty option see http://php.net/manual/en/function.imap-open.php
					if (! is_string ( $config ['imap_security'] )) {
						throw new Exception ( 'Missing or invalid imap_security option in config.' );
					}
					$this->imap_security = $config ['imap_security'];
					
					// imap additional option e.x. '/novalidate-cert see more http://php.net/manual/en/function.imap-open.php
					if (is_string ( $config ['imap_additional_options'] )) {
						$this->imap_additional_options = $config ['imap_additional_options'];
					}
					$this->auth_mech = $config ['auth_mech'];
					break;
				default :
					throw new Exception ( 'Unknown auth_mech: ' . $config ['auth_mech'] . ' option in config.' );
					break;
			}
		}
		
		// mail host name used for unknown email address
		if (! is_string ( $config ['mail_host'] )) {
			throw new Exception ( 'Missing or invalid mail_host option in config.' );
		}
		$this->mail_host = $config ['mail_host'];
		
		if (isset ( $config ['use_rc_database'] )) {
			if ($config ['use_rc_database'] == true) {
				$this->use_rc_database = $config ['use_rc_database'];
				if (! is_string ( $config ['dsn'] )) {
					throw new Exception ( 'Missing or invalid dsn option in config.' );
				}
				$this->dsn = $config ['dsn'];
				
				if (! is_string ( $config ['table_name'] )) {
					throw new Exception ( 'Missing or invalid table_name option in config.' );
				}
				$this->table_name = $config ['table_name'];
				
				if (! is_string ( $config ['username'] )) {
					throw new Exception ( 'Missing or invalid username option in config.' );
				}
				$this->username = $config ['username'];
				
				if (! is_string ( $config ['password'] )) {
					throw new Exception ( 'Missing or invalid password option in config.' );
				}
				$this->password = $config ['password'];
			}
		}
	}
	protected function login($username, $password) {
		
		//Does user enter email address insted of username?
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
		$parts = explode('@', $username);
		$username = $parts[0];    
		}

		// Defaults if there is no database entry
		$email = $username . "@" . $this->mail_host;
		$email_imap = "";				
		$uid = "UID=" . $username . ",DC=" . $this->mail_host;
		$name = "CN=" . $username . ",DC=" . $this->mail_host;
		$authenticated = false;
						
		switch ($this->auth_mech) {
			
			case "imapd" :
				$imap = $this->imap_hostname . ":" . $this->imap_port . "/" . $this->imap_security;
				if (isset ( $this->imap_additional_options )) {
					$imap = $imap . $this->imap_additional_options;
				}
				$imap = "{" . $imap . "}";
				
				$matches = array ();
				$stripbr = array ();
				
				$sess = imap_open ( $imap, $username, $password, OP_READONLY, 1 );
				
				if ($sess === false) {
					throw new SimpleSAML_Error_Error ( 'WRONGUSERPASS' );
				} else {
					$authenticated = true;
					if (($last = imap_num_msg ( $sess )) > 0) {
						$head1 = imap_fetchheader ( $sess, $last );
						preg_match ( '/Envelope\-to\:(.+)/m', $head1, $matches );
						$candidate = trim ( ($matches [1]) );
						if (filter_var ( $candidate, FILTER_VALIDATE_EMAIL ))
							$email_imap = $candidate;
					} elseif (imap_reopen ( $sess, $imap . "Sent", OP_READONLY )) {
						if (($last = imap_num_msg ( $sess )) > 0) {
							$head2 = imap_fetchheader ( $sess, $last );
							preg_match ( '/From\:(.+)/m', $head2, $matches );
							$candidate = trim ( ($matches [1]) );
							if (preg_match ( '/(?<=[<\[]).*?(?=[>\]]$)/', $candidate, $stripbr )) {
								$candidate = $stripbr [0];
								if (filter_var ( $candidate, FILTER_VALIDATE_EMAIL )) {
									$email_imap = $candidate;
								}
							}
						}
					}
					imap_close ( $sess );
				}
				if ($email_imap != "") {
					$email = $email_imap;
				}
				break;
			case "ssh" :
				$connection = ssh2_connect ( $this->ssh_hostname, $this->ssh_port );
				$output = @ssh2_auth_password ( $connection, $username, $password );
				if ($output === false) {
					throw new SimpleSAML_Error_Error ( 'WRONGUSERPASS' );
				} else {
					$authenticated = true;
				}
				break;
		}
		
		if ($this->use_rc_database == true) {
			try {
				/* Connect to the database. */
				$db = new PDO ( $this->dsn, $this->username, $this->password );
				$db->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				$db->exec ( "SET NAMES 'utf8'" );
				
				$table_name = $this->table_name;
				$sql_username = $username;
				
				$sql = 'SELECT user_id,username,name,email FROM `' . $table_name . '` WHERE username=:username LIMIT 1';
				
				$st = $db->prepare ( $sql );
				
				if (! $st->execute ( array (
						'username' => $sql_username 
				) )) {
					throw new Exception ( 'Failed to query database for user.' );
				}
				
				/* Retrieve the row from the database. */
				$row = $st->fetch ( PDO::FETCH_ASSOC );
				
				if ($row) {
					/* User found. */
					$email = $row ['email'];
					$name = $row ['name'];
					// To check
					// $user_id = $row ['user_id'];
					$uid = $row ['username'] . "_" . $this->mail_host;
				}
			} catch ( PDOException $e ) {
				throw new SimpleSAML_Error_Error ( 'DATABASE_ERROR:' . $e->getMessage () );
			}
		}
		
		if ($authenticated == true) {
			return array (
					'uid' => array (
							$uid 
					),
					'displayName' => array (
							$name 
					),
					'eduPersonTargetedID' => array (
							$uid 
					),
					'mail' => array (
							$email 
					),
					'cn' => array (
							$name 
					) 
			);
		}
	}
}

?>
