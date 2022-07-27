<?php
/**
 *
 * Function that will automatically remove ACF field groups via JSON file update
 *
 */
function acf_auto_suppr($json_dirs) {

    $groups = acf_get_field_groups();
    if (empty($groups)) {
        return;
    }
    // sync groups that have been deleted
    if (!is_array($json_dirs) || !$json_dirs) {
        throw new \Exception('JSON dirs missing');
    }
    $delete = array();
    foreach ($groups as $group) {
        $found = false;

        foreach ($json_dirs as $json_dir) {
            $json_file = rtrim($json_dir, '/') . '/' . $group['key'] . '.json';

            if (is_file($json_file)) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            $delete[] = $group['key'];
        }
    }
    if (!empty($delete)) {
        foreach ($delete as $group_key) {
            acf_delete_field_group($group_key);
        }
    }
}
/**
 * Function that will automatically update ACF field groups via JSON file update.
 *
 * @link http://www.advancedcustomfields.com/resources/synchronized-json/
 */
function acf_auto_synch($json_dirs) {

    $groups = acf_get_field_groups();
    if (empty($groups)) {
        return;
    }

    // find JSON field groups which have not yet been imported
    $sync 	= array();
    foreach ($groups as $group) {
        $local 		= acf_maybe_get($group, 'local', false);
        $modified 	= acf_maybe_get($group, 'modified', 0);
        $private 	= acf_maybe_get($group, 'private', false);

        // ignore DB / PHP / private field groups
        if ($local !== 'json' || $private) {
            // do nothing
        } elseif (! $group['ID']) {
            $sync[$group['key']] = $group;
        } elseif ($modified && $modified > get_post_modified_time('U', true, $group['ID'], true)) {
            $sync[$group['key']]  = $group;
        }
    }

    if (empty($sync)) {
        return;
    }
    foreach ($sync as $key => $group) { //foreach ($keys as $key) {
        // append fields
        if (acf_have_local_fields($key)) {
            $group['fields'] = acf_get_fields($key);
        }

        // import
        $field_group = acf_import_field_group($group);
    }
}

/**
 * run the auto synch
 */
add_action('init', function() {
    $path = get_stylesheet_directory() . '/acf-json';
    $json_dirs = array($path);
    acf_auto_suppr($json_dirs);
    acf_auto_synch($json_dirs);
});