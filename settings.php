<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Admin settings for the learning journey login portal.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_learningjourneylogin',
        get_string('pluginname', 'local_learningjourneylogin')
    );
    $ADMIN->add('localplugins', $settings);

    $loginurl = (new moodle_url('/local/learningjourneylogin/index.php'))->out(false);
    $coursesurl = (new moodle_url('/local/learningjourneylogin/courses.php'))->out(false);
    $settings->add(new admin_setting_heading(
        'local_learningjourneylogin/heading',
        get_string('pluginname', 'local_learningjourneylogin'),
        get_string('settingintro', 'local_learningjourneylogin', (object) [
            'login' => $loginurl,
            'courses' => $coursesurl,
        ])
    ));

    $settings->add(new admin_setting_heading(
        'local_learningjourneylogin/logincopyheading',
        get_string('logincopyheading', 'local_learningjourneylogin'),
        get_string('logincopyheading_desc', 'local_learningjourneylogin')
    ));

    $textsettings = [
        'logintitle' => PARAM_TEXT,
        'loginsubtitle' => PARAM_TEXT,
        'usernamelabel' => PARAM_TEXT,
        'passwordlabel' => PARAM_TEXT,
        'loginbutton' => PARAM_TEXT,
        'forgotpassword' => PARAM_TEXT,
        'loginfooter' => PARAM_TEXT,
    ];
    foreach ($textsettings as $name => $param) {
        $settings->add(new admin_setting_configtext(
            'local_learningjourneylogin/' . $name,
            get_string($name . '_setting', 'local_learningjourneylogin'),
            get_string($name . '_setting_desc', 'local_learningjourneylogin'),
            '',
            $param,
            70
        ));
    }
}
