<?php
/**
 * Header template file
 */
?>
<div id="wrapper">
    <div id="page-container">
        <div id="header">
            <div class="container clearfix">
                <a href="<?php echo _url('home'); ?>" id="logo">PHPLucidFrame</a>
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
                        <a href="<?php echo _url('example/layout'); ?>" <?php if (_arg(1) == 'layout') echo 'class="active"'; ?> title="Layout Mode Example"><?php echo _t('Example 7'); ?></a>
                    </li>
                    <li>
                        <a href="http://phplucidframe.github.io/downloads" target="_blank"><?php echo _t('Downloads'); ?></a>
                    </li>
                    <li>
                        <a href="https://github.com/phplucidframe/phplucidframe" target="_blank">GitHub</a>
                    </li>
                </ul>
            </div>
        </div>
        <div id="page">
            <div class="container">
