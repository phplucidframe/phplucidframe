Page.Post = {
	Setup : {
		url : Page.url('admin/post/setup'), /* mapping directory */
		/* Initialize the page */
		init : function(mode){
		}
	},
	List : {
		url : Page.url('admin/post/list'), /* mapping directory */
		queryStr : {},
		/* Initialize the page */
		init : function(lang){
			//Page.Post.List.queryStr.lang = LC.lang;
			Page.Post.List.list(lang);
			/* delete confirmation */
			$( "#dialog-confirm" ).dialog({
				modal: true,
				autoOpen: false,
				resizable: false,
				height: 133,
				buttons: {
					OK: function() {
						$(this).dialog( "close" );
						Page.Post.List.doDelete();
					},
					Cancel: function(){
						$(this).dialog( "close" );
					}
				}
			});

			$( "#btnNew" ).click(function(){
				window.location = Page.url('admin/post/setup');
			});
		},
		remove : function( id ){
			$('#hidDeleteId').val( id );
			$('#dialog-confirm').dialog( 'open' );
		},
		/* Do delete action upon confirm OK */
		doDelete : function(){
			Page.progress.start();
			$.ajax({
				type: "POST",
				url: Page.Post.List.url + 'action.php',
				dataType: 'script',
				data: {
					hidDeleteId: $('#hidDeleteId').val(),
					action: 'delete'
				},
				success: Form.submitHandler
			});
		},
		list : function(lang){
			Page.Post.List.queryStr = { lang: lang }
			Page.request( 'list', Page.Post.List.url + 'list.php', Page.Post.List.queryStr );
		}
	}
}

Page.Category = {
	url : Page.url('admin/category'), /* mapping directory */
	/* Initialize the Category page */
	init : function(){
		/* Load list */
		Page.Category.list();
		/* delete confirmation */
		$( "#dialog-confirm" ).dialog({
			modal: true,
			autoOpen: false,
			resizable: false,
			height: 133,
			buttons: {
				OK: function() {
					$(this).dialog( "close" );
					Page.Category.doDelete();
				},
				Cancel: function(){
					$(this).dialog( "close" );
				}
			}
		});
		/* Add/Edit area */
		$( "#dialog-category" ).dialog({
			modal: true,
			autoOpen: false,
			resizable: false,
			width: 390,
			minHeight: 120
		});

		$( "#btnNew" ).click(function(){
			Page.Category.create();
		});
	},
	/* Load the list */
	list : function(param){
		$('#dialog-category').dialog( 'close' );
		Page.request( 'list', Page.Category.url + 'list.php', param );
	},
	/* Launch the dialog to create a new entry */
	create : function(){
		Form.clear('frmCategory');
		$('#dialog-category').dialog( 'open' );
	},
	/* Launch the dialog to edit an existing entry */
	edit : function( id ){
		Form.clear('frmCategory');
		var $data = Form.data( id );
		if($data){
			var $form = $('#frmCategory');
			$form.find('#hidEditId').val( id );
			$form.find('input[name=txtName]').val($data.catName);
			// load data for the other translation text boxes
			if(typeof $data.catName_i18n != 'undefined'){
				for(c in $data.catName_i18n){
					$form.find('input[name=txtName_'+c+']').val($data.catName_i18n[c]);
				}
			}
			$('#dialog-category').dialog( 'open' );
		}
	},
	/* Launch the dialog to confirm an entry delete */
	remove : function( id ){
		$('#hidDeleteId').val( id );
		$('#dialog-confirm').dialog( 'open' );
	},
	/* Do delete action upon confirm OK */
	doDelete : function(){
		Page.progress.start();
		$.ajax({
			type: "POST",
			url: Page.Category.url + 'action.php',
			dataType: 'script',
			data: {
				hidDeleteId: $('#hidDeleteId').val(),
				action: 'delete'
			},
			success: Form.submitHandler
		});
	}
};

Page.User = {
	Setup : {
		url : Page.url('admin/user/setup'), /* mapping directory */
		/* Initialize the page */
		init : function(){
			$( "#btnCancel" ).click(function(){
				window.location = Page.url('admin/user/list');
			});
		}
	},
	List : {
		url : Page.url('admin/user/list'), /* mapping directory */
		/* Initialize the page */
		init : function(){
			/* Load list */
			Page.User.List.list();
			/* delete confirmation */
			$( "#dialog-confirm" ).dialog({
				modal: true,
				autoOpen: false,
				resizable: false,
				height: 133,
				buttons: {
					OK: function() {
						$(this).dialog( "close" );
						Page.User.List.doDelete();
					},
					Cancel: function(){
						$(this).dialog( "close" );
					}
				}
			});
			/* Add/Edit  */
			$( "#dialog-warning" ).dialog({
				modal: true,
				autoOpen: false,
				resizable: false,
				width: 350,
				minHeight: 90,
				buttons: {
					OK: function() {
						$(this).dialog( "close" );
					}
				}
			});
			$( "#btnNew" ).click(function(){
				window.location = Page.url('admin/user/setup');
			});
		},
		/* Load the list */
		list : function(param){
			$('#dialog-area').dialog( 'close' );
			Page.request( 'list', Page.User.List.url + 'list.php', param );
		},
		/* Launch the dialog to confirm an entry delete */
		remove : function( id ){
			$('#hidDeleteId').val( id );
			$('#dialog-confirm').dialog( 'open' );
		},
		/* Launch the dialog to confirm an entry delete */
		warning : function(){
			$('#dialog-warning').dialog( 'open' );
		},
		/* Do delete action upon confirm OK */
		doDelete : function(){
			Page.progress.start();
			$.ajax({
				type: "POST",
				url: Page.User.List.url + 'action.php',
				dataType: 'script',
				data: {
					hidDeleteId: $('#hidDeleteId').val(),
					action: 'delete'
				},
				success: Form.submitHandler
			});
		}
	}
}