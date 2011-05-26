<?php

// from http://www.devshed.com/c/a/PHP/Creating-a-Secure-PHP-Login-Script/
// Been using it for years with no real troubles. Didn't want 
// to reinvent the wheel, so to speak. ;)

class User {
    private $DB;
	var $loginFailed = false;
	var $alreadyReg = false;
	var $notInCorp = false;
	var $id = 0;
	var $name = null;

	// this class is prone to session injection if anyone has write access 
	// to where temp php session files are stored. but, since this isn't used
	// for a credit card site, I don't think that's a big deal
	
	// Later on I MAY add a special session table that included session IDs
	// and IP addresses. This should help against session injection
	
	function __construct() {
		global $DB;
		$this->DB = $DB; //set database class
		
		if (!isset($_SESSION['charID'])) {
			$this->logout(); }
	} 
	
	function logout() {
		$_SESSION['logged'] = false;
		$_SESSION['charID'] = 0;
		$_SESSION['name']   = null;
	}

	function checkLogin($userID, $password) {
		$result = $this->DB->q("SELECT pass.pass, members.* FROM `passwords` AS pass LEFT JOIN memberList AS members ON (pass.charID = members.charID) WHERE pass.charID = ?", $userID);

		if ($this->generateHash($password, $result['pass']) === $result['pass']){
			$this->setSession($result);
			return true;
		} else {
			$this->loginFailed = true;
			$this->logout();
			return false;
		}
	}
	
	function regUser ($userID, $password) {
		$result = $this->DB->q("SELECT member.*, pass.pass FROM `memberList` AS member LEFT JOIN `passwords` AS pass ON (pass.charID = member.charID) WHERE member.charID = ?", (int)$_SERVER['HTTP_EVE_CHARID']);

		if ($result === false) {
			$this->notInCorp = true;
			return false; 
		}
 
		if ($result['pass'] !== null) {
			 return $this->checkLogin($userID, $password); // try logging in with password
		}

		// user is in corp and hasn't reg yet, add them
		$this->DB->ea("INSERT INTO `passwords` (`charID`, `pass`) VALUES (?, ?)", array($userID, $this->generateHash($password)));
		$this->setSession($result);
		return true;
	}

	function setSession($values) {
		$_SESSION['charID'] = $this->id = $values['charID'];
		$_SESSION['name']   = $this->name = $values['name'];
		$_SESSION['logged'] = true;
	} 

	function generateHash($plainText, $salt = null) {
		if ($salt === null) {
			$salt = substr(md5(uniqid(rand(), true)), 0, SALT_LENGTH); }
		else {
			$salt = substr($salt, 0, SALT_LENGTH); }
		return $salt . sha1($salt . $plainText);
	}
} 
?>