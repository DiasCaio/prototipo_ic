<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_imagedesc_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025040400) {

        // Define table local_imagedesc_posts.
        $table = new xmldb_table('local_imagedesc_posts');

        // Adding fields to table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Condicional: só cria se não existir ainda.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Marca o upgrade como feito.
        upgrade_plugin_savepoint(true, 2025040400, 'local', 'imagedesc');
    }

    return true;
}
