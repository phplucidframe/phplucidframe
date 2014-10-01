<?php
$get  = _get($_GET);
$lang = _getLang();

$condition = ' WHERE a.deleted IS NULL';

# Count query for the pager
$rowCount = 0;
$sql = 'SELECT COUNT(*) AS count FROM '.db_prefix().'post a' . $condition;
$rowCount = db_count($sql);

# Prerequisite for the Pager
$pager = new Pager();
$pager->set('itemsPerPage', $lc_itemsPerPage);
$pager->set('pageNumLimit', $lc_pageNumLimit);
$pager->set('total', $rowCount);
$pager->set('imagePath', WEB_ROOT.'images/pager/');
$pager->set('ajax', true);
$pager->calculate();

$sql = "SELECT a.postId, a.slug, a.postTitle, a.postTitle_".$lang." postTitle_i18n, a.postBody, a.postBody_".$lang." postBody_i18n,
				u.uid, a.created, u.fullName, c.catName, c.catName_".$lang." catName_i18n
		FROM ".db_prefix()."post a
		JOIN ".db_prefix()."category c USING(catId)
		JOIN ".db_prefix()."user as u USING(uid)
		{$condition}
		ORDER BY u.uid DESC
		LIMIT ".$pager->get('offset').", ".$pager->get('itemsPerPage');
$result = db_query($sql);
	$lang = _urlLang($lang);

if($result){
	if(db_numRows($result)){
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
		while($row = db_fetchObject($result)){
			$row->postTitle = ($row->postTitle_i18n) ? $row->postTitle_i18n : $row->postTitle;
			$row->postBody 	= ($row->postBody_i18n) ? $row->postBody_i18n : $row->postBody;
			$row->catName 	= ($row->catName_i18n) ? $row->catName_i18n : $row->catName;
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
	}else{
	?>	<div class="no-record"><?php echo _t("You don't have any post! %sLet's go make a new post!%s", '<a href="'._url('admin/post/setup').'">', '</a>'); ?></div>
	<?php
	}
}