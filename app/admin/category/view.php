<?php include( _i('inc/tpl/header.php') ); ?>
<h4><?php echo $pageTitle; ?></h4>
<div id="buttonZone">
    <button type="button" class="button mini green" id="btnNew"><?php echo _t('Add New Category'); ?></button>
</div>
<div id="list"></div>
<input type="hidden" id="hidDeleteId" value="" />
<!-- Confirm Delete Dialog -->
<div id="dialog-confirm" class="dialog" title="<?php echo _t('Confirm Category Delete'); ?>" style="display:none">
    <div class="msg-body"><?php echo _t('Are you sure you want to delete?'); ?></div>
</div>
<!-- Category Entry Form -->
<div id="dialog-category" class="dialog" title="<?php echo _t('Category'); ?>" style="display:none">
    <form method="post" id="frmCategory" action="<?php echo _url('admin/category/action.php'); ?>">
        <div class="message error"></div>
        <input type="hidden" id="hidEditId" name="hidEditId" />
        <table cellpadding="0" cellspacing="0" class="form">
            <tr>
                <td class="label">
                    <?php echo _t('Name'); ?>
                    <label class="lang">(<?php echo _langName(); ?>)</label>
                    <?php echo $lc_reqSign; ?>
                </td>
                <td class="labelSeperator">:</td>
                <td class="entry"><input type="text" name="txtName" id="txtName" size="30" /></td>
            </tr>
            <?php $langs = _langs(_defaultLang()); ?>
            <?php foreach ($langs as $lcode => $lname) { ?>
            <tr>
                <td class="label">
                    <?php echo _t('Name'); ?>
                    <?php if (_langName($lcode)) { ?>
                    <label class="lang">(<?php echo _langName($lcode); ?>)</label>
                    <?php } ?>
                </td>
                <?php $lcode = _queryLang($lcode); ?>
                <td class="labelSeperator">:</td>
                <td class="entry"><input type="text" name="txtName_<?php echo $lcode; ?>" id="txtName_<?php echo $lcode; ?>" size="30" /></td>
            </tr>
            <?php } ?>
            <tr>
                <td colspan="2">
                <td class="entry">
                    <button type="button" class="jqbutton submit" id="btnSave" name="btnSave"><?php echo _t('Save'); ?></button>
                    <button type="button" class="jqbutton" id="btnCancel" name="btnCancel" onclick="$('#dialog-category').dialog('close');"><?php echo _t('Cancel'); ?></button>
                </td>
            </tr>
        </table>
        <?php form_token(); ?>
    </form>
</div>
<?php include( _i('inc/tpl/footer.php') ); ?>
