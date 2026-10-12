<?php
// Copyright © CentricApp LTD. dev@centricapp.co.il

/**
 * Privacy API implementation. This plugin stores no personal data.
 *
 * @package    local_learningjourneylogin
 * @copyright  CentricApp LTD. dev@centricapp.co.il
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learningjourneylogin\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Null provider.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Reason this plugin stores no personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
