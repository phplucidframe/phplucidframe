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
                <div id="language-switcher">
				<?php
				$languages = _cfg('languages');
				foreach($languages as $lcode => $lname){
					$class = (_lang() == $lcode) ? 'active' : '';
					$url = _self(NULL, $lcode);
                ?>
                    <a href="<?php echo $url ?>" class="<?php echo $class; ?>">
                    	<span><img src="<?php echo _img('flags/'.$lcode.'.png'); ?>" alt="<?php echo $lname; ?>" title="<?php echo $lname; ?>" /></span>
                    </a>
                <?php
                }
                ?>
                </div>
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
						<a href="https://github.com/cithukyaw/LucidFrame/releases" target="_blank">Download</a>
					</li>
                    <li>
						<a href="https://github.com/cithukyaw/LucidFrame" target="_blank">GitHub</a>
					</li>
                </ul>
            </div>
        </div>
        <div id="page">
        	<div class="container">