<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Full-screen loading video shown once after sign-in, then the course list.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(local_learningjourneylogin_loading_url());
$PAGE->set_pagelayout('embedded');
$PAGE->set_cacheable(false);
$PAGE->set_title(get_string('loadingtitle', 'local_learningjourneylogin'));
$PAGE->set_heading(get_string('loadingtitle', 'local_learningjourneylogin'));
$PAGE->add_body_class('ljlogin-body ljloading-body');
$PAGE->requires->css(new moodle_url('/local/learningjourneylogin/styles.css'));

if (!isloggedin() || isguestuser()) {
    redirect(local_learningjourneylogin_login_url());
}
require_login();

$nexturl = local_learningjourneylogin_courses_url()->out(false);
$videourl = (new moodle_url('/local/learningjourneylogin/video/loading.mp4'))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_learningjourneylogin/loading', [
    'videourl' => $videourl,
    'nexturl' => $nexturl,
    'loadingtitle' => get_string('loadingtitle', 'local_learningjourneylogin'),
]);
// Inline, not AMD: the listener must be attached before the short video can finish.
$nextjson = json_encode($nexturl);
echo html_writer::script("
(function() {
    var next = {$nextjson};
    var video = document.querySelector('.ljloading-video');
    var left = false;
    var go = function() {
        if (left) {
            return;
        }
        left = true;
        window.location.replace(next);
    };
    if (!video || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        go();
        return;
    }
    var timer = window.setTimeout(go, 14000);
    video.addEventListener('ended', function() {
        window.clearTimeout(timer);
        go();
    });
    video.addEventListener('error', go);
    var start = video.play();
    if (start && start.catch) {
        start.catch(function() {
            video.muted = true;
            var retry = video.play();
            if (retry && retry.catch) {
                retry.catch(go);
            }
        });
    }
})();
");
echo $OUTPUT->footer();
