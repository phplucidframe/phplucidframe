<?php
/**
 * The list.php (optional) is a server page requested by AJAX, which retrieves data and renders HTML to the client.
 * It is normally implemented for listing with pagination.
 */

$get = _get($_GET);

/*

# Count query for the pager
$totalRecords = db_count('post')
    ->where()->condition('deleted', null)
    ->fetch();

# Prerequisite for the Pager
$page = isset($get['page']) ? $get['page'] : 1;


$pager = _pager()
    ->set('page', $page)
    ->set('itemsPerPage', _cfg('itemsPerPage'))
    ->set('pageNumLimit', _cfg('pageNumLimit'))
    ->set('total', $totalRecords)
    ->set('imagePath', WEB_ROOT.'images/pager/')
    ->set('ajax', true)
    ->calculate();

$qb = db_select('post', 'p')
    ->where()->condition('deleted', null)
    ->orderBy('p.title', 'DESC')
    ->limit($pager->get('offset'), $pager->get('itemsPerPage'));

if ($qb->getNumRows()) {
    // ...
    while($row = $qb->fetchRow()) {
        // render HTML here
    }
} else {
    // ...
}

//// you could use the above similar code for the actual querying from db
//// the below is a sample code to render the simulated data

*/

$blog = array();
$blog[0] = array(
    'title' => 'Welcome to the PHPLucidFrame Blog',
    'slug'  => 'welcome-to-the-phplucidframe-blog',
    'body'  => 'PHPLucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.'
);
$blog[] = array(
    'title' => 'Custom Routing to a Page Including a Form Example',
    'slug'  => 'custom-routing-to-a-page-including-a-form-example',
    'body'     => 'This is an example page which shows custom routing rules defined in <code class="inline">/inc/route.config.php</code>. The rule is mapping to <code class="inline">/app/blog-page/index.php</code>. That could be possible to write URL rewrite rules in <code class="inline">.htacccess</code>.'
);
$blog[] = array(
    'title' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
    'slug'  => 'php-micro-application-development-framework',
    'body'     => 'Quisque varius sapien eget lorem feugiat vel dictum neque semper. Duis consequat nisl vitae risus adipiscing aliquam. Suspendisse vehicula egestas blandit. In laoreet molestie est. Donec rhoncus sodales ligula vitae fringilla. Mauris congue blandit metus eu eleifend. Cras gravida, nisi at euismod malesuada, justo massa adipiscing nisl, porttitor tristique urna ipsum id lacus. Nullam a leo neque, eget pulvinar urna. Suspendisse fringilla ante vitae nisi ultricies vestibulum. Donec id libero quis orci blandit placerat. '
);
$totalRecords = count($blog);

# Prerequisite for the Pager
$pager = _pager()
    ->set('itemsPerPage', $lc_itemsPerPage = 2)
    ->set('pageNumLimit', $lc_pageNumLimit)
    ->set('total', $totalRecords)
    ->set('imagePath', WEB_ROOT.'images/pager/')
    ->set('ajax', true)
    ->calculate();

$blog = array_slice($blog, $pager->get('offset'), $pager->get('itemsPerPage'));

if (count($blog)) {
    foreach ($blog as $id => $b) {
        $id++;
        $b = (object) $b;
    ?>
        <p class="blog">
            <h5><a href="<?php echo _url('blog', array($id, $b->slug)); ?>"><?php echo $b->title; ?></a></h5>
            <p><?php echo $b->body; ?></p>
            <p><a href="<?php echo _url('blog', array($id, $b->slug)); ?>" class="button mini green"><?php echo _t('Read More'); ?></a></p>
        </p>
    <?php
    }
    ?>
    <div class="pager-container clearfix">
        <?php echo $pager->display(); ?>
        <div class="pager-records"><?php echo _t('Total %d records', $totalRecords); ?></div>
    </div>
    <?php
} else {
    ?>
    <div class="noRecord"><?php echo _t('There is no record.'); ?></div>
    <?php
}
