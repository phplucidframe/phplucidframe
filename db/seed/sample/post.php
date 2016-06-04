<?php

use LucidFrame\Core\Seeder;

return array(
    'order' => 3,
    'post-1' => array(
        'catId'         => Seeder::setReference('category-2'),
        'uid'           => Seeder::setReference('user-1'),
        'slug'          => 'welcome-to-the-lucidframe-blog',
        'postTitle'     => 'Welcome to the LucidFrame Blog',
        'postTitle_en'  => 'Welcome to the LucidFrame Blog',
        'postTitle_my'  => 'LucidFrame ဘလော့ဂ်မှ ကြိုဆိုပါသည်',
        'postBody'      => 'LucidFrame is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development. It uses a module architecture to make the development of complex applications simplified.',
        'postBody_en'   => 'LucidFrame is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development. It uses a module architecture to make the development of complex applications simplified.',
        'postBody_my'   => 'LucidFrame သည် PHP developer များအတွက် Toolkit အဖြစ်အသုံးချနိုင်သော mini application development framework တစ်ခုဖြစ်ပါသည်။ Web application တည်ဆောက်ခြင်းအတွက် logic ကျသောဖွဲ့စည်းတည်ဆောက်ပုံ နှင့် အထောက်အပံ့အကူအညီအများအပြားကို ပံပိုးပေးထားသည်။ ခက်ခဲရှုပ်ထွေးသော application များဖန်တီးခြင်းကို ရိုးရှင်းလွယ်ကူအောင် Module အခြေခံတည်ဆောက်မှုကို အသုံးပြုထားသည်။',
    ),
    'post-2' => array(
        'catId'         => Seeder::setReference('category-3'),
        'uid'           => Seeder::setReference('user-1'),
        'slug'          => 'hello-world',
        'postTitle'     => 'Hello world!',
        'postTitle_en'  => 'Hello world',
        'postTitle_my'  => 'မင်္ဂလာပါ',
        'postBody'      => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
        'postBody_en'   => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
        'postBody_my'   => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
    ),
);
