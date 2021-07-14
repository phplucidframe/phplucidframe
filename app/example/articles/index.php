<?php
/**
 * The index.php serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */

/*

# Count query for the pager
$totalRecords = db_count('post')
    ->where()->condition('deleted', null)
    ->fetch();

# Prerequisite for the Pager
$pager = pager_ordinary();
//// OR
$pager = _pager()
    ->set('itemsPerPage', _cfg('itemsPerPage'))
    ->set('pageNumLimit', _cfg('pageNumLimit'))
    ->set('total', $totalRecords)
    ->set('imagePath', WEB_ROOT.'images/pager/')
    ->set('ajax', false)
    ->calculate();

$qb = db_select('post', 'p')
    ->where()->condition('deleted', null)
    ->orderBy('p.title', 'DESC')
    ->limit($pager->get('offset'), $pager->get('itemsPerPage'));

$articles = $qb->getResult();

    // in view.php
    if (count($articles)) {
        foreach ($articles as $article) {
            // ...
        }
    } else {
        // ...
    }

//// OR you can even use $qb directly in view.php

$articles = $qb; // to use more user-friendly name in view.php

    // in view.php
    if ($articles->getNumRows()) {
        // ...
        while($article = $articles->fetchRow()) {
            // ...
        }
    } else {
        // ...
    }

//// you could use the above similar code for the actual querying from db
//// the below is a sample code to render the simulated data

*/

$view = _app('view');

$articles = array();
$articles[] = array(
    'title' => 'Welcome to the PHPLucidFrame Articles',
    'slug'  => 'welcome-to-the-lucidframe-blog',
    'body'     => 'PHPLucidFrame is a mini application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.'
);
$articles[] = array(
    'title' => 'Custom Routing to a Page Including a Form Example',
    'slug'  => 'custom-routing-to-a-page-including-a-form-example',
    'body'     => 'This is an example page which shows custom routing rules defined in <code class="inline">/inc/route.config.php</code>. The rule is mapping to <code class="inline">/app/blog-page/index.php</code>. That could be possible to write URL rewrite rules in <code class="inline">.htacccess</code>.'
);
$articles[] = array(
    'title' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
    'slug'  => 'php-micro-application-development-framework',
    'body'     => 'Quisque varius sapien eget lorem feugiat vel dictum neque semper. Duis consequat nisl vitae risus adipiscing aliquam. Suspendisse vehicula egestas blandit. In laoreet molestie est. Donec rhoncus sodales ligula vitae fringilla. Mauris congue blandit metus eu eleifend. Cras gravida, nisi at euismod malesuada, justo massa adipiscing nisl, porttitor tristique urna ipsum id lacus. Nullam a leo neque, eget pulvinar urna. Suspendisse fringilla ante vitae nisi ultricies vestibulum. Donec id libero quis orci blandit placerat. '
);
$totalRecords = count($articles);

# Prerequisite for the Pager
$pager = _pager()
    ->set('itemsPerPage', _cfg('itemsPerPage', 2))
    ->set('pageNumLimit', _cfg('pageNumLimit'))
    ->set('total', $totalRecords)
    ->calculate();
// OR just one-line
// $pager = pager_ordinary($totalRecords);

$articles = array_slice($articles, $pager->get('offset'), $pager->get('itemsPerPage'));

_app('title', _t('Articles') . ' ('. _t('Normal Pagination Example') . ')');

$view->data = array(
    'pageTitle'     => _app('title'),
    'totalRecords'  => count($articles),
    'articles'      => $articles,
    'pager'         => $pager,
);

# Another way of setting view data
//$view->addData('pageTitle', _t('Articles') . ' ('. _t('Normal Pagination Example') . ')');
//$view->addData('totalRecords', count($articles);
//$view->addData('articles', $articles);
//$view->addData('pager', $pager);
