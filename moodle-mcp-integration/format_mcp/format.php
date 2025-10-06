<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Modern Classroom Project course format display logic
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Retrieve course format option fields and add them to the $course object.
$format = course_get_format($course);
$course = $format->get_course();
$context = context_course::instance($course->id);

// Add CSS and JavaScript for MCP format
$PAGE->requires->css('/course/format/mcp/styles/mcp.css');
$PAGE->requires->js('/course/format/mcp/javascript/mcp.js');

// Make sure all sections are created.
course_create_sections_if_missing($course, range(0, $course->numsections));

$modinfo = get_fast_modinfo($course);
$course = course_get_format($course)->get_course();

$renderer = $PAGE->get_renderer('format_mcp');
$courserenderer = $PAGE->get_renderer('core', 'course');

if (!empty($displaysection)) {
    $format->set_section_number($displaysection);
}
$outputclass = $format->get_output_classname('content');
$output = new $outputclass($format);
echo $renderer->render($output);