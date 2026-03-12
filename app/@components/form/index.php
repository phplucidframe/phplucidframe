<?php

# Define props that are not used to re-render the component
$max = $component->setProps('max', 1000);
$component->setProps('randomNumber', random_int(0, $max));

# Define stateful data using default values
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
