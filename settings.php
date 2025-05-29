<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_imagedesc', get_string('pluginname', 'local_imagedesc'));

    // Configuração da API Key do Google AI Studio
    $settings->add(new admin_setting_configpasswordunmask(
        'local_imagedesc/apikey',
        get_string('apikey', 'local_imagedesc'),
        get_string('apikey_desc', 'local_imagedesc'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}