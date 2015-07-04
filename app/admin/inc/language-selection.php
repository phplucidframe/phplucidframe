<?php
if (auth_isAdmin()) {
?>
<div id="lang-content-management">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Select one to manage contents by language</td>
                <td>
                <?php
                $lang = _getLang();
                foreach ($lc_languages as $lcode => $lname) {
                    $class = ($lang == $lcode) ? 'active' : '';
                    $url = _url(null, array('lang' => $lcode));
                ?>
                    <a href="<?php echo $url ?>" class="<?php echo $class; ?>">
                        <span><?php _image('flags/'.$lcode.'.png', $lname); ?></span>
                    </a>
                <?php
                }
                ?>
            </td>
        </tr>
    </table>
</div>
<?php
}
