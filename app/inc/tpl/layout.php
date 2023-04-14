<!DOCTYPE html>
<html>
<head>
    <title><?php echo _title(); ?></title>
    <link rel="canonical" href="<?php echo _canonical(); ?>" />
    <?php _hreflang(); ?>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php _metaSeoTags(); ?>
    <link rel="shortcut icon" href="<?php echo _img('favicon.ico'); ?>" type="image/x-icon" />
    <?php _css('base.css'); ?>
    <link href="//fonts.googleapis.com/css?family=Padauk:400,700" rel="stylesheet">
    <?php _css('base.'._lang().'.css'); ?>
    <?php _css('responsive.css'); ?>
    <?php _css('jquery.ui'); ?>
    <?php _app('view')->headStyle() ?>
    <?php _js('jquery'); ?>
    <?php _js('jquery.ui'); ?>
    <?php _script(); ?>
    <?php _js('LC.js'); ?>
    <?php _app('view')->headScript() ?>
    <?php _js('app.js'); ?>
</head>
<body>
    <div id="wrapper">
        <div id="page-container">
            <div id="header">
                <div class="container clearfix">
                    <a href="<?php echo _url('home'); ?>" id="logo">
                        <img src="<?php echo _img('logo-blue.png'); ?>" alt="<?php echo _cfg('siteName'); ?>" />
                    </a>
                    <div id="language-switcher">
                        <?php
                        $languages = _cfg('languages');
                        foreach ($languages as $lcode => $lname) {
                            $class = (_lang() == $lcode) ? 'active' : '';
                            $url = _self(NULL, $lcode);
                            ?>
                            <a href="<?php echo $url ?>" class="<?php echo $class; ?>">
                                <span><?php _image('flags/'.$lcode.'.png', $lname); ?></span>
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div id="menu-bar">
                <div class="container clearfix">
                    <ul id="menu" class="clearfix">
                        <li>
                            <a href="<?php echo _url('home'); ?>" <?php if (_arg(0) == '' || _arg(0) == 'home') echo 'class="active"'; ?>><?php echo _t('Welcome'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('example/blog'); ?>" <?php if (in_array(_arg(1), array('blog'))) echo 'class="active"'; ?> title="AJAX List Example"><?php echo _t('Example 1'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('example/articles'); ?>" <?php if (_arg(1) == 'articles') echo 'class="active"'; ?> title="Ordinary List Example"><?php echo _t('Example 2'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('blog/2/custom-routing-to-a-page-including-a-form-example'); ?>" <?php if (_arg(1) == 'blog-page') echo 'class="active"'; ?> title="AJAX Form Example"><?php echo _t('Example 3'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('example/comment'); ?>" <?php if (_arg(1) == 'comment') echo 'class="active"'; ?> title="Generic Form Example"><?php echo _t('Example 4'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('example/file-uploader'); ?>" <?php if (_arg(1) == 'file-uploader') echo 'class="active"'; ?> title="Generic File Upload Example"><?php echo _t('Example 5'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo _url('example/asyn-file-uploader'); ?>" <?php if (_arg(1) == 'asyn-file-uploader') echo 'class="active"'; ?> title="AsynFileUploader Example"><?php echo _t('Example 6'); ?></a>
                        </li>
                        <li>
                            <a href="http://www.phplucidframe.com/downloads" target="_blank"><?php echo _t('Downloads'); ?></a>
                        </li>
                        <li>
                            <a href="https://github.com/phplucidframe/phplucidframe" target="_blank">GitHub</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div id="page">
                <div class="container">
                    <?php _app('view')->load() ?>
                </div> <!-- .container -->
            </div> <!-- #page -->
            <div id="footer">
                <div class="container">
                    <div id="copyright" class="clearfix">
                        <span id="left">&copy; <?php echo date('Y'); ?></span>
                        <span id="right"><?php echo _cfg('siteName'); ?></span>
                    </div>
                    <ul class="social-icons">
                        <li><a href="http://bit.ly/fbphplucidframe" class="fb" target="_blank">Facebook</a></li>
                        <li><a href="https://twitter.com/phplucidframe" class="tw" target="_blank">Twitter</a></li>
                    </ul>
                </div>
            </div>
        </div> <!-- #page-container -->
    </div> <!-- #wrapper -->
</body>
</html>
