<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Helpers for the learning journey login portal.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * URL of the portal login page.
 *
 * @return moodle_url
 */
function local_learningjourneylogin_login_url(): moodle_url {
    return new moodle_url('/local/learningjourneylogin/index.php');
}

/**
 * Admin-configured login copy, or the language-pack default when a field is empty.
 *
 * @param string $setting Config name under local_learningjourneylogin.
 * @param string $stringid Fallback string identifier.
 * @param string $component Fallback component. Empty string uses a core string.
 * @return string
 */
function local_learningjourneylogin_configured_text(string $setting, string $stringid, string $component = 'local_learningjourneylogin'): string {
    $value = trim((string) (get_config('local_learningjourneylogin', $setting) ?? ''));
    if ($value !== '') {
        return format_string($value);
    }
    if ($component === '') {
        return get_string($stringid);
    }
    return get_string($stringid, $component);
}

/**
 * Visible texts on the login page.
 *
 * @return array<string, string>
 */
function local_learningjourneylogin_login_copy(): array {
    global $SITE;

    $footer = trim((string) (get_config('local_learningjourneylogin', 'loginfooter') ?? ''));

    return [
        'logintitle' => local_learningjourneylogin_configured_text('logintitle', 'logintitle'),
        'loginsubtitle' => local_learningjourneylogin_configured_text('loginsubtitle', 'loginsubtitle'),
        'usernamelabel' => local_learningjourneylogin_configured_text('usernamelabel', 'username', ''),
        'passwordlabel' => local_learningjourneylogin_configured_text('passwordlabel', 'password', ''),
        'loginlabel' => local_learningjourneylogin_configured_text('loginbutton', 'login', ''),
        'forgotpasswordlabel' => local_learningjourneylogin_configured_text('forgotpassword', 'forgotten', ''),
        'sitename' => $footer !== '' ? format_string($footer) : format_string($SITE->fullname),
    ];
}

/**
 * URL of the learning journey course list.
 *
 * @return moodle_url
 */
function local_learningjourneylogin_courses_url(): moodle_url {
    return new moodle_url('/local/learningjourneylogin/courses.php');
}

/**
 * Site logo configured in Moodle (Appearance > Logos), via the active theme.
 *
 * @return array{haslogo: bool, logourl: string, logoalt: string}
 */
function local_learningjourneylogin_logo(): array {
    global $OUTPUT, $SITE;

    $logo = $OUTPUT->get_logo_url(null, 200);
    if (!$logo) {
        $logo = $OUTPUT->get_compact_logo_url();
    }

    return [
        'haslogo' => !empty($logo),
        'logourl' => $logo ? $logo->out(false) : '',
        'logoalt' => format_string($SITE->fullname),
    ];
}

/**
 * Course image URL from overview files, when one exists.
 *
 * @param stdClass $course
 * @return string|null
 */
function local_learningjourneylogin_course_image(stdClass $course): ?string {
    $courseinlist = new core_course_list_element($course);
    foreach ($courseinlist->get_course_overviewfiles() as $file) {
        if (!$file->is_valid_image()) {
            continue;
        }
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            null,
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }
    return null;
}

/**
 * Visible learning journey courses the current user is allowed to see.
 *
 * @return array<int, array<string, mixed>>
 */
function local_learningjourneylogin_get_courses(): array {
    global $DB, $OUTPUT, $USER;

    $sql = "SELECT c.*, cc.name AS categoryname
              FROM {course} c
         LEFT JOIN {course_categories} cc ON cc.id = c.category
             WHERE c.format = :format
          ORDER BY c.sortorder ASC, c.fullname ASC";
    $records = $DB->get_records_sql($sql, ['format' => 'learningjourney']);
    $enrolled = enrol_get_users_courses($USER->id, true, 'id');

    $courses = [];
    foreach ($records as $course) {
        if (!core_course_category::can_view_course_info($course)) {
            continue;
        }

        $context = context_course::instance($course->id);
        $summary = '';
        if (!empty($course->summary)) {
            $summary = shorten_text(strip_tags(format_text(
                $course->summary,
                (int) $course->summaryformat,
                ['context' => $context, 'para' => false]
            )), 180);
        }

        $image = local_learningjourneylogin_course_image($course);
        $courses[] = [
            'id' => (int) $course->id,
            'fullname' => format_string($course->fullname, true, ['context' => $context]),
            'shortname' => format_string($course->shortname, true, ['context' => $context]),
            'category' => format_string($course->categoryname ?? ''),
            'summary' => $summary,
            'hassummary' => $summary !== '',
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'image' => $image,
            'hasimage' => $image !== null,
            'placeholder' => $OUTPUT->get_generated_image_for_id((int) $course->id),
            'enrolled' => isset($enrolled[$course->id]),
            'hidden' => empty($course->visible),
        ];
    }

    return $courses;
}

/**
 * Add a link to the course list in the navigation drawer.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_learningjourneylogin_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $navigation->add(
        get_string('coursesheading', 'local_learningjourneylogin'),
        local_learningjourneylogin_courses_url(),
        navigation_node::TYPE_CUSTOM,
        null,
        'learningjourneylogin',
        new pix_icon('i/course', '')
    );
}
