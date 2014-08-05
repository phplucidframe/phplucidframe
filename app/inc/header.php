<?php
/**
 * Header template file 
 */
?>
<div id="wrapper">
	<div id="page-container">
        <div id="header">
        	<div class="container clearfix">
        		<a href="<?php echo _url('home'); ?>" id="logo">LucidFrame</a>
				<ul class="social-icons">
					<li><a href="https://facebook.com" class="fb" target="_blank">Facebook</a></li>		
					<li><a href="https://twitter.com" class="tw" target="_blank">Twitter</a></li>
				</ul>
                <ul id="menu" class="clearfix">
                	<li>
						<a href="<?php echo _url('home'); ?>" <?php if(_arg(0) == 'home') echo 'class="active"'; ?>><?php echo _t('Welcome'); ?></a>
					</li>
                    <li>
						<a href="<?php echo _url('blog'); ?>" <?php if(in_array(_arg(0), array('blog', 'blog-page'))) echo 'class="active"'; ?>><?php echo _t('Blog'); ?></a>
					</li>
                	<li>
						<a href="<?php echo _url('articles'); ?>" <?php if(_arg(0) == 'articles') echo 'class="active"'; ?>><?php echo _t('Articles'); ?></a>
					</li>					
                    <li>
						<a href="https://github.com/cithukyaw/LucidFrame" _target="_blank">GitHub</a>
					</li>
                </ul>
            </div>
        </div>
        <div id="page">
        	<div class="container">