<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Log out of the learning journey portal and return to its login page.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/learningjourneylogin/logout.php'));

require_sesskey();
require_logout();

redirect(local_learningjourneylogin_login_url());
