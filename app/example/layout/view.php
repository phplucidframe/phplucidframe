<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<h3><?php echo _t($title); ?></h3>
<p>Some developers may not want to have header and footer templates separately and they may not want to include the files in all views. They just want a layout file. This example shows how you can easily enable and use a layout file in PHPLucidFrame.</p>
<ul>
    <li>This page is located in <code class="inline"><?php echo $path ?></code>.</li>
    <li>This view is called by <code class="inline">/app/inc/tpl/layout.php</code>.</li>
    <li>If there is <code class="inline">/app/example/layout/query.php</code>, it is automatically included or all data retrieval process can also be written in <code class="inline">/app/example/layout/index.php</code> without <code class="inline">query.php</code>.</li>
    <li>Layout mode can be enabled by <code class="inline">_cfg('layoutMode', true)</code> for a particular page or by setting <code class="inline">$lc_layoutMode = true</code> in <code class="inline">/inc/config.php</code> for all pages.</li>
    <li>A particular layout file name can be given like <code class="inline">_cfg('layoutName', 'mobile')</code> for a page.</li>
</ul>
<p>
    <h6><?php echo _t('Leave a Comment.'); ?></h6>
    <div class="fluid-50">
        <form id="frmComment" method="post">
            <div class="message"></div>
            <table cellspacing="0" class="form fluid">
                <tr>
                    <td class="label"><?php echo _t('Name')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtName" class="fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Email')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtEmail" class="fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Re-type Email')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <input type="text" name="txtConfirmEmail" class="fluid-100" />
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Comment')._cfg('reqSign'); ?></td>
                    <td class="labelSeparator">:</td>
                    <td class="entry">
                        <textarea name="txaComment" rows="7" class="fluid-100"></textarea>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="entry">
                        <input type="submit" name="btnSubmit" value="<?php echo _t('Post Comment'); ?>" class="button green" />
                    </td>
                </tr>
            </table>
            <?php form_token(); ?>
        </form>
    </div>
</p>
