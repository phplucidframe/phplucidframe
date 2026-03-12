<?php

# Define stateful data using default values
$component->useData('task_title', '');
$component->useData('tasks', []);
$component->useData('completed', []);

/**
 * Register a live action "addTask" to add a new task
 */
$component->action('addTask', static function($component) {
    $taskTitle = $component->getData('task_title');
    if ($taskTitle) {
        $tasks = $component->getData('tasks');
        $id = count($tasks) + 1;
        $tasks[] = [
            'id' => $id,
            'title' => $component->getData('task_title')
        ];
        $component->setData('tasks', array_values($tasks));
        $component->setData('task_title', '');
    }
});

/**
 * Register a live action "deleteTask" to delete the new task by id
 */
$component->action('deleteTask', static function($component, $id) {
    $tasks = $component->getData('tasks');
    $tasks = array_filter($tasks, function($task) use ($id) {
        return $task['id'] !== $id;
    });
    $component->setData('tasks', array_values($tasks));
});

/**
 * Register a live action "completeAll" to mark all tasks completed
 */
$component->action('completeAll', static function($component) {
    $ids = array_column($component->getData('tasks'), 'id');
    $component->setData('completed', $ids);
});

/**
 * Register a live action "clearAll" to delete all tasks
 */
$component->action('clearAll', static function($component) {
    $component->setData('tasks', []);
});
