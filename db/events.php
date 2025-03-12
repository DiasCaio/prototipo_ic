<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_forum\event\post_created',
        'callback'    => '\local_imagedesc\observers::forum_post_created',
    ],
    [
        'eventname'   => '\mod_forum\event\post_updated',
        'callback'    => '\local_imagedesc\observers::forum_post_updated',
    ],
    [
        'eventname'   => '\mod_forum\event\discussion_created',
        'callback'    => '\local_imagedesc\observers::forum_post_created',
    ],
    [
        'eventname'   => '\core\event\draft_file_added',
        'callback'    => '\local_imagedesc\observers::file_uploaded',
    ],
    [
        'eventname'   => '\mod_forum\event\assessable_uploaded',
        'callback'    => '\local_imagedesc\observers::file_uploaded',
    ],
];
