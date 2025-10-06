<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Renderer for outputting the MCP course format.
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_mcp\output;

defined('MOODLE_INTERNAL') || die();

use core_courseformat\output\local\content as content_base;
use core_courseformat\base as course_format;
use moodle_url;
use stdClass;
use context_course;

/**
 * Basic renderer for MCP format.
 *
 * @copyright 2025 Digital Skills for Marketing
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $USER;
        
        $format = $this->format;
        $course = $format->get_course();
        $context = context_course::instance($course->id);
        
        $data = new stdClass();
        $data->title = $course->fullname;
        $data->courseid = $course->id;
        $data->format = $course->format;
        
        // Get course sections
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        
        $data->sections = [];
        $data->initialsection = null;
        $data->numsections = $course->numsections;
        
        foreach ($sections as $sectionnum => $section) {
            if ($sectionnum > $course->numsections) {
                continue;
            }
            
            $sectiondata = $this->export_section_data($section, $course, $output);
            
            if ($sectionnum == 0) {
                $data->initialsection = $sectiondata;
            } else {
                $data->sections[] = $sectiondata;
            }
        }
        
        // Add navigation data
        $data->navigation = $this->get_navigation_data($course);
        
        // Add course completion data
        $data->completion = $this->get_completion_data($course, $USER->id);
        
        return $data;
    }
    
    /**
     * Export section data for template
     *
     * @param \section_info $section
     * @param stdClass $course
     * @param \renderer_base $output
     * @return stdClass
     */
    protected function export_section_data($section, $course, $output) {
        global $USER;
        
        $format = $this->format;
        $context = context_course::instance($course->id);
        
        $sectiondata = new stdClass();
        $sectiondata->id = $section->id;
        $sectiondata->section = $section->section;
        $sectiondata->name = get_section_name($course, $section);
        $sectiondata->summary = format_text($section->summary, $section->summaryformat);
        $sectiondata->visible = $section->visible;
        $sectiondata->available = $section->available;
        
        // Get MCP components for this section
        $sectiondata->components = $this->get_mcp_components_data($section, $format);
        
        // Get activities in this section
        $sectiondata->activities = $this->get_section_activities($section, $output);
        
        // Get progress data for this section
        $sectiondata->progress = $this->get_section_progress($section, $USER->id);
        
        // Get mastery status
        $sectiondata->mastery_status = $this->get_mastery_status($section, $USER->id);
        
        // Navigation within section
        $sectiondata->navigation = $this->get_section_navigation($section, $course);
        
        return $sectiondata;
    }
    
    /**
     * Get MCP components data for a section
     *
     * @param \section_info $section
     * @param course_format $format
     * @return array
     */
    protected function get_mcp_components_data($section, $format) {
        $components = $format->get_mcp_components($section);
        $componentdata = [];
        
        foreach ($components as $key => $component) {
            $data = new stdClass();
            $data->key = $key;
            $data->icon = $component['icon'];
            $data->title = $component['title'];
            $data->content = $component['content'];
            $data->required = $component['required'];
            
            $componentdata[] = $data;
        }
        
        return $componentdata;
    }
    
    /**
     * Get activities for a section
     *
     * @param \section_info $section
     * @param \renderer_base $output
     * @return array
     */
    protected function get_section_activities($section, $output) {
        global $USER;
        
        $activities = [];
        $modinfo = get_fast_modinfo($section->course);
        
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                
                if (!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', 
                    context_course::instance($section->course))) {
                    continue;
                }
                
                $activity = new stdClass();
                $activity->id = $cm->id;
                $activity->name = $cm->name;
                $activity->url = $cm->url;
                $activity->icon = $cm->get_icon_url();
                $activity->modname = $cm->modname;
                $activity->description = $cm->content;
                
                // Get completion status
                $completion = new \completion_info($modinfo->get_course());
                if ($completion->is_enabled($cm)) {
                    $completiondata = $completion->get_data($cm, true, $USER->id);
                    $activity->completion = new stdClass();
                    $activity->completion->enabled = true;
                    $activity->completion->completed = $completiondata->completionstate == COMPLETION_COMPLETE ||
                                                    $completiondata->completionstate == COMPLETION_COMPLETE_PASS;
                } else {
                    $activity->completion = new stdClass();
                    $activity->completion->enabled = false;
                    $activity->completion->completed = false;
                }
                
                $activities[] = $activity;
            }
        }
        
        return $activities;
    }
    
    /**
     * Get progress data for a section
     *
     * @param \section_info $section
     * @param int $userid
     * @return stdClass
     */
    protected function get_section_progress($section, $userid) {
        global $DB;
        
        $progress = new stdClass();
        $progress->percentage = 0;
        $progress->completed_activities = 0;
        $progress->total_activities = 0;
        
        // Get completion info
        $course = $DB->get_record('course', ['id' => $section->course]);
        $completion = new \completion_info($course);
        
        if ($completion->is_enabled()) {
            $modinfo = get_fast_modinfo($section->course);
            
            if (!empty($modinfo->sections[$section->section])) {
                $total = 0;
                $completed = 0;
                
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    
                    if ($completion->is_enabled($cm)) {
                        $total++;
                        $completiondata = $completion->get_data($cm, true, $userid);
                        
                        if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                            $completed++;
                        }
                    }
                }
                
                $progress->total_activities = $total;
                $progress->completed_activities = $completed;
                
                if ($total > 0) {
                    $progress->percentage = round(($completed / $total) * 100);
                }
            }
        }
        
        // Add helper properties for template
        $progress->percentage_gte_50 = $progress->percentage >= 50;
        $progress->percentage_gte_80 = $progress->percentage >= 80;
        
        return $progress;
    }
    
    /**
     * Get mastery status for a section
     *
     * @param \section_info $section
     * @param int $userid
     * @return stdClass
     */
    protected function get_mastery_status($section, $userid) {
        $mastery = new stdClass();
        
        // Get progress data
        $progress = $this->get_section_progress($section, $userid);
        
        // Get mastery threshold from course format options
        $format = course_get_format($section->course);
        $options = $format->get_format_options();
        $threshold = isset($options['mastery_threshold']) ? $options['mastery_threshold'] : 80;
        
        $mastery->threshold = $threshold;
        $mastery->achieved = $progress->percentage >= $threshold;
        $mastery->score = $progress->percentage;
        
        return $mastery;
    }
    
    /**
     * Get navigation data for sections
     *
     * @param \section_info $section
     * @param stdClass $course
     * @return stdClass
     */
    protected function get_section_navigation($section, $course) {
        $navigation = new stdClass();
        $navigation->previous = null;
        $navigation->next = null;
        
        // Get previous section
        if ($section->section > 1) {
            $prevsection = $section->section - 1;
            $navigation->previous = new stdClass();
            $navigation->previous->name = get_string('lesson', 'format_mcp', $prevsection);
            $navigation->previous->url = new moodle_url('/course/view.php', 
                ['id' => $course->id, 'section' => $prevsection]);
        }
        
        // Get next section
        if ($section->section < $course->numsections) {
            $nextsection = $section->section + 1;
            $navigation->next = new stdClass();
            $navigation->next->name = get_string('lesson', 'format_mcp', $nextsection);
            $navigation->next->url = new moodle_url('/course/view.php', 
                ['id' => $course->id, 'section' => $nextsection]);
        }
        
        return $navigation;
    }
    
    /**
     * Get overall navigation data
     *
     * @param stdClass $course
     * @return stdClass
     */
    protected function get_navigation_data($course) {
        $navigation = new stdClass();
        $navigation->course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $navigation->edit_url = null;
        
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $navigation->edit_url = new moodle_url('/course/view.php', 
                ['id' => $course->id, 'edit' => 'on']);
        }
        
        return $navigation;
    }
    
    /**
     * Get completion data for the course
     *
     * @param stdClass $course
     * @param int $userid
     * @return stdClass
     */
    protected function get_completion_data($course, $userid) {
        $completion_data = new stdClass();
        $completion_data->enabled = false;
        $completion_data->percentage = 0;
        
        $completion = new \completion_info($course);
        if ($completion->is_enabled()) {
            $completion_data->enabled = true;
            
            // Calculate overall course completion
            $modinfo = get_fast_modinfo($course);
            $total = 0;
            $completed = 0;
            
            foreach ($modinfo->get_cms() as $cm) {
                if ($completion->is_enabled($cm)) {
                    $total++;
                    $completiondata = $completion->get_data($cm, true, $userid);
                    
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed++;
                    }
                }
            }
            
            if ($total > 0) {
                $completion_data->percentage = round(($completed / $total) * 100);
            }
        }
        
        return $completion_data;
    }
}