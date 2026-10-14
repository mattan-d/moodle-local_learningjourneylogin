<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * List every course that uses the learningjourney course format.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(local_learningjourneylogin_courses_url());
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('coursesheading', 'local_learningjourneylogin'));
$PAGE->set_heading(get_string('coursesheading', 'local_learningjourneylogin'));
// Same chrome classes as format_learningjourney content-only dark mode.
$PAGE->add_body_class('ljlogin-body ljlogin-courses-body lj-content-only lj-dark-mode');
$PAGE->requires->css(new moodle_url('/course/format/learningjourney/styles.css'));
$PAGE->requires->css(new moodle_url('/local/learningjourneylogin/styles.css'));

if (!isloggedin() || isguestuser()) {
    redirect(local_learningjourneylogin_login_url());
}
require_login();

// Ensure format helpers (portal URLs) are available when the plugin is installed.
$formatlib = $CFG->dirroot . '/course/format/learningjourney/lib.php';
if (is_readable($formatlib)) {
    require_once($formatlib);
}

$courses = local_learningjourneylogin_get_courses();
$count = count($courses);

$PAGE->requires->js_call_amd('format_learningjourney/contentonly', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_learningjourneylogin/courses', [
    'heading' => get_string('coursesheading', 'local_learningjourneylogin'),
    'intro' => get_string('coursesintro', 'local_learningjourneylogin'),
    'countlabel' => get_string('coursescount', 'local_learningjourneylogin', $count),
    'hascourses' => $count > 0,
    'nocourses' => get_string('nocourses', 'local_learningjourneylogin'),
    'courses' => $courses,
    'enterlabel' => get_string('entercourse', 'local_learningjourneylogin'),
    'enrolledlabel' => get_string('enrolled', 'local_learningjourneylogin'),
    'hiddenlabel' => get_string('hiddencourse', 'local_learningjourneylogin'),
]);
echo $OUTPUT->footer();
