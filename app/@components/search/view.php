<h3><?php echo $title ?></h3>

<form>
    <div class="row">
        <!-- bind:query, on:keyup, live:search -->
        <!-- On keyup, trigger the live action "search" binding the input value to the data property "query" -->
        <input
            data-bind="query"
            data-on="keyup"
            data-live="search"
            type="text"
            placeholder="Results update as you type"
            class="lc-form-input fluid-100"
            autofocus
        />
    </div>
</form>

<table class="list table" data-render="query">
    <tr class="label">
        <td><?php echo _t('ID') ?></td>
        <td><?php echo _t('Name') ?></td>
        <td><?php echo _t('Email') ?></td>
    </tr>
    <?php if (count($users) > 0): ?>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?>
                <td><?php echo $user['name']; ?>
                <td><?php echo $user['email']; ?>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="3" class="center"><?php echo _t('No results found'); ?></td>
        </tr>
    <?php endif; ?>
</table>
