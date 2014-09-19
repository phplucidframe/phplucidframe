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

Page.Home = { /* just example */
	url : Page.url('home'), /* mapping directory */
	/* Initialization of home page */
	init : function(){
		console.log('This is home page.');
		console.log(Page.Home.url);
	}
}

Page.Blog = {
	url : Page.url('blog'), /* mapping directory */
	/* Initialization of home page */
	init : function(){
		Page.Blog.list();
	},
	list : function(){
		Page.request( 'list', Page.Blog.url + 'list.php' );
	}
}

$(function(){
	Page.init();
});