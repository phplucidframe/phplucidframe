<?php
/**
 * You can write functions here for db operations related to a specific entity/table
 * This is an example file for post table
 * The file name convention is {table_name}.php, e.g., post.php
 * The function name convention is table_name_methodName(), e.g., post_getComments($id)
 */

/**
 * This is just an example function
 * @param int $id
 * @return stdClass
 */
function post_getMock($id)
{
    $post = new stdClass();

    $post->id       = $id;
    $post->title    = 'Custom Routing to a Page Including a Form Example';
    $post->body     = 'This would be from the database.';
    $post->slug     = 'custom-routing-to-a-page-including-a-form-example';

    return $post;
}
