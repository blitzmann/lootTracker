<?php
/**
 * Class used to interact with roles returned by character EVE API.
 *
 * This class can also be used with same information return from the EVE IGB.
 *
 * @author Michael Cummings <mgcummings de-spam at yahoo.com>
 * @copyright Copyright (c) 2008, Michael Cummings
 * @licence http://creativecommons.org/licenses/by-nc-sa/3.0/ This work is
 * licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 3.0
 * License.
 *
 */
 
class EveApiRoles {
 
	/**
	 * String used to hold binary bit map of roles.
	 */
	private $bitRoles='';
 
	/**
	 * Used to give description roles index by bit position.
	 *
	 * @see eveApiRoles::getRoleDescription()
	 */
	// Note how index is set for 'personal manager' to skip bit positions.
	protected $roleDescriptions=array(
		'director',7=>'personal manager','accountant','security officer',
		'factory manager','station manager','auditer',
		'hanger can take division 1','hanger can take division 2',
		'hanger can take division 3','hanger can take division 4',
		'hanger can take division 5','hanger can take division 6',
		'hanger can take division 7',
		'hanger can query division 1','hanger can query division 2',
		'hanger can query division 3','hanger can query division 4',
		'hanger can query division 5','hanger can query division 6',
		'hanger can query division 7',
		'account can take division 1','account can take division 2',
		'account can take division 3','account can take division 4',
		'account can take division 5','account can take division 6',
		'account can take division 7',
		'account can query division 1','account can query division 2',
		'account can query division 3','account can query division 4',
		'account can query division 5','account can query division 6',
		'account can query division 7',
		'equipment config',
		'container can take division 1','container can take division 2',
		'container can take division 3','container can take division 4',
		'container can take division 5','container can take division 6',
		'container can take division 7',
		'can rent office','can rent factory slot','can rent research slot',
		'junior accountant','starbase config','trader');
 
	/**
	 * Maps shorten role names to bit positions.
	 */
	// Note how index is set for 'perman' to skip un-used bit positions.
	protected $roleNames=array(
		'director',7=>'perman','accountant','secoff','facman','staman','auditer',
		'hangertake1','hangertake2','hangertake3','hangertake4','hangertake5',
		'hangertake6','hangertake7',
		'hangerquery1','hangerquery2','hangerquery3','hangerquery4','hangerquery5',
		'hangerquery6','hangerquery7',
		'accounttake1','accounttake2','accounttake3','accounttake4','accounttake5',
		'accounttake6','accounttake7',
		'accountquery1','accountquery2','accountquery3','accountquery4',
		'accountquery5','accountquery6','accountquery7',
		'equipconfig',
		'containertake1','containertake2','containertake3','containertake4',
		'containertake5','containertake6','containertake7',
		'rentoff','rentfact','rentres','jraccount','sbconfig','trader');
 
	/**
	 * Takes role string from EVE API and changes into object.
	 *
	 * Convert the decimal string (number) you past it into a binary bit map that
	 * class uses internally for it's methods.
	 *
	 * @param string $roles Decimal string containing roles.
	 *
	 * @return void
	 */
	function __construct($roles) {
		$this->roleNames=array_flip($this->roleNames);
		$digits='01';
		$bits='';
		bcscale(0);
		while ($roles) {
			$bits.=$digits[bcmod($roles,2)];
			$roles=bcdiv($roles,2);
		};
		$this->bitRoles=str_pad($bits,64,'0',STR_PAD_RIGHT);
	}
 
	/**
	 * Get the description of an EVE API role using number.
	 *
	 * @param integer $role bit position of role we want description of.
	 * @param boolean $uCase Returns description as uppercased words when true.
	 *
	 * @return string
	 */
	function getRoleDescriptionByBit($role,$uCase=FALSE) {
		$retval='unknown role';
		if (array_key_exists($role,$this->roleDescriptions)) {
			$retval=$this->roleDescriptions[$role];
		};
		if ($uCase==TRUE) {
			$retval=ucwords($retval);
		};
		return $retval;
	}
 
	/**
	 * Get the description of an EVE API role by shortened role name.
	 *
	 * @param string $role Shortened name of role we want description of.
	 * @param boolean $uCase Returns description as uppercased words when true.
	 *
	 * @return string
	 *
	 * @uses EveApiRoles::getRoleDescriptionByBit()
	 */
	function getRoleDescriptionByRole($role,$uCase=FALSE) {
		$retval='unknown role';
		if (array_key_exists($role,$this->roleNames)) {
			$retval=$this->getRoleDescriptionByBit($this->roleNames[$role]);
		};
		if ($uCase==TRUE) {
			$retval=ucwords($retval);
		};
		return $retval;
	}
 
	/**
	 * Get the binary bit map of the roles.
	 *
	 * Returns the binary bit map as LSB...MSB 64 character long string. Set
	 * $reverse to true to get it as MSB...LSB string.
	 *
	 * @param boolean $reverse Will return bit map string in MSB...LSB when true.
	 *
	 * @return string
	 */
	function getRoles($reverse=FALSE) {
		if ($reverse==TRUE) {
			return strrev($this->bitRoles);
		};
		return $this->bitRoles;
	}
 
	/**
	 * See if role is set.
	 *
	 * Using string matching values in $roleNames see if that role is set or not.
	 *
	 * @param string $roleMask Role to check for.
	 *
	 * @return boolean
	 */
	function hasRole($roleMask) {
		if (array_key_exists($roleMask,$this->roleNames)) {
			$bit=$this->bitRoles[$this->roleNames[$roleMask]];
			if ($bit==='1') {
				return TRUE;
			};
		};
		return FALSE;
	}
 
	/**
	 * See if a group of roles is set.
	 *
	 * Using array of strings matching values in $roleNames to see if those roles
	 * are set or not.
	 *
	 * @param array $roleMask Roles to check for.
	 *
	 * @return boolean
	 *
	 * @uses EveApiRoles::hasRole()
	 */
	function hasRoles(array $roleMask) {
		if (!empty($roleMask)) {
			foreach ($roleMask as $mask) {
				if (!$this->hasRole($mask)) {
					return FALSE;
				};
			};
			return TRUE;
		};
		return FALSE;
	}
 
}
?>