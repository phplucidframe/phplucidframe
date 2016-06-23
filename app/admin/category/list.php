<?php
$get = _get($_GET);

# Count query for the pager
$rowCount = db_count('category')
    ->where()->condition('deleted', null)
    ->fetch();

# Prerequisite for the Pager
$pager = _pager()
    ->set('itemsPerPage', $lc_itemsPerPage)
    ->set('pageNumLimit', $lc_pageNumLimit)
    ->set('total', $rowCount)
    ->set('ajax', true)
    ->calculate();

# List query
$qb = db_select('category', 'c')
    ->where()->condition('deleted', null)
    ->orderBy('catName')
    ->limit($pager->get('offset'), $pager->get('itemsPerPage'));

if ($qb->getNumRows()) {
    $langs = _langs(_defaultLang());
    ?>
    <table cellpadding="0" cellspacing="0" border="0" class="list">
        <tr class="label">
            <td class="tableLeft" colspan="2"><?php echo _t('Actions'); ?></td>
            <td>
                <span>Name</span>
                <label class="lang">(<?php echo _langName(); ?>)</label>
            </td>
            <?php if ($langs) { ?>
                <?php foreach ($langs as $lcode => $lname) { ?>
                <td>
                    <span>Name</span>
                    <?php if (_langName($lcode)) { ?>
                    <label class="lang">(<?php echo _langName($lcode); ?>)</label>
                    <?php } ?>
                </td>
                <?php } ?>
            <?php } ?>
        </tr>
        <?php
        while ($row = $qb->fetchRow()) {
            $data = array(
                'catId'     => $row->catId,
                'catName'   => $row->catName,
            );
            # Get translations for other languages
            $i18n = (array) _getTranslationStrings($row, 'catName');
            $data = array_merge($data, $i18n);
            ?>
            <tr id="row-<?php echo $row->catId; ?>">
                <td class="tableLeft colAction">
                    <span class="row-data" style="display:none"><?php echo json_encode($data); ?></span>
                    <a href="javascript:" class="edit" title="Edit" onclick="LC.Page.Category.edit(<?php echo $row->catId; ?>)">
                        <span><?php echo _t('Edit'); ?></span>
                    </a>
                </td>
                <td class="colAction">
                    <a href="#" class="delete" title="Delete" onclick="LC.Page.Category.remove(<?php echo $row->catId; ?>)">
                        <span><?php echo _t('Delete'); ?></span>
                    </a>
                </td>
                <td class="colName">
                    <?php echo $row->catName; ?>
                </td>
                <?php if ($langs) { ?>
                    <?php foreach ($langs as $lcode => $lname) { ?>
                    <td class="colName <?php echo $lcode; ?>">
                        <?php
                        $lcode = _queryLang($lcode);
                        if (isset($i18n['catName_i18n'][$lcode])) echo $i18n['catName_i18n'][$lcode];
                        else echo '&nbsp;';
                        ?>
                    </td>
                    <?php } ?>
                <?php } ?>
            </tr>
        <?php
        }
        ?>
    </table>
    <div class="pager-container"><?php echo $pager->display(); ?></div>
<?php
} else {
?>
    <div class="no-record"><?php echo _t('There is no item found. Click "Add New Category" to add a new category.'); ?></div>
<?php
}
