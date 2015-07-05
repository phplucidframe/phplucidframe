<?php
/*

# Count query for the pager
$totalRecords = 0;
$sql = 'SELECT COUNT(*) AS count FROM '.db_prefix().'post';
$totalRecords = db_count($sql);

# Prerequisite for the Pager
$page = _arg('page');
$page = ( $page ) ? $page : 1;
$pager = new Pager();
$pager->set('page', $page);
$pager->set('itemsPerPage', $lc_itemsPerPage);
$pager->set('pageNumLimit', $lc_pageNumLimit);
$pager->set('total', $totalRecords);
$pager->set('imagePath', WEB_ROOT.'images/pager/');
$pager->set('ajax', false);
$pager->calculate();

$sql = 'SELECT * FROM '.db_prefix().' post
        ORDER BY post.title DESC
        LIMIT '.$pager->get('offset').', '.$pager->get('itemsPerPage');
$result = db_query($sql);

//// you could use the above similar code for the actual querying from db
//// the below is a sample code to render the simulated data

*/

$articles = array();
$articles[0] = array(
    'title' => 'Welcome to the LucidFrame Articles',
    'slug'  => 'welcome-to-the-lucidframe-blog',
    'body' 	=> 'LucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.'
);
$articles[] = array(
    'title' => 'URL Rewrite to A Lucid Page Including a Form Example',
    'slug'  => 'url-rewrite-to-a-lucid-page-including-a-form-example',
    'body' 	=> 'This is an example page which shows URL Rewrite rule in <code class="inline">.htacccess</code> how to rewrite URL to this page. The rule is mapping to /app/blog-page/index.php.'
);
$articles[] = array(
    'title' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
    'slug'  => 'php-micro-application-development-framework',
    'body' 	=> 'Quisque varius sapien eget lorem feugiat vel dictum neque semper. Duis consequat nisl vitae risus adipiscing aliquam. Suspendisse vehicula egestas blandit. In laoreet molestie est. Donec rhoncus sodales ligula vitae fringilla. Mauris congue blandit metus eu eleifend. Cras gravida, nisi at euismod malesuada, justo massa adipiscing nisl, porttitor tristique urna ipsum id lacus. Nullam a leo neque, eget pulvinar urna. Suspendisse fringilla ante vitae nisi ultricies vestibulum. Donec id libero quis orci blandit placerat. '
);
$totalRecords = count($articles);

# Prerequisite for the Pager
$pager = new Pager();
$pager->set('itemsPerPage', $lc_itemsPerPage = 2);
$pager->set('pageNumLimit', $lc_pageNumLimit);
$pager->set('total', $totalRecords);
$pager->set('imagePath', WEB_ROOT.'images/pager/');
$pager->set('ajax', false);
$pager->calculate();

$articles = array_slice($articles, $pager->get('offset'), $pager->get('itemsPerPage'));
