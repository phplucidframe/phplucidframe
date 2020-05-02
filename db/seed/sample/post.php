<?php

use LucidFrame\Core\Seeder;

return array(
    'order' => 3,
    'post-1' => array(
        'cat_id'    => Seeder::setReference('category-2'),
        'user_id'   => Seeder::setReference('user-1'),
        'slug'      => 'welcome-to-the-lucidframe-blog',
        'title'     => 'Welcome to the LucidFrame Blog',
        'title_en'  => 'Welcome to the LucidFrame Blog',
        'title_my'  => 'LucidFrame ဘလော့ဂ်မှ ကြိုဆိုပါသည်',
        'body'      => 'LucidFrame is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development. It uses a module architecture to make the development of complex applications simplified.',
        'body_en'   => 'LucidFrame is a mini application development framework - a toolkit for PHP developers. It provides logical structure and several helper utilities for web application development. It uses a module architecture to make the development of complex applications simplified.',
        'body_my'   => 'LucidFrame သည် PHP developer များအတွက် Toolkit အဖြစ်အသုံးချနိုင်သော mini application development framework တစ်ခုဖြစ်ပါသည်။ Web application တည်ဆောက်ခြင်းအတွက် logic ကျသောဖွဲ့စည်းတည်ဆောက်ပုံ နှင့် အထောက်အပံ့အကူအညီအများအပြားကို ပံပိုးပေးထားသည်။ ခက်ခဲရှုပ်ထွေးသော application များဖန်တီးခြင်းကို ရိုးရှင်းလွယ်ကူအောင် Module အခြေခံတည်ဆောက်မှုကို အသုံးပြုထားသည်။',
    ),
    'post-2' => array(
        'cat_id'    => Seeder::setReference('category-3'),
        'user_id'   => Seeder::setReference('user-1'),
        'slug'      => 'hello-world',
        'title'     => 'Hello world!',
        'title_en'  => 'Hello world',
        'title_my'  => 'မင်္ဂလာပါ',
        'body'      => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
        'body_en'   => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
        'body_my'   => 'Welcome to PHPLucidFrame. This is your post. Edit or delete it, then start blogging!',
    ),
);
