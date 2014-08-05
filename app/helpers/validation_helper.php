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