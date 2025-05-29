<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    // Eventos antigos que já funcionavam
    [
        'eventname'   => '\mod_forum\event\post_created',
        'callback'    => '\local_imagedesc\observers::forum_post_created',
    ],
];
