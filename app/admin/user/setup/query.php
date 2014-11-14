<?php
$user = new stdClass();
$user->fullName	= '';
$user->username	= '';
$user->email	= '';
$user->role		= '';

if($id){
	$sql = 'SELECT * FROM '.db_prefix().'user WHERE uid = :uid LIMIT 1';
	if($result = db_query($sql, array(':uid' => $id))){
		$user = db_fetchObject($result);
	}
}
