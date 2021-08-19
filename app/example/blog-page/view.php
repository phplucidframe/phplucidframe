<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by index.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<h3><?php echo $blog->title; ?></h3>
<p>
    If you need your own custom routes, you can easily define them in <code class="inline">/inc/route.config.php</code>. The following example shows the route key <code class="inline">lc_blog_show</code> of the route path <code class="inline">/blog/{id}/{slug}</code> mapping to <code class="inline">/app/example/blog-page/index.php</code> by passing two arguments <code class="inline">id</code> and <code class="inline">slug</code> with the requirements of 'id' to be digits and 'slug' to be alphabets/dashes/underscores.
    <code>
    // inc/route.config.php<br>
    route('lc_blog_show')->map('/blog/{id}/{slug}', '/example/blog-page', 'GET', array(</br>
    &nbsp;&nbsp;&nbsp;&nbsp;'id'    => '\d+',<br>
    &nbsp;&nbsp;&nbsp;&nbsp;'slug'  => '[a-zA-Z\-_]+'<br>
    ));
    </code>
</p>
<p>
    It is equivalent to the following <code class="inline">.htaccess</code> Rewrite rule.
    <code>
    # ~/blog/99/foo-bar to ~/app/example/blog-page/?lang=~&id=99&slug=foo-bar<br>
    RewriteRule ^(([a-z]{2}|[a-z]{2}-[A-Z]{2})/)?blog/([0-9]+)/(.*)$ app/index.php?lang=$1&id=$3&slug=$4&route=example/blog-page [NC,L]
    </code>
</p>
<p>This page also shows AJAX form example below. You can check the form validation and handling in <code class="inline">/app/example/blog-page/action.php</code>.</p>
<p>
    <h6><?php echo _t('Leave a Comment.'); ?></h6>
    <div class="fluid-50">
        <form id="frmComment" method="post">
            <div class="message"></div>
            <table cellpadding="0" cellspacing="0" class="form fluid">
                <tr>
                    <td class="label"><?php echo _t('Name')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtName" class="lc-form-input fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Email')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtEmail" class="lc-form-input fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Re-type Email')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtConfirmEmail" class="lc-form-input fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Comment')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <textarea name="txaComment" rows="7" class="lc-form-input fluid-100"></textarea>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="entry">
                        <input type="submit" name="btnSubmit" value="<?php echo _t('Post Comment'); ?>" class="button green" />
                        <a href="<?php echo _url('example/blog'); ?>" class="button black"><?php echo _t('Cancel'); ?></a>
                    </td>
                </tr>
            </table>
            <?php form_token(); ?>
        </form>
    </div>
</p>
