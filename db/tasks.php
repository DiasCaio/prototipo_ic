<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\local_imagedesc\\task\\process_pending_posts',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ]
];
