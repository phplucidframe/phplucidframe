<?php
/**
 * All custom validation helper functions specific to the site should be defined here.
 */

/**
* The custom validation function to check the retyped email address matching
* This is just for example
*
* @param string $emailRetyped The re-typed email address
* @param string $email The email address to check against
* @return boolean
*/
function validate_emailRetyped($emailRetyped, $email=''){
	if(empty($email)) return true;
	return (strcasecmp($emailRetyped, $email) == 0) ? true : false;
}
/**
 * Custom validation function for password confirmation
 * This is just for the example admin panel
 *
 * @param $value	(string) confirmed password
 * @param $pwd		(string) password need to be checked
 * @return boolean	TRUE for success; FALSE for failure
 */
function validate_confirmPassword($value, $pwd){
	if(empty($value)) return true;
	$confirmPwd = trim($pwd);
	return ($value == $pwd);
}
/**
 * Custom validation function to check user name duplicate
 * This is just for the example admin panel
 *
 * @param $value	(string) Name to check
 * @param $id		(string) uid if any
 * @return boolean	TRUE for no duplicate; FALSE for duplicate
 */
function validate_checkDuplicateUsername($value, $id=0){
	$value = strtolower($value);
	if(empty($value)) return true;

	$sql = 'SELECT uid FROM ' . db_prefix() . 'user WHERE LOWER(username) = ":value"';
	if($id) $sql .= ' AND uid <> :id';
	$sql .= ' LIMIT 1';
	$args = array(
		':value' => strtolower($value),
		':id' => $id
	);
	if($result = db_query($sql, $args)){
		return (db_numRows($result)) ? false : true;
	}
	return false;
}