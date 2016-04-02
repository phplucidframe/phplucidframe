LC.Page.Post = {
    Setup : {
        url : LC.Page.url('admin/post/setup'), /* mapping directory */
        /* Initialize the page */
        init : function() {
        }
    },
    List : {
        url : LC.Page.url('admin/post/list'), /* mapping directory */
        queryStr : {},
        /* Initialize the page */
        init : function(lang) {
            LC.Page.Post.List.list(lang);
            /* delete confirmation */
            $('#dialog-confirm').dialog({
                modal: true,
                autoOpen: false,
                resizable: false,
                height: 133,
                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                        LC.Page.Post.List.doDelete();
                    },
                    Cancel: function() {
                        $(this).dialog('close');
                    }
                }
            });

            $('#btnNew').click(function() {
                window.location = LC.Page.url('admin/post/setup');
            });
        },
        remove : function( id ) {
            $('#hidDeleteId').val( id );
            $('#dialog-confirm').dialog( 'open' );
        },
        /* Do delete action upon confirm OK */
        doDelete : function() {
            LC.Page.request('POST', // type
                LC.Page.Post.List.url + 'action.php', // page to post
                { // data to post
                    hidDeleteId: $('#hidDeleteId').val(),
                    action: 'delete'
                },
                function() { // callback
                    LC.Page.Post.List.list();
                }
            );
        },
        list : function(lang) {
            LC.Page.Post.List.queryStr = { lang: lang };
            LC.Page.request( 'list', LC.Page.Post.List.url + 'list.php', LC.Page.Post.List.queryStr );
        }
    }
};

LC.Page.Category = {
    url : LC.Page.url('admin/category'), /* mapping directory */
    /* Initialize the Category page */
    init : function() {
        /* delete confirmation */
        $('#dialog-confirm').dialog({
            modal: true,
            autoOpen: false,
            resizable: false,
            height: 133,
            buttons: {
                OK: function() {
                    $(this).dialog('close');
                    LC.Page.Category.doDelete();
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }
        });
        /* Add/Edit area */
        $('#dialog-category').dialog({
            modal: true,
            autoOpen: false,
            resizable: false,
            width: 390,
            minHeight: 120
        });

        $('#btnNew').click(function() {
            LC.Page.Category.create();
        });

        /* Load list */
        LC.Page.Category.list();
    },
    /* Load the list */
    list : function(param) {
        $('#dialog-category').dialog( 'close' );
        LC.Page.request( 'list', LC.Page.Category.url + 'list.php', param );
    },
    /* Launch the dialog to create a new entry */
    create : function() {
        LC.Form.clear('frmCategory');
        $('#dialog-category').dialog( 'open' );
    },
    /* Launch the dialog to edit an existing entry */
    edit : function( id ) {
        LC.Form.clear('frmCategory');
        var $data = LC.Form.data( id );
        if ($data) {
            var $form = $('#frmCategory');
            $form.find('#hidEditId').val( id );
            $form.find('input[name=txtName]').val($data.catName);
            // load data for the other translation text boxes
            if (typeof $data.catName_i18n !== 'undefined') {
                for (var c in $data.catName_i18n) {
                    if ($data.catName_i18n.hasOwnProperty(c)) {
                        $form.find('input[name=txtName_'+c+']').val($data.catName_i18n[c]);
                    }
                }
            }
            $('#dialog-category').dialog( 'open' );
        }
    },
    /* Launch the dialog to confirm an entry delete */
    remove : function( id ) {
        $('#hidDeleteId').val( id );
        $('#dialog-confirm').dialog( 'open' );
    },
    /* Do delete action upon confirm OK */
    doDelete : function() {
        LC.Page.request('POST', // type
            LC.Page.Category.url + 'action.php', // page to post
            { // data to post
                hidDeleteId: $('#hidDeleteId').val(),
                action: 'delete'
            },
            function() { // callback
                LC.Page.Category.list();
            }
        );
    }
};

LC.Page.User = {
    Setup : {
        url : LC.Page.url('admin/user/setup'), /* mapping directory */
        /* Initialize the page */
        init : function() {
            $('#btnCancel').click(function() {
                window.location = LC.Page.url('admin/user/list');
            });
        }
    },
    List : {
        url : LC.Page.url('admin/user/list'), /* mapping directory */
        /* Initialize the page */
        init : function() {
            /* delete confirmation */
            $('#dialog-confirm').dialog({
                modal: true,
                autoOpen: false,
                resizable: false,
                height: 133,
                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                        LC.Page.User.List.doDelete();
                    },
                    Cancel: function() {
                        $(this).dialog('close');
                    }
                }
            });
            /* Add/Edit  */
            $('#dialog-warning').dialog({
                modal: true,
                autoOpen: false,
                resizable: false,
                width: 350,
                minHeight: 90,
                buttons: {
                    OK: function() {
                        $(this).dialog('close');
                    }
                }
            });
            $('#btnNew').click(function() {
                window.location = LC.Page.url('admin/user/setup');
            });
            /* Load list */
            LC.Page.User.List.list();
        },
        /* Load the list */
        list : function(param) {
            $('#dialog-confirm').dialog( 'close' );
            $('#dialog-warning').dialog( 'close' );
            LC.Page.request( 'list', LC.Page.User.List.url + 'list.php', param );
        },
        /* Launch the dialog to confirm an entry delete */
        remove : function( id ) {
            $('#hidDeleteId').val( id );
            $('#dialog-confirm').dialog( 'open' );
        },
        /* Launch the dialog to confirm an entry delete */
        warning : function() {
            $('#dialog-warning').dialog( 'open' );
        },
        /* Do delete action upon confirm OK */
        doDelete : function() {
            LC.Page.request('POST', // type
                LC.Page.User.List.url + 'action.php', // page to post
                { // data to post
                    hidDeleteId: $('#hidDeleteId').val(),
                    action: 'delete'
                },
                function() { // callback
                    LC.Page.User.List.list();
                }
            );
        }
    }
};
