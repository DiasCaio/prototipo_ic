<?php
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext(
    'block_descricao_imagens/api_url',
    get_string('api_url', 'block_descricao_imagens'),
    get_string('api_url_desc', 'block_descricao_imagens'),
    'https://huggingface.co/spaces/akhaliq/Molmo-7B-D-0924',
    PARAM_URL
));
