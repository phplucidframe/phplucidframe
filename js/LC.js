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
		Form.placeholderIE();		
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
				// if the form has no type=submit button and the form has a class "default-submit", make form submit when pressing "Enter" in any textbox
				if( $form.find('[type=submit]').size() == 0 && $form.hasClass('default-submit') ){	
					$form.find('input[type=text],input[type=password]').keyup(function(e){
						if(e.keyCode == 13) $form.submit();
					});
				}				
				$.each( btns, function(){ // buttons that have the class "submit"
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
	/* IE placeholder attribute fix */
	placeholderIE : function(){
		if($.browser.msie && $.browser.version <= 8.0 ){
			$inputs = $('[placeholder]');
			$inputs.focus(function() {
				var input = $(this);
				if (input.val() == input.attr('placeholder')) {
					input.val('');
					input.removeClass('placeholder');
				}
			});
			
			$inputs.blur(function() {
				var input = $(this);
				if (input.val() == '' || input.val() == input.attr('placeholder')) {
					input.addClass('placeholder');
					input.val(input.attr('placeholder'));
				}
			}).blur();
			
			$inputs.parents('form').submit(function() {
				$(this).find('[placeholder]').each(function() {
					var input = $(this);
					if (input.val() == input.attr('placeholder')) {
						input.val('');
					}
				})
			}).addClass('no-focus'); // no focus on the first element of the form.			
		}		
	},
	submitForm : function(formId, e){
		var $form = $('#'+formId);
		var $message = $form.find('.message').filter(':first');
		$message.html('').hide();
		$form.find('.message.success').filter(':first').removeClass('success').addClass('error');				
		$form.find('.invalid').removeClass('invalid');
		
		var $action = $form.attr('action');
		if(!$action){
			$form.attr('action', LC.self + '/action.php');
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
		if(typeof response == 'object'){
			var $form 	 = $('#'+response.formId);
			var $message = $form.find('.message').filter(':first');
			if(response.error){
				var errHtml = '<ul>';
				$.each( response.error, function(i, err){ 			
					if(err.htmlID){ 
						if(err.htmlID.indexOf('[]') != -1){
							err.htmlID = err.htmlID.replace(/\[\]/, '');
							$form.find('#'+err.htmlID).find('input,select,textarea').addClass('invalid');
						}else{
							if($('#'+err.htmlID).size()) $form.find('#' + err.htmlID).addClass('invalid');
							else $form.find('input[name='+err.htmlID+'],textarea[name='+err.htmlID+'],select[name='+err.htmlID+']').addClass('invalid');
						}
					}
					errHtml += '<li>' + err.msg + '</li>';
				} );
				errHtml += '</ul>';			
				$message.html(errHtml).show();
				$message.removeClass('error').addClass('error');
				if( $message.find('ul').html() == '' ) $('#form_error ul').remove(); 
				window.location = '#' + response.formId;
			}else{
				if(response.success){
					if(response.msg){ 
						$message.removeClass('error').addClass('success');
						$message.html('<ul><li>'+response.msg+'</li></ul>').show();					
					}								
					if(response.redirect) window.location = response.redirect;
					else{ 
						$form.find('.reset').click();
						window.location = '#' + response.formId;
					}
				}
			}
			if(response.callback) eval(response.callback);
			Page.progress.stop(response.formId);			
		}else{
			Page.progress.stop();
		}
	},
	clear : function( formId ){
		var $form = $('#'+formId);
		$form.find('.invalid').removeClass('invalid');
		$form.find('select,textarea').val('');
		var $inputs = $form.find('input').filter('input:not([name^=lc_formToken])');
		$inputs.val('');
		$form.find('.message').filter(':first').html('').hide();		
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
	/* Path to the site root including the language code (if multi-langual site) */
	root : (LC.lang) ? LC.root + LC.lang + '/' : LC.root,
	/* Throbber when doing AJAX requests */
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
	/* 
	 * Initialize the page 
	 */
	initialize : function(){
		// 	overlay and progress message create
		$overlay = $('body').prepend('<div id="page-loading" />').children(':first').hide();
		$loading = $overlay.append('<div />').children(':last').attr('id', 'processing');
		$div = $loading.append('<div />').children(':last');
		$div.append('<span />').children(':last').html('Processing, please wait...').attr('id', 'line1');
		
		$overlay.width($(window).width());
		$overlay.height($(window).height());
				
		Page.scroller();
		Form.init();
		Page.showGlobalMessage();
	},
	/* 
	 * Display side-wide global message (if any)
	 */	
	showGlobalMessage : function(){
		var html = '';
		if(LC.sitewideWarnings && LC.sitewideWarnings.length){			
			$.each(LC.sitewideWarnings, function(i, msg){
				html = '<div class="message sitewide-message warning" title="Click to dismiss">';
				html += '<ul>';
				html += '<li>' + msg + '</li>';
				html += '</ul>';
				html += '</div>';
				$('body').prepend(html);				
			});	
			$('.message.sitewide-message.warning').slideDown().click(function(){
				$(this).hide();
			});;
		}
	},	
	/* 
	 * Language switcher callback
	 * @param string lng The language code to be switched
	 */		
	languageSwitcher : function(lng){
		var $lang = LC.lang + '/';
		var $path = window.location.pathname;
		if($path.indexOf('/') == 0){ // remove leading slash
			$path = $path.substr(1);	
		}
		
		// remove baseURL from the URI
		var baseURL = LC.baseURL;
		if(LC.baseURL == '/') baseURL = '';
		var regexp = new RegExp(baseURL);
		$path = $path.replace(regexp, ''); 
		
		// replace language code in URI
		if($path.indexOf($lang) == 0){
			var regexp = new RegExp($lang); 
			$path = $path.replace(regexp, lng + '/');	
		}else{
			$path = lng + '/' + $path;
		}
					
		// new URL
		$url = window.location.protocol + '//' + window.location.host + '/' + baseURL + $path;
		window.location = $url;		
	},
	/**
	 * Performs a smooth page scroll to an anchor on the same page.
	 */	
	scroller : function(){
		$('a[href*=#]:not([href=#])').click(function() {
			if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
				var target = $(this.hash);
				target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
				if (target.length) {
					$('html,body').animate({
					  scrollTop: target.offset().top
					}, 1000);
					return false;
				}
			}
		});		
	},
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
	Page.initialize();
} );