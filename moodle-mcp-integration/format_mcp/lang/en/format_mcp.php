<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Language file for Modern Classroom Project course format
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name
$string['pluginname'] = 'Modern Classroom Project Format';
$string['privacy:metadata'] = 'The MCP course format plugin does not store any personal data.';

// Course format settings
$string['format_mcp'] = 'Modern Classroom Project';
$string['format_mcp_help'] = 'The Modern Classroom Project format organizes course content into structured lessons with learning objectives, pacing guides, activities, and mastery checks.';

// Section/Lesson names
$string['sectionname'] = 'Lesson';
$string['section0name'] = 'Course Introduction';
$string['lesson'] = 'Lesson {$a}';
$string['currentsection'] = 'Current lesson';
$string['newsectionname'] = 'New lesson {$a}';
$string['deletesection'] = 'Delete lesson';

// MCP Components
$string['learning_objectives'] = '🎯 Learning Objectives';
$string['pacing_guide'] = '⏳ Pacing Guide';
$string['blended_instruction'] = '📺 Blended Instruction';
$string['activities_resources'] = '📝 Activities & Resources';
$string['mastery_check'] = '✅ Mastery Check';
$string['reflection_collaboration'] = '🛠️ Reflection & Collaboration';
$string['supports_differentiation'] = '🌱 Supports & Differentiation';
$string['progress_tracking'] = '📊 Progress Tracking';

// Progress and mastery
$string['mastery_achieved'] = 'Mastery Achieved';
$string['mastery_not_achieved'] = 'Mastery Not Yet Achieved';
$string['progress_complete'] = 'Complete';
$string['progress_in_progress'] = 'In Progress';
$string['progress_not_started'] = 'Not Started';

// Time estimates
$string['estimated_time'] = 'Estimated Time';
$string['self_paced'] = 'Self-Paced';
$string['instructor_paced'] = 'Instructor-Paced';
$string['blended'] = 'Blended';

// Instructions
$string['instruction_self_directed'] = 'Self-Directed Learning';
$string['instruction_video'] = 'Video Instruction';
$string['instruction_reading'] = 'Reading Material';
$string['instruction_interactive'] = 'Interactive Content';

// Activities
$string['activity_practice'] = 'Practice Activity';
$string['activity_project'] = 'Project Work';
$string['activity_discussion'] = 'Discussion';
$string['activity_assessment'] = 'Assessment';

// Differentiation and support
$string['support_visual'] = 'Visual Learners';
$string['support_auditory'] = 'Auditory Learners';
$string['support_kinesthetic'] = 'Kinesthetic Learners';
$string['support_additional'] = 'Additional Resources';

// Navigation
$string['previous_lesson'] = 'Previous Lesson';
$string['next_lesson'] = 'Next Lesson';
$string['lesson_overview'] = 'Lesson Overview';
$string['course_progress'] = 'Course Progress';

// Error messages
$string['error_no_objectives'] = 'Learning objectives must be defined for this lesson.';
$string['error_no_mastery'] = 'Mastery criteria must be specified.';
$string['warning_incomplete'] = 'Please complete all required activities before proceeding.';

// Admin settings
$string['defaultmastery'] = 'Default mastery threshold';
$string['defaultmastery_desc'] = 'Default percentage required for mastery (1-100)';
$string['showprogressbar'] = 'Show progress bar';
$string['showprogressbar_desc'] = 'Display a progress bar at the top of each lesson';
$string['enablemastery'] = 'Enable mastery tracking';
$string['enablemastery_desc'] = 'Track and require mastery before proceeding to next lesson';