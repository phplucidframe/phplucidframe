<?php

$blog = array();

$blog[] = array(
    'title' => 'Welcome to the PHPLucidFrame Blog',
    'slug'  => 'welcome-to-the-phplucidframe-blog',
    'body'  => 'PHPLucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.'
);

$blog[] = array(
    'title' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
    'slug'  => 'php-micro-application-development-framework',
    'body'  => 'Quisque varius sapien eget lorem feugiat vel dictum neque semper. Duis consequat nisl vitae risus adipiscing aliquam. Suspendisse vehicula egestas blandit. In laoreet molestie est. Donec rhoncus sodales ligula vitae fringilla. Mauris congue blandit metus eu eleifend. Cras gravida, nisi at euismod malesuada, justo massa adipiscing nisl, porttitor tristique urna ipsum id lacus. Nullam a leo neque, eget pulvinar urna. Suspendisse fringilla ante vitae nisi ultricies vestibulum. Donec id libero quis orci blandit placerat. '
);

_json(array(
    'total' => count($blog),
    'result' => $blog
));
