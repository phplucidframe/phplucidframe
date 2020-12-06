<?php
/**
 * All custom pager helper functions specific to the site should be defined here.
 */

/**
 * Get ordinary (non-ajax) pager object
 * @param int $rowCount
 * @param  string $pageQueryStr The customized page query string name
 * @return \LucidFrame\Core\Pager|object
 */
function pager_ordinary($rowCount, $pageQueryStr = '')
{
    return _pager($pageQueryStr)
        ->set('itemsPerPage', _cfg('itemsPerPage'))
        ->set('pageNumLimit', _cfg('pageNumLimit'))
        ->set('total', $rowCount)
        ->calculate();
}

/**
 * Get ajax pager object
 * @param int $rowCount
 * @param  string $pageQueryStr The customized page query string name
 * @return \LucidFrame\Core\Pager|object
 */
function pager_ajax($rowCount, $pageQueryStr = '')
{
    return _pager($pageQueryStr)
        ->set('itemsPerPage', _cfg('itemsPerPage'))
        ->set('pageNumLimit', _cfg('pageNumLimit'))
        ->set('total', $rowCount)
        ->set('ajax', true)
        ->calculate();
}

/**
 * Display callback for Pager object to customize pagination HTML
 * @param array $result The result property from Pager class
 *
 *  Array(
 *      [offset] => xx
 *      [thisPage] => xx
 *      [beforePages] => Array()
 *      [afterPages] => Array()
 *      [firstPageEnable] => xx
 *      [prePageEnable] => xx
 *      [nextPageNo] => xx
 *      [nextPageEnable] => xx
 *      [lastPageNo] => xx
 *      [lastPageEnable] => xx
 *      [url] => xx
 *      [ajax] => 1 or 0
 *  )
 *
 */
function pager_bootstrap($result)
{
    # The outermost container must have "lc-pager" class for AJAX pagination
    ?>
    <ul class="pagination lc-pager">
        <li class="first">
            <?php if ($result['firstPageEnable']): ?>
                <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $result['firstPageNo'] ?>"><?php echo _t('First') ?></a>
            <?php else: ?>
                <span><?php echo _t('First') ?></span>
            <?php endif ?>
        </li>

        <li class="prev">
            <?php if ($result['prePageEnable']): ?>
                <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $result['prePageNo'] ?>">«</a>
            <?php else: ?>
                <span>«</span>
            <?php endif ?>
        </li>

        <?php if (!empty($result['beforePages'])): ?>
            <?php foreach ($result['beforePages'] as $pg): ?>
                <li>
                    <?php if ($result['ajax']): ?>
                        <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $pg ?>"><?php echo $pg ?></a>
                    <?php else: ?>
                        <a href="<?php echo _url($result['url'], array($this->pageQueryStr => $pg)) ?>" rel="<?php echo $pg ?>"><?php echo $pg ?></a>
                    <?php endif ?>
                </li>
            <?php endforeach; ?>
        <?php endif ?>

        <li class="active">
            <a href="#"><?php echo $result['thisPage'] ?></a>
        </li>

        <?php if (!empty($result['afterPages'])): ?>
            <?php foreach ($result['afterPages'] as $pg): ?>
                <li>
                    <?php if ($result['ajax']): ?>
                        <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $pg ?>"><?php echo $pg ?></a>
                    <?php else: ?>
                        <a href="<?php echo _url($result['url'], array($this->pageQueryStr => $pg)) ?>" rel="<?php echo $pg ?>"><?php echo $pg ?></a>
                    <?php endif ?>
                </li>
            <?php endforeach; ?>
        <?php endif ?>

        <li class="next">
            <?php if ($result['nextPageEnable']): ?>
                <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $result['nextPageNo'] ?>">»</a>
            <?php else: ?>
                <span>»</span>
            <?php endif ?>
        </li>

        <li class="last">
            <?php if ($result['lastPageEnable']): ?>
                <a href="<?php echo _url($result['url']) ?>" rel="<?php echo $result['lastPageNo'] ?>"><?php echo _t('Last') ?></a>
            <?php else: ?>
                <span><?php echo _t('Last') ?></span>
            <?php endif ?>
        </li>
    </ul>
    <?php
}
