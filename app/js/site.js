// JavaScript Document
Page.init = function(){ /* just example */
	Page.Language.init();
	Page.Sidebar.init();	
}

Page.Language = { /* just example */
	init : function(){
	}
}

Page.Sidebar = { /* just example */
	init : function(){
	}
}

Page.Home = function(){ /* just example */
	/* mapping directory */
	url : WEB_ROOT + 'home/',
	/* Initialize the home page */	
	init : function(){
	}
}

$(function(){
	Page.init();
});