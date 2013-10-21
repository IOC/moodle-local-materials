<?php

function xmldb_local_materials_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = true;

    /// Add a new column newcol to the mdl_myqtype_options
    if ($result && $oldversion < 2013100801) {
         // Rename field path on table local_materials to sources.
        $table = new xmldb_table('local_materials');
        $field = new xmldb_field('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch rename field path.
        $dbman->rename_field($table, $field, 'sources');

        // Local savepoint reached.
        upgrade_plugin_savepoint(true, 2013100801, '', 'local');
    }

    return $result;
}

