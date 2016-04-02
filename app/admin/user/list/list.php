<?php
$get = _get($_GET);

$args = array();

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

$qb = db_select('user', 'u')
    ->where()->condition('deleted', null)
    ->orderBy('role')
    ->orderBy('fullName')
    ->limit($pager->get('offset'), $pager->get('itemsPerPage'));

if ($qb->getNumRows()) {
?>
    <table cellpadding="0" cellspacing="0" border="0" class="list users">
        <tr class="label">
            <td class="tableLeft" colspan="2"><?php echo _t('Actions'); ?></td>
            <td><?php echo _t('Full Name') ?></td>
            <td><?php echo _t('Username') ?></td>
            <td><?php echo _t('Email') ?></td>
            <td><?php echo _t('User Role') ?></td>
        </tr>
        <?php
        while ($row = $qb->fetchRow()) {
            // Edit & delete flag
            $edit = true;
            $delete = true;
            $action = '';
        ?>
            <tr id="row-<?php echo $row->usrId; ?>">
                <td class="tableLeft colAction">
                    <?php if ($row->isMaster) { ?>
                        <?php $delete = false; ?>
                        <?php $action = 'onclick="LC.Page.User.List.warning()"'; ?>
                    <?php } ?>
                    <?php if ($edit) { ?>
                        <a href="<?php echo _url('admin/user/setup',array($row->uid)); ?>" class="edit" title="Edit" >
                            <span><?php echo _t('Edit'); ?></span>
                        </a>
                    <?php } else { ?>
                        <span class="edit disabled"></span>
                    <?php } ?>
                </td>
                <td class="colAction">
                    <?php if ($delete): ?>
                        <a href="#" class="delete" title="Delete" onclick="LC.Page.User.List.remove(<?php echo $row->uid; ?>)">
                            <span><?php echo _t('Delete'); ?></span>
                        </a>
                    <?php else: ?>
                        <span class="delete disabled" <?php echo $action; ?>></span>
                    <?php endif ?>
                </td>
                <td class="colFullName">
                    <div class="overflow"><?php echo $row->fullName; ?></div>
                </td>
                <td class="colUsername">
                    <div class="overflow"><?php echo $row->username; ?></div>
                </td>
                <td class="colEmail">
                    <div class="overflow"><?php echo $row->email; ?></div>
                </td>
                <td class="colRole">
                    <?php echo ucfirst($row->role); ?>
                </td>
            </tr>
        <?php
        }
    ?>
    </table>
    <div class="pager-container"><?php echo $pager->display(); ?></div>
<?php
} else {
?>
    <div class="no-record"><?php echo _t("You don't have any user! %sLet's go create a new user!%s", '<a href="'._url('admin/user/setup').'">', '</a>'); ?></div>
<?php
}
