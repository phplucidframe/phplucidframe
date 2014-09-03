<?php
$success = false;
if(sizeof($_POST)){
	$post = _post($_POST);
	extract($post);	
	
	if(isset($action) && $action == 'delete' && isset($hidDeleteId) && $hidDeleteId){
		# DELETE	
		if( db_delete('post', array('postId' => $hidDeleteId)) ){
			$success = true;
		}	
	}
	if($success){
		Form::set('success', true);
		Form::set('callback', 'Page.Article.List.list()'); # Ajax callback
	}	
}
Form::respond('frmNews'); # Ajax response