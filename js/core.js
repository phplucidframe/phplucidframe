/**
 * Javascript
 *
 * LucidFrame : Simple & Flexible PHP Development
 * Copyright (c), LucidFrame.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @package     LC.js 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
var Form = {
	init: function(){
		$forms = $('form');
		$.each( $forms, function(){
			var $form = $(this);			
			if($form.hasClass('no-ajax')) return; // normal form submission			
			// Add a hidden input and a reset button
			$form.append('<input type="hidden" name="submitButton" class="submitButton" />');
			$form.append('<input type="reset" class="reset" style="display:none;width:0;height:0" />');			
			// submit buttons: class="submit"
			var btns = $form.find('.submit');
			if(btns.size()){
				// if the form has no type=submit button, make form submit when pressing "Enter" in any textbox
				if( $form.find('[type=submit]').size() == 0 ){	
					$form.find('input[type=text],input[type=password]').keyup(function(e){
						if(e.keyCode == 13) $form.submit();
					});
				}				
				$.each( btns, function(){ // buttons that has a class "submit"
					// The submit button click
					$(this).bind('click', function(){
						$form.find('input.submitButton').val($(this).attr('name'));
						if( $(this).attr('type') == 'button' ){							
							$form.submit();
						}
					});
				});		
			}
			// submit buttons: type="submit"
			$.each( $form.find('[type=submit]'), function(){
				$(this).bind('click', function(){
					$form.find('input.submitButton').val($(this).attr('name'));
				});
			} );
			
			// form submit handler init
			$form.submit( function(e){			   
				Form.submitForm($form.attr('id'), e);							
				return false;		
			} );

			if( !$form.hasClass('no-focus') ){ // sometimes a page is long and the form is at the bottom of the page, no need to focus it.
				// focus on the first input
				if($form.find('input[type=text]').size()) $form.find('input[type=text]').filter(':first').focus();	
				else if($form.find('textarea').size()) $form.find('textarea').filter(':first').focus();	
			}
		});
		// jquery ui button theme
		$('button.jqbutton').button();
		// datepicker initialize
		$( ".datepicker" ).datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: 'dd-mm-yy'
		});			
	},
	submitForm : function(formId, e){
		var $form = $('#'+formId);			
		$form.find('.message').html('').hide();
		$form.find('.message.success').removeClass('success').addClass('error');
		$form.find('.invalid').removeClass('invalid');
		
		var $action = $form.attr('action');
		if(!$action){
			$form.attr('action', WEB_SELF + '/action.php');
		}
					
		if( $form.find('input[type=file]').size() ){
			Page.progress.start();
			eval('document.' + formId + '.submit()');
			return true;
		}
				
		var url = $form.attr('action'); // which URL to be posted; captured from form action attribute		
		var values 	= $form.serialize(); // encode a set of form elements as a string for submission	
		
		$.ajax({
			type: "POST",
			url: url,
			dataType: 'script',
			data: values,
			success: Form.submitHandler
		});
		Page.progress.start(formId);
	},
	submitHandler : function(){
		if(arguments.length == 1) response = arguments[0];
		var $form = $('#'+response.formId);
		if(response.error){
			var errHtml = '<ul>';
			$.each( response.error, function(i, err){ 			
				if(err.htmlID){ 
					if($('#'+err.htmlID).size()) $('#' + err.htmlID).addClass('invalid');
					else $('input[name='+err.htmlID+']').addClass('invalid');
				}
				errHtml += '<li>' + err.msg + '</li>';
			} );
			errHtml += '</ul>';
			$form.find('.message').html(errHtml).show();
			if( $form.find('.message ul').html() == '' ) $('#form_error ul').remove(); 
			window.location = '#' + response.formId;
		}else{
			if(response.success){
				if(response.msg){ 
					$form.find('.message').removeClass('error').addClass('success');
					$form.find('.message').html('<ul><li>'+response.msg+'</li></ul>').show();
					$form.find('.reset').click();
				}
				if(response.callback) eval(response.callback);
				if(response.redirect) window.location = response.redirect;
			}
		}
		Page.progress.stop(response.formId);
	},
	clear : function( formId ){
		var $form = $('#'+formId);
		$form.find('.invalid').removeClass('invalid');
		$form.find('input,select,textarea').val('');
		$form.find('.message').html('').hide();		
	},
	data : function( id ){
		$data = $( '#row-'+id ).find('.colAction span.row-data');
		if($data.size()){
			eval('var $row = ' + $( '#row-'+id ).find('.colAction span.row-data').text() );	
			return $row;
		}
		return false;
	}
};

var Page = {
	progress : {
		start : function(id){
			if(id){
				if(typeof Page.throbber[id] != 'undefined' && typeof Page.throbber[id].start == 'function') Page.throbber[id].start();
				else $('#page-loading').show();
			}else{
				$('#page-loading').show();
			}
		},
		stop : function(id){
			if(id){
				if(typeof Page.throbber[id] != 'undefined' && typeof Page.throbber[id].stop == 'function') Page.throbber[id].stop();
				else $('#page-loading').hide();
			}else{
				$('#page-loading').hide();
			}
		}
	},
	throbber : {
		/**
		 * Register a custom throbber
		 * @param id		(string) HTML container ID for the request
		 * @param callback  (object) The callback must be a functional object like { start: function(){}, stop: function(){} }
		*/		
		register : function(id, callback){
			Page.throbber[id] = callback;
		}
	},
	queryStr : {},
	/**
	 * Get the updated query string
	 */
	getQueryStr : function(id, key, value){
		if(typeof Page.queryStr['_'+id] != 'undefined' && key){
			eval("Page.queryStr._"+id+"."+key+" = value;");
			return Page.queryStr['_'+id];
		}
		return {};
	},
	/**
	 * Ajax request helper
	 * @param id	(string) HTML container ID or GET/POST for no HTML response
	 * @param url	(strong) URL to request
	 * @param param (object) optional literal parameters
	 * @param callback (function) optional callback function to execute
	*/
	request : function(id, url, params, callback){
		Page.progress.start(id);
		var p = {};
		if(typeof params != 'undefined'){
			p = params;
		}
		
		var $type = 'GET';
		var $html = true;
		if(id.toUpperCase() == 'POST' || id.toUpperCase() == 'GET'){
			$type = id;
			$html = false;
		}else{
			Page.queryStr['_'+id] = p;
		}

		$.ajax({
			type: $type,
			url: url,
			data: p,
			cache: false,
			success: function(response){ 
				if(typeof callback != 'undefined'){
					callback();
				}else{
					if($html == true){ // the response may contain both HTML and script
						var $rsp = response.split('[script]');
						var html = $rsp[0];
						if(html) $('#'+id).html(html);
						if( $rsp.length > 1 ){ 
							var $js = $rsp[1];
							eval($js);
						}
						// pager init
						Page.pager(id);
					}else{ // The response contains only script
						eval(response);
					}
					// afterRequest callback
					if(Page.afterRequest) Page.afterRequest();					
				}
				// hide overlay
				Page.progress.stop(id);
			}
		});
	},
	/**
	 * Pager helper
	 * @param id	(string) HTML container ID for the list to be paginated
	*/	
	pager : function(id){
		var $pager = $('#'+id).find('.pagerTable a');
		if($pager.size()){
			$.each($pager, function(i, a){
				if($(a).attr('rel')){ // ajax pager
					var $url = $(a).attr('href');
					var $page = $(a).attr('rel');
					$(a).attr('href', '#').click(function(){
						// attach with the existing query string
						//if(typeof Page.queryStr['_'+id].page != 'undefined') 
						Page.queryStr['_'+id].page = $page;
						//else Page.queryStr.page = $page;
						Page.request(id, $url, Page.queryStr['_'+id]);
					});
				}
			});
		}
	}
};

$(document).ready( function(){
	// 	overlay and progress message create
	$overlay = $('body').prepend('<div id="page-loading" />').children(':first').hide();
	$loading = $overlay.append('<div />').children(':last').attr('id', 'processing');
	$div = $loading.append('<div />').children(':last');
	$div.append('<span />').children(':last').html('Processing, please wait...').attr('id', 'line1');
	
	$overlay.width($(window).width());
	$overlay.height($(window).height());
	
	Form.init();
} );