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
 * Journey mark used on the login page and the catalog header.
 *
 * @return array{haslogo: bool, logourl: string, logoalt: string}
 */
function local_learningjourneylogin_logo(): array {
    global $OUTPUT;

    $logo = $OUTPUT->image_url('logo', 'local_learningjourneylogin');

    return [
        'haslogo' => true,
        'logourl' => $logo->out(false),
        'logoalt' => 'Journey',
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

    static $courses = null;
    if ($courses !== null) {
        return $courses;
    }
    $courses = [];

    // Only courses with format option "content only" enabled.
    $sql = "SELECT c.*, cc.name AS categoryname
              FROM {course} c
         LEFT JOIN {course_categories} cc ON cc.id = c.category
              JOIN {course_format_options} cfo
                ON cfo.courseid = c.id
               AND cfo.format = c.format
               AND cfo.sectionid = 0
               AND cfo.name = :optname
               AND cfo.value = :optvalue
             WHERE c.format = :format
          ORDER BY c.sortorder ASC, c.fullname ASC";
    $records = $DB->get_records_sql($sql, [
        'format' => 'learningjourney',
        'optname' => 'contentonly',
        'optvalue' => '1',
    ]);
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
 * Prefer an enrolled learning-journey course for portal footer deep-links.
 *
 * @param array<int, array<string, mixed>> $courses
 * @return int|null
 */
function local_learningjourneylogin_footer_courseid(array $courses): ?int {
    foreach ($courses as $course) {
        if (!empty($course['enrolled']) && !empty($course['id'])) {
            return (int) $course['id'];
        }
    }
    if (!empty($courses[0]['id'])) {
        return (int) $courses[0]['id'];
    }
    return null;
}

/**
 * Shared sticky header + fixed footer chrome (matches format_learningjourney dark mode).
 *
 * @param array<int, array<string, mixed>> $courses
 * @param string $activefooter One of home|personal|messages|contact.
 * @return array<string, mixed>
 */
function local_learningjourneylogin_chrome_context(array $courses = [], string $activefooter = 'home'): array {
    global $OUTPUT, $SITE, $USER;

    $homeurl = local_learningjourneylogin_courses_url();
    $sitename = format_string($SITE->fullname, true, [
        'context' => context_course::instance(SITEID),
    ]);

    $brand = local_learningjourneylogin_logo();
    $haslogo = $brand['haslogo'];
    $logourl = $brand['logourl'];

    $menusections = [];
    foreach ($courses as $course) {
        $menusections[] = [
            'name' => $course['fullname'],
            'url' => $course['url'],
            'active' => false,
        ];
    }

    $footerhome = get_string('footerhome', 'local_learningjourneylogin');
    $footerpersonal = get_string('footerpersonal', 'local_learningjourneylogin');
    $footermessages = get_string('footermessages', 'local_learningjourneylogin');
    $footercontact = get_string('footercontact', 'local_learningjourneylogin');

    $courseid = local_learningjourneylogin_footer_courseid($courses);
    $personalurl = $homeurl;
    $messagesurl = $homeurl;
    $contacturl = $homeurl;
    if ($courseid && function_exists('format_learningjourney_portal_url')) {
        $personalurl = format_learningjourney_portal_url($courseid, 'personal');
        $messagesurl = format_learningjourney_portal_url($courseid, 'messages');
        $contacturl = format_learningjourney_portal_url($courseid, 'contact');
    } else if ($courseid) {
        $personalurl = new moodle_url('/course/format/learningjourney/personal.php', ['id' => $courseid]);
        $messagesurl = new moodle_url('/course/format/learningjourney/messages.php', ['id' => $courseid]);
        $contacturl = new moodle_url('/course/format/learningjourney/contact.php', ['id' => $courseid]);
    }

    $footeritems = [
        [
            'label' => $footerhome,
            'url' => $homeurl->out(false),
            'icon' => $OUTPUT->pix_icon('i/home', $footerhome),
            'active' => $activefooter === 'home',
        ],
        [
            'label' => $footerpersonal,
            'url' => $personalurl instanceof moodle_url ? $personalurl->out(false) : (string) $personalurl,
            'icon' => $OUTPUT->pix_icon('i/user', $footerpersonal),
            'active' => $activefooter === 'personal',
        ],
        [
            'label' => $footermessages,
            'url' => $messagesurl instanceof moodle_url ? $messagesurl->out(false) : (string) $messagesurl,
            'icon' => $OUTPUT->pix_icon('t/messages', $footermessages),
            'active' => $activefooter === 'messages',
        ],
        [
            'label' => $footercontact,
            'url' => $contacturl instanceof moodle_url ? $contacturl->out(false) : (string) $contacturl,
            'icon' => $OUTPUT->pix_icon('t/email', $footercontact),
            'active' => $activefooter === 'contact',
        ],
    ];

    $logouturl = new moodle_url('/local/learningjourneylogin/logout.php', ['sesskey' => sesskey()]);

    return [
        'homeurl' => $homeurl->out(false),
        'sitename' => $sitename,
        'haslogo' => $haslogo,
        'logourl' => $logourl,
        'menulabel' => get_string('footermenu', 'local_learningjourneylogin'),
        'coursemenulabel' => get_string('coursesheading', 'local_learningjourneylogin'),
        'sectionslabel' => get_string('coursesheading', 'local_learningjourneylogin'),
        'optionslabel' => get_string('footeraccount', 'local_learningjourneylogin'),
        'closelabel' => get_string('closebuttontitle'),
        'footerlabel' => get_string('footernav', 'local_learningjourneylogin'),
        'footeritems' => $footeritems,
        'hassections' => $menusections !== [],
        'sections' => $menusections,
        'hasoptions' => true,
        'options' => [
            [
                'name' => fullname($USER),
                'url' => $personalurl instanceof moodle_url ? $personalurl->out(false) : (string) $personalurl,
                'active' => false,
            ],
            [
                'name' => get_string('logout'),
                'url' => $logouturl->out(false),
                'active' => false,
            ],
        ],
        'editurl' => null,
        'editlabel' => '',
        'hamburgericon' => $OUTPUT->pix_icon('i/menubars', get_string('footermenu', 'local_learningjourneylogin')),
        // Catalog pages have no single course name/link in the menu head list.
        'coursename' => get_string('coursesheading', 'local_learningjourneylogin'),
        'courseurl' => $homeurl->out(false),
    ];
}

/**
 * Place the content-only header above #page, flush with the top of the viewport.
 *
 * The format stylesheet pads .embedded-main. Rendering the header inside that
 * shell leaves a gap above it. Course pages inject the same chrome here.
 *
 * @return string
 */
function local_learningjourneylogin_before_standard_top_of_body_html(): string {
    global $OUTPUT, $PAGE;

    if (strpos($PAGE->bodyclasses, 'ljlogin-courses-body') === false) {
        return '';
    }
    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $chrome = local_learningjourneylogin_chrome_context(local_learningjourneylogin_get_courses(), 'home');
    return $OUTPUT->render_from_template('format_learningjourney/contentonly_header', $chrome);
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
