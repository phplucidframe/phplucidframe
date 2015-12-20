<?php include( _i('inc/tpl/header.php') ); ?>
<div class="block full-width">
    <h3><?php echo _t($pageTitle); ?></h3>
    <div class="content-box">
        <form method="post" name="frmUser" id="frmUser" action="<?php echo _url('admin/user/setup/action.php'); ?>">
            <input type="hidden" name="hidEditId" id="hidEditId" value="<?php echo $id; ?>" />
            <div class="message error"></div>
            <table cellpadding="0" cellspacing="0" class="form">
                <tr>
                    <td class="label"><?php echo _t('Full Name').$lc_reqSign; ?></td>
                    <td class="labelSeperator">:</td>
                    <td class="entry"><input type="text" name="txtFullName" id="txtFullName" value="<?php echo $user->fullName; ?>" size="30" /></td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Username').$lc_reqSign; ?></td>
                    <td class="labelSeperator">:</td>
                    <td class="entry"><input type="text" name="txtUsername" id="txtUsername" value="<?php echo $user->username; ?>" size="30" /></td>
                </tr>
                <?php if (!$id): ?>
                    <tr class="tdPassword">
                        <td class="label"><?php echo _t('Password').$lc_reqSign; ?></td>
                        <td class="labelSeperator">:</td>
                        <td class="entry">
                            <input type="password" name="txtPwd" id="txtPwd" size="30" />
                        </td>
                    </tr>
                    <tr class="tdPassword">
                        <td class="label"><?php echo _t('Confirm Password').$lc_reqSign; ?></td>
                        <td class="labelSeperator">:</td>
                        <td class="entry">
                            <input type="password" name="txtConfirmPwd" id="txtConfirmPwd" size="30" />
                        </td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td class="label"><?php echo _t('Email').$lc_reqSign; ?></td>
                    <td class="labelSeperator">:</td>
                    <td class="entry"><input type="text" name="txtEmail" id="txtEmail" value="<?php echo $user->email ?>" size="50" /></td>
                </tr>
                <tr>
                    <td class="label"><?php echo _t('Role').$lc_reqSign; ?></td>
                    <td class="labelSeperator">:</td>
                    <td class="entry">
                        <select name="cboRole">
                            <option value="editor" <?php if ($user->role == 'editor') echo 'selected="selected"'; ?>>Editor</option>
                            <option value="admin" <?php if ($user->role == 'admin') echo 'selected="selected"'; ?>>Administrator</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <td class="entry">
                        <button type="submit" class="button green" id="btnSave" name="btnSave"><?php echo _t('Save'); ?></button>
                        <button type="button" class="button" id="btnCancel" name="btnCancel"><?php echo _t('Cancel'); ?></button>
                    </td>
                </tr>
            </table>
            <?php form_token(); ?>
        </form>
    </div>
    <div id="block-foot"></div>
</div>
<?php include( _i('inc/tpl/footer.php') ); ?>
