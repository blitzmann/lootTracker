<?php

/**
 * User class
 * author: Ant P. <ant@specialops.ath.cx>
 * copyright: © 2002-2009 Special Ops
 * license: http://www.gnu.org/licenses/agpl.html
 * package: SO5
 *
 * Modified and extended by Ryan H. <ryan.xgamer99@gmail.com> 5.1
 */

class User extends EveApiRoles {
    private $DB;

	public $is_registered = false;
	public $is_logged_in  = false;
	
	var $stuff = array();

	/**
     * Create a new user object. This is mostly called by the login functions
     *
     * @param $uid integer Userid of user to load, assumes you've done all the authentication elsewhere
     * @param $login bool Tells the code whether to do stuff like updating timestamps and constructing user menus or not
     */
    function __construct($charID = null, $login = false) {
		global $DB;
			
		$this->DB = $DB; //set database class
        // Calls one of the two functions below depending on whether it's a registed user or not.
        if ($charID) {
            $this->loadUser($charID, $login);
			parent::__construct($this->roles);
        }
        else {
			return null; }
    }
	
	/**
     * Create a new user object for registered users
     */
    function loadUser($charID, $login) {
        global $DB;

        // Pull userinfo from DB into $stuff
        $userinfo = $DB->q('SELECT * FROM users NATURAL JOIN memberList WHERE charID = ?', $charID);

        // Check that this is a valid charID first
        if (!$userinfo['name']) {
            return; }

        $this->is_registered = true;

        $this->stuff = array_merge($this->stuff, $userinfo, array('charID' => $charID));

        // If $login is false then this isn't the user viewing the page, so don't bother updating login time etc.
        if (!$login) {
            return; }

        if (filter_has_var(INPUT_POST, 'logout')) {
            // Logout in two lines. Works on every page and also works if you logged in elsewhere and forgot to log that out.
            $DB->e('UPDATE users SET sessionID = null WHERE charID = ?', $charID);
            self::setcookie('sessionID', null);
        }
        else {
            $DB->e('UPDATE users SET login_date = UNIX_TIMESTAMP(), login_addr = INET_ATON(?) WHERE charID = ?', $_SERVER['REMOTE_ADDR'], $charID);
            $this->is_logged_in = true;
        }
    }
	
	/**
     * The bit that happens when they click "Log In"
     */
    static function login($charID, $password)
    {
        global $DB;

		$result = $DB->q("SELECT * FROM `users` WHERE charID = ?", $charID);
        $User = new self(
			(self::generateHash($password, $result['pass']) === $result['pass'] ? $result['charID'] : null)
			, true);						 
								 
        // Got a logged in user, set session cookie
        if ($User->is_logged_in) {

            if (!$User->sessionid) {
				$User->sessionID = base64_encode(hash('sha256', uniqid().$_SERVER['REMOTE_ADDR'], true));
                $DB->e("UPDATE users SET sessionID = ? WHERE charID = ?", $User->sessionID, $User->charID);
            }

            $expiry = 604800; // 7 days
            self::setcookie('sessionID', $User->sessionID, $expiry);
        }
        // Avoids the edge case where they log in and out in one action
        elseif (!$User->is_registered) {
            //header('Status: 400');
            //$DB->audit_msg(sprintf('Failed login attempt: user="%s" pass="%s"', $username, $password));
        }

        return $User;
    }
	
    /**
     * The bit that happens when they claim to have a login cookie
     */
	static function auth($sessionid) {
        global $DB;

        $User = new self($DB->q1('SELECT charID FROM users WHERE sessionID = ?', $sessionid), true);

        // User's session cookie is invalid, either from logging out elsewhere or just forging it. Delete it.
        if (!$User->is_registered) {
            self::setcookie('sessionID', null); }

        return $User;
    }
	
	/**
     * The bit that happens when they click "Register"
     */
	static function create($charID, $password) {
        global $DB;

        $errors = array();
		// test for corp members only

        if ($DB->q1('SELECT COUNT(*) FROM users WHERE charID = ?', $charID)) {
            $errors[] = 'CharacterID "'.$charID.'" is already registered.';
        }
		if (!$DB->q1('SELECT COUNT(*) FROM memberList WHERE charID = ?', $charID)) {
            $errors[] = 'CharacterID "'.$charID.'" is not part of the corp.';
        }

        if ( $errors ) {
/*
		   foreach ( $errors as $error ) {
                $Page->sysnote($error, E_USER_WARNING);
            }
			
            header('Status: 400');
            $DB->audit_msg(sprintf('Failed register attempt: user="%s" pass="%s" serialized_errors="%s"',
                                    $username, $password, serialize($errors)));
*/
            return new self;
        }

        $DB->e('INSERT INTO users (charID, pass) VALUES (?, ?)', $charID, self::generateHash($password));

        header('Status: 201');

        return self::login($charID, $password);
    }

	/*****************
	 * Magic Methods
	 */
	
	function __set($what, $to) {
        // If they're not logged in this shouldn't even be happening
        if (!$this->is_registered) {
            die('Tried to User->__set() while not logged in');
        }

        if ( $this->$what != $to ) {
            $this->stuff[$what] = $to;
        }
    }

	function __get($what) {
        // If it's not a registered user just return null (userlevel is a number because I didn't write soDB properly)
        if (!$this->is_registered) {
            return null; }

        return array_key_exists($what, $this->stuff) ? $this->stuff[$what] : null;
    }
	
	/**
     * Returns the character name
     */
    function __toString() {
        return $this->is_registered ? $this->stuff['name'] : '[nobody]'; }
	
	
	/*************
	 * Utilities
	 */
	 
    static function setcookie($name, $value, $max_age = 86400) {
		// deleting cookie
        if ( is_null($value) ) {
            $expiry = 1; }
        // session cookie
        elseif ( $max_age == -1 ) {
            $expiry = null; }

        // default is expire after max_age seconds; default default is one day
        else {
            $expiry = $_SERVER['REQUEST_TIME']+$max_age; }

		setcookie($name, $value, $expiry, dirname($_SERVER['SCRIPT_NAME']), $_SERVER['HTTP_HOST']);
    }
	
	function generateHash($plainText, $salt = null) {
		if ($salt === null) {
			$salt = substr(md5(uniqid(rand(), true)), 0, SALT_LENGTH); }
		else {
			$salt = substr($salt, 0, SALT_LENGTH); }
		return $salt.sha1($salt.$plainText);
	}
} 

?>