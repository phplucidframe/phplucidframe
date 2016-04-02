<?php
$get  = _get($_GET);
$lang = _getLang();

# Count query for the pager
$rowCount = db_count('post')
    ->where()->condition('deleted', null)
    ->fetch();

# Prerequisite for the Pager
$pager = _pager()
    ->set('itemsPerPage', $lc_itemsPerPage)
    ->set('pageNumLimit', $lc_pageNumLimit)
    ->set('total', $rowCount)
    ->set('ajax', true)
    ->calculate();

$qb = db_select('post', 'p')
    ->join('category', 'c', 'p.catId = c.catId')
    ->join('user', 'u', 'p.uid = u.uid')
    ->fields('p', array(
        'postId', 'created', 'postTitle', 'postBody',
        array('postTitle_'.$lang, 'postTitle_i18n'),
        array('postBody_'.$lang, 'postBody_i18n')
    ))
    ->fields('c', array(
        'catName',
        array('catName_'.$lang, 'catName_i18n')
    ))
    ->fields('u', array('fullName'))
    ->where()->condition('p.deleted', null)
    ->orderBy('p.created', 'DESC')
    ->orderBy('u.fullName')
    ->limit($pager->get('offset'), $pager->get('itemsPerPage'));

$lang = _urlLang($lang);

if ($qb->getNumRows()) {
?>
    <table cellpadding="0" cellspacing="0" border="0" class="list news">
        <tr class="label">
            <td class="tableLeft" colspan="2"><?php echo _t('Actions'); ?></td>
            <td>
                <span><?php echo _t('Title'); ?></span>
                <label class="lang">(<?php echo _langName($lang); ?>)</label>
            </td>
            <td><?php echo _t('Author'); ?></td>
            <td><?php echo _t('Category'); ?></td>
            <td><?php echo _t('Date') ?></td>
        </tr>
        <?php
        while ($row = $qb->fetchRow()) {
            $row->postTitle = ($row->postTitle_i18n) ? $row->postTitle_i18n : $row->postTitle;
            $row->postBody  = ($row->postBody_i18n) ? $row->postBody_i18n : $row->postBody;
            $row->catName   = ($row->catName_i18n) ? $row->catName_i18n : $row->catName;
            ?>
            <tr id="row-<?php echo $row->postId; ?>">
                <td class="tableLeft colAction">
                    <a href="<?php echo _url('admin/post/setup', array($row->postId, 'lang' => $lang)); ?>" class="edit" title="Edit" >
                        <span><?php echo _t('Edit'); ?></span>
                    </a>
                </td>
                <td class="colAction">
                    <a href="#" class="delete" title="Delete" onclick="LC.Page.Post.List.remove(<?php echo $row->postId; ?>)">
                        <span><?php echo _t('Delete'); ?></span>
                    </a>
                </td>
                <td class="colTitle <?php echo $lang; ?>"><?php echo $row->postTitle;?></td>
                <td class=""><?php echo $row->fullName; ?></td>
                <td class="<?php echo $lang; ?>"><?php echo $row->catName; ?></td>
                <td class=""><?php echo _fdateTime($row->created); ?></td>
            </tr>
            <?php
        }
?>
    </table>
    <div class="pager-container"><?php echo $pager->display(); ?></div>
<?php
} else {
?>
    <div class="no-record"><?php echo _t("You don't have any post! %sLet's go make a new post!%s", '<a href="'._url('admin/post/setup').'">', '</a>'); ?></div>
<?php
}

