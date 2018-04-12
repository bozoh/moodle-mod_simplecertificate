<?php
$observers = array(
    array(
        'eventname'   => '\core\event\course_completed',
        'callback'    => '\mod_simplecertificate\observer::course_completed',
    )
);