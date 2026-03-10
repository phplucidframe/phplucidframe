<?php

$max = $component->useData('max', 1000);

$component->setProps('randomNumber', random_int(0, $max));
$component->useData('count', 0);
$component->useData('name', 'Alex Johnson');
$component->useData('volume', 50);
$component->useData('birthdate', '1996-04-12');
$component->useData('meeting_time', '14:30');
$component->useData('favorite_color', '#3366ff');
$component->useData('bio', 'I like building web apps and exploring new tech.');
$component->useData('country');
$component->useData('contact_pref', 'email');
$component->useData('skills', []);
$component->useData('food', []);
$component->useData('terms', 0);

$component->action('increment', static function ($component) {
    $count = $component->getData('count');
    $component->setData('count', $count + 1);
});

$component->action('decrement', static function ($component) {
    $count = $component->getData('count');
    if ($count) {
        $component->setData('count', $count - 1);
    }
});

$component->action('delete', static function ($component, $id) {
    // echo $id;
});

return $component->getData();
