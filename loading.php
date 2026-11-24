<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * The intro video address now opens the course list.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

if (!isloggedin() || isguestuser()) {
    redirect(local_learningjourneylogin_login_url());
}

redirect(local_learningjourneylogin_courses_url());
