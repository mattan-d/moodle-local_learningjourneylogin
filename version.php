<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_learningjourneylogin';
$plugin->version   = 2026101502;
$plugin->release   = '1.0.1';
$plugin->maturity  = MATURITY_STABLE;
// Same floor as format_learningjourney, so the portal can be installed on that site.
$plugin->requires  = 2022100100;
$plugin->dependencies = [
    'format_learningjourney' => 2025050147,
];
