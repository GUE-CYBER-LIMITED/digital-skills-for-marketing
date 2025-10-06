<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Modern Classroom Project course format main library
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Main class for the Modern Classroom Project course format
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_mcp extends core_courseformat\base {

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_format_options.section
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth(stdClass $cm = null, stdClass $section = null) {
        // Allow stealth for modular activities.
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        if (is_object($section)) {
            $section = $section->section;
        }
        if ($section == 0) {
            return get_string('section0name', 'format_mcp');
        } else {
            return get_string('lesson', 'format_mcp', $section);
        }
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax response.
     */
    function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added when course format is changed.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array('search_forums', 'news_items', 'calendar_upcoming', 'recent_activity')
        );
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * MCP format uses the following options:
     * - mastery_threshold: percentage required for mastery (default 80%)
     * - show_progress: whether to show progress indicators
     * - enable_mastery_tracking: whether mastery must be achieved before proceeding
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'mastery_threshold' => array(
                    'default' => 80,
                    'type' => PARAM_INT,
                ),
                'show_progress' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                ),
                'enable_mastery_tracking' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['mastery_threshold']['label'])) {
            $courseformatoptionsedit = array(
                'mastery_threshold' => array(
                    'label' => new lang_string('defaultmastery', 'format_mcp'),
                    'help' => 'defaultmastery',
                    'help_component' => 'format_mcp',
                    'element_type' => 'text',
                    'element_attributes' => array('size' => 3),
                ),
                'show_progress' => array(
                    'label' => new lang_string('showprogressbar', 'format_mcp'),
                    'help' => 'showprogressbar',
                    'help_component' => 'format_mcp',
                    'element_type' => 'advcheckbox',
                ),
                'enable_mastery_tracking' => array(
                    'label' => new lang_string('enablemastery', 'format_mcp'),
                    'help' => 'enablemastery',
                    'help_component' => 'format_mcp',
                    'element_type' => 'advcheckbox',
                ),
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Get MCP component structure for a section
     *
     * @param stdClass $section
     * @return array
     */
    public function get_mcp_components($section = null) {
        $components = array(
            'learning_objectives' => array(
                'icon' => '🎯',
                'title' => get_string('learning_objectives', 'format_mcp'),
                'content' => '',
                'required' => true,
            ),
            'pacing_guide' => array(
                'icon' => '⏳',
                'title' => get_string('pacing_guide', 'format_mcp'),
                'content' => '',
                'required' => true,
            ),
            'blended_instruction' => array(
                'icon' => '📺',
                'title' => get_string('blended_instruction', 'format_mcp'),
                'content' => '',
                'required' => true,
            ),
            'activities_resources' => array(
                'icon' => '📝',
                'title' => get_string('activities_resources', 'format_mcp'),
                'content' => '',
                'required' => true,
            ),
            'mastery_check' => array(
                'icon' => '✅',
                'title' => get_string('mastery_check', 'format_mcp'),
                'content' => '',
                'required' => true,
            ),
            'reflection_collaboration' => array(
                'icon' => '🛠️',
                'title' => get_string('reflection_collaboration', 'format_mcp'),
                'content' => '',
                'required' => false,
            ),
            'supports_differentiation' => array(
                'icon' => '🌱',
                'title' => get_string('supports_differentiation', 'format_mcp'),
                'content' => '',
                'required' => false,
            ),
            'progress_tracking' => array(
                'icon' => '📊',
                'title' => get_string('progress_tracking', 'format_mcp'),
                'content' => '',
                'required' => false,
            ),
        );

        // If section is provided, populate with actual content
        if ($section && !empty($section->summary)) {
            $components = $this->parse_mcp_content($section->summary, $components);
        }

        return $components;
    }

    /**
     * Parse MCP content from section summary
     *
     * @param string $summary
     * @param array $components
     * @return array
     */
    private function parse_mcp_content($summary, $components) {
        // Parse each MCP component from the section summary
        foreach ($components as $key => $component) {
            $pattern = '/' . preg_quote($component['icon']) . '.*?' . preg_quote($component['title']) . '\s*\n(.*?)(?=' . 
                       preg_quote('🎯|⏳|📺|📝|✅|🛠️|🌱|📊') . '|$)/s';
            
            if (preg_match($pattern, $summary, $matches)) {
                $components[$key]['content'] = trim($matches[1]);
            }
        }
        
        return $components;
    }

    /**
     * Whether this format allows to delete sections
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return \core\output\inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_mcp');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_mcp', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of the Competencies link in the course administration menu.
     *
     * @return bool
     */
    public function supports_competencies() {
        return true;
    }
}