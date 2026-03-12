<?php
# count starts with 0
$component->useData('count', 0);

/**
 * Register a live action "increment" to increase the count
 */
$component->action('increment', static function ($component) {
    $count = $component->getData('count');
    $component->setData('count', $count + 1);
});

/**
 * Register a live action "decrement" to decrease the count
 */
$component->action('decrement', static function ($component) {
    $count = $component->getData('count');
    if ($count) {
        $component->setData('count', $count - 1);
    }
});
