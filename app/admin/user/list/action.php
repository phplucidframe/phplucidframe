<?php
$success = false;
if(sizeof($_POST)){
	$post = _post($_POST);
	extract($post);
	if(isset($action) && $action == 'delete' && isset($hidDeleteId) && $hidDeleteId){
		# DELETE
		if( db_delete('user', array('uid' => $hidDeleteId)) ){
			$success = true;
		}
	}
}

if($success){
?>
Page.User.List.list();
<?php
}