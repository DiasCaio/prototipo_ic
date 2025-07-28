<?php
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext(
    'block_descricao_imagens/api_url',
    get_string('api_url', 'block_descricao_imagens'),
    get_string('api_url_desc', 'block_descricao_imagens'),
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
    PARAM_URL
));

$settings->add(new admin_setting_configpasswordunmask(
    'block_descricao_imagens/api_key',
    get_string('api_key', 'block_descricao_imagens'),
    get_string('api_key_desc', 'block_descricao_imagens'),
    ''
));
