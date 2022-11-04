<?php

$addons = [
    'mod_simplecertificate' => [ // Plugin identifier
        'handlers' => [ // Different places where the plugin will display content.
            'coursesimplecertificate' => [ // Handler unique name (alphanumeric).
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/simplecertificate/pix/icon.svg',
                    'class' => '',
                ],
 
                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the plugin)
                'method' => 'download_certificate', // Main function in \mod_simplecertificate\output\mobile
                'offlinefunctions' => [
                    'download_certificate' => array()
                ], // Function that needs to be downloaded for offline.
            ],
        ],
        'lang' => [ // Language strings that are used in all the handlers.
            ['pluginname', 'simplecertificate'],
        ],
    ],
];