<h3><?php echo $title ?></h3>

<form>
    <div class="row">
        <!-- Bind the input value to the data property "task_title" -->
        <!-- data-on="off" to prevent re-rendering on input change -->
        <input
            data-bind="task_title"
            data-on="off"
            type="text"
            placeholder="What needs to be done?"
            class="lc-form-input fluid-35"
            value="<?php echo $task_title ?>"
            autofocus
        />
        <!-- On click, trigger the live action "addTask" and re-render the component -->
        <button type="submit" data-live="addTask" class="button green">Add Todo</button>
    </div>
</form>

<?php if (count($tasks)): ?>
    <?php foreach ($tasks as $task): ?>
        <p class="row">
            <!-- On click, trigger the live action "deleteTask" by passing the task ID and re-render the component -->
            <button type="button" class="button mini red" data-live="deleteTask(<?php echo $task['id'] ?>)">Delete</button>
            <!-- On change, bind the selected checkbox value to the data property array "completed[]" and re-render the component -->
            <input
                data-bind="completed[]"
                type="checkbox"
                id="task-<?php echo $task['id'] ?>"
                value="<?php echo $task['id'] ?>"
            />
            <label
                for="task-<?php echo $task['id'] ?>"
                class="form-check-label"
                <?php echo in_array($task['id'], $completed) ? ' style="text-decoration: line-through; color: #999"' : '' ?>
            >
                <?php echo $task['title'] ?>
            </label>
        </p>
    <?php endforeach; ?>
    <p>
        <!-- On click, trigger the live action "completeAll" and re-render the component -->
        <a href="#" data-live="completeAll">Complete All</a> |
        <!-- On click, trigger the live action "clearAll" and re-render the component -->
        <a href="#" data-live="clearAll">Clear All</a>
    </p>
<?php endif; ?>
