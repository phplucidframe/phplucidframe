<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/tpl/header.php') ); ?>

<h3><?php echo $blog->title; ?></h3>
<p>
	This is an example page which shows URL Rewrite rule in <code class="inline">.htacccess</code> how to rewrite URL to this page. The rule is mapping to <code class="inline">/app/example/blog-page/index.php</code>.
	<code>
	# ~/blog/99/foo-bar to ~/app/example/blog-page/?lang=~&id=99&slug=foo-bar<br>
	RewriteRule ^(([a-z]{2}|[a-z]{2}-[A-Z]{2})/)?blog/([0-9]+)/(.*)$ app/index.php?lang=$1&id=$3&slug=$4&route=example/blog-page [NC,L]
	</code>
</p>
<p>This page also shows AJAX form example below. You can check the form validation and handling in <code class="inline">/app/blog-page/action.php</code>.</p>
<p>
	<h6><?php echo _t('Leave a Comment.'); ?></h6>
	<form id="frmComment" method="post">
		<div class="message"></div>
		<table cellpadding="0" cellspacing="0" class="form">
			<tr>
				<td class="label"><?php echo _t('Name')._cfg('reqSign'); ?></td>
				<td class="labelSeparator">:</td>
				<td class="entry">
					<input type="text" name="txtName" size="40" value="" />
				</td>
			</tr>
			<tr>
				<td class="label"><?php echo _t('Email')._cfg('reqSign'); ?></td>
				<td class="labelSeparator">:</td>
				<td class="entry">
					<input type="text" name="txtEmail" size="50" value="" />
				</td>
			</tr>
			<tr>
				<td class="label"><?php echo _t('Re-type Email')._cfg('reqSign'); ?></td>
				<td class="labelSeparator">:</td>
				<td class="entry">
					<input type="text" name="txtConfirmEmail" size="50" value="" />
				</td>
			</tr>
			<tr>
				<td class="label"><?php echo _t('Comment')._cfg('reqSign'); ?></td>
				<td class="labelSeparator">:</td>
				<td class="entry">
					<textarea name="txaComment" rows="7" cols="60"></textarea>
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
		<?php Form::token(); ?>
	</form>
</p>

<?php include( _i('inc/tpl/footer.php') ); ?>
