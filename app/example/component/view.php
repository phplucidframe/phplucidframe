<p>
    <a href="<?php echo _url('example/component/counter') ?>">Counter Demo</a> |
    <a href="<?php echo _url('example/component/form') ?>">Form Demo</a> |
    <a href="<?php echo _url('example/component/search') ?>">Search Demo</a> |
    <a href="<?php echo _url('example/component/todo') ?>">Todo Demo</a>
</p>

<?php if (_arg(2) == 'form'): ?>
    <?php _app('view')->component('form', array('title' => 'Live Form Component Demo', 'max' => 500)) ?>
<?php elseif (_arg(2) == 'search'): ?>
    <?php _app('view')->component('search', array('title' => 'Live Search Component Demo')) ?>
<?php elseif (_arg(2) == 'todo'): ?>
    <?php _app('view')->component('todos', array('title' => 'Live Todos Component Demo')) ?>
<?php else: ?>
    <?php _app('view')->component('counter', array('title' => 'Live Counter Component Demo')) ?>
<?php endif ?>
