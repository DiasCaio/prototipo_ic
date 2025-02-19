<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/descricao_imagens:myaddinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['user' => CAP_ALLOW]
    ],
    'block/descricao_imagens:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager' => CAP_ALLOW]
    ]
];
