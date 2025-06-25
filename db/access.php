<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/quizgenerator:use' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
