// JavaScript Document
LC.Page.init = function() { /* just example */
	LC.Page.Language.init();
	LC.Page.Sidebar.init();
}

LC.Page.Language = { /* just example */
	init : function() {
	}
}

LC.Page.Sidebar = { /* just example */
	init : function() {
	}
}

LC.Page.Home = { /* just example */
	url : LC.Page.url('home'), /* mapping directory */
	/* Initialization of home page */
	init : function() {
		console.log('This is home page.');
		console.log(LC.Page.Home.url);
	}
}

LC.Page.Blog = {
	url : LC.Page.url('example/blog'), /* mapping directory */
	/* Initialization of the blog page */
	init : function() {
		LC.Page.Blog.list();
	},
	list : function() {
		LC.Page.request( 'list', LC.Page.Blog.url + 'list.php' );
	}
}

$(function() {
	LC.Page.init();
});
