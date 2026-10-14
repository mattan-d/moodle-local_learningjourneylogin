<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Login screen for the learning journey portal.
 *
 * Successful sign-in is sent to the list of courses that use the learningjourney format.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(local_learningjourneylogin_login_url());
$PAGE->set_pagelayout('embedded');
$PAGE->set_cacheable(false);
$logincopy = local_learningjourneylogin_login_copy();
$PAGE->set_title($logincopy['logintitle']);
$PAGE->set_heading($logincopy['logintitle']);
$PAGE->add_body_class('ljlogin-body lj-dark-mode');
$PAGE->requires->css(new moodle_url('/local/learningjourneylogin/styles.css'));

$coursesurl = local_learningjourneylogin_courses_url();
$testsession = optional_param('testsession', 0, PARAM_INT);
$errormsg = '';

// Confirm the session cookie works, then continue to the course list.
if ($testsession) {
    if ((int) $testsession === (int) $USER->id) {
        $urltogo = !empty($SESSION->wantsurl) ? $SESSION->wantsurl : $coursesurl->out(false);
        unset($SESSION->wantsurl);
        redirect($urltogo);
    }
    $errormsg = get_string('cookiesnotenabled');
}

if (isloggedin() && !isguestuser() && !$testsession && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($coursesurl);
}

$frm = data_submitted();
if ($frm && isset($frm->username) && $errormsg === '') {
    $username = trim(core_text::strtolower((string) $frm->username));
    $password = (string) ($frm->password ?? '');
    $logintoken = isset($frm->logintoken) ? (string) $frm->logintoken : '';
    $errorcode = 0;
    // Login reCAPTCHA exists from Moodle 4.3. Older sites authenticate without it.
    $usecaptcha = function_exists('login_captcha_enabled') && login_captcha_enabled();
    if ($usecaptcha) {
        $loginrecaptcha = $frm->{'g-recaptcha-response'} ?? '';
        $user = authenticate_user_login($username, $password, false, $errorcode, $logintoken, $loginrecaptcha);
    } else {
        $user = authenticate_user_login($username, $password, false, $errorcode, $logintoken);
    }

    if ($user) {
        complete_user_login($user);
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        if (!empty($CFG->nolastloggedin) || empty($CFG->rememberusername)) {
            set_moodle_cookie('');
        } else {
            set_moodle_cookie($USER->username);
        }

        // Profile completion and forced password change still win over the course list.
        $SESSION->wantsurl = $coursesurl->out(false);
        $urltogo = core_login_get_return_url();
        $SESSION->wantsurl = $urltogo;
        unset($SESSION->ljloginerrormsg);
        redirect(new moodle_url('/local/learningjourneylogin/index.php', ['testsession' => $USER->id]));
    }

    if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
        $errormsg = get_string('unauthorisedlogin', '', $username);
    } else if (defined('AUTH_LOGIN_FAILED_RECAPTCHA') && $errorcode == AUTH_LOGIN_FAILED_RECAPTCHA) {
        $errormsg = get_string('missingrecaptchachallengefield');
    } else {
        $errormsg = get_string('invalidlogin');
    }

    // Never keep the submitted password on the next request.
    $SESSION->ljloginerrormsg = $errormsg;
    $SESSION->ljloginusername = $username;
    redirect(local_learningjourneylogin_login_url());
}

if (!empty($SESSION->ljloginerrormsg)) {
    $errormsg = (string) $SESSION->ljloginerrormsg;
    unset($SESSION->ljloginerrormsg);
}

$usernamevalue = '';
if (!empty($SESSION->ljloginusername)) {
    $usernamevalue = (string) $SESSION->ljloginusername;
    unset($SESSION->ljloginusername);
} else if (!empty($_GET['username'])) {
    $usernamevalue = clean_param($_GET['username'], PARAM_RAW);
} else {
    $usernamevalue = (string) get_moodle_cookie();
}

$recaptchahtml = '';
if (function_exists('login_captcha_enabled') && login_captcha_enabled()) {
    require_once($CFG->libdir . '/recaptchalib_v2.php');
    $recaptchahtml = recaptcha_get_challenge_html(RECAPTCHA_API_URL, $CFG->recaptchapublickey);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_learningjourneylogin/login', [
    ...local_learningjourneylogin_logo(),
    ...$logincopy,
    'username' => $usernamevalue,
    'error' => $errormsg,
    'haserror' => $errormsg !== '',
    'logintoken' => \core\session\manager::get_login_token(),
    'forgotpasswordurl' => (new moodle_url('/login/forgot_password.php'))->out(false),
    'recaptcha' => $recaptchahtml,
    'hasrecaptcha' => $recaptchahtml !== '',
]);
echo $OUTPUT->footer();
