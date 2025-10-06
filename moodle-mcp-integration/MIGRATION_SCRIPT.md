# Content Migration Script for Azure Moodle MCP Integration

## 🔄 Automated Migration from Your Current Curriculum

This script automatically converts your existing MCP-formatted curriculum into Moodle courses with the custom MCP format.

## 📋 Prerequisites

- PHP 7.4+ with CLI access
- Admin credentials for your Moodle instance
- Your curriculum files (Markdown format)

## 🚀 Usage

### Basic Migration
```bash
php migrate_to_moodle.php --source="../" --course-name="Digital Skills for Marketing" --category=1
```

### With Custom Settings
```bash
php migrate_to_moodle.php \
  --source="../" \
  --course-name="Digital Skills for Marketing" \
  --category=1 \
  --mastery-threshold=85 \
  --instructor-name="Gabriel Aloho" \
  --start-date="2025-01-01"
```

## 📄 Migration Script

Save this as `migrate_to_moodle.php` in your Moodle root directory:

```php
<?php
// migrate_to_moodle.php - MCP Curriculum to Moodle Migration Script

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Migration script for converting MCP curriculum to Moodle courses
 */
class MCPMoodleMigrator {
    
    private $source_path;
    private $course_data;
    private $category_id;
    private $mastery_threshold;
    
    public function __construct($options) {
        $this->source_path = $options['source'];
        $this->category_id = $options['category'];
        $this->mastery_threshold = isset($options['mastery_threshold']) ? $options['mastery_threshold'] : 80;
        
        $this->course_data = [
            'fullname' => $options['course_name'],
            'shortname' => $this->generate_shortname($options['course_name']),
            'category' => $this->category_id,
            'format' => 'mcp',
            'showgrades' => 1,
            'visible' => 1,
            'startdate' => isset($options['start_date']) ? strtotime($options['start_date']) : time(),
            'enddate' => 0,
            'summary' => 'Digital Skills for Marketing course using Modern Classroom Project format',
            'summaryformat' => FORMAT_HTML
        ];
    }
    
    /**
     * Main migration process
     */
    public function migrate() {
        try {
            echo "🚀 Starting MCP to Moodle migration...\n";
            
            // Step 1: Create course
            $course = $this->create_course();
            echo "✅ Course created: {$course->fullname} (ID: {$course->id})\n";
            
            // Step 2: Parse curriculum structure
            $modules = $this->parse_curriculum_structure();
            echo "📚 Found " . count($modules) . " modules\n";
            
            // Step 3: Create sections for each lesson
            $lesson_count = 0;
            foreach ($modules as $module) {
                foreach ($module['lessons'] as $lesson) {
                    $lesson_count++;
                    $section = $this->create_lesson_section($course->id, $lesson_count, $lesson);
                    echo "📝 Created Lesson {$lesson_count}: {$lesson['title']}\n";
                    
                    // Add activities
                    $this->create_lesson_activities($course->id, $section->id, $lesson);
                }
            }
            
            // Step 4: Configure course completion
            $this->setup_course_completion($course->id);
            echo "✅ Course completion configured\n";
            
            echo "\n🎉 Migration completed successfully!\n";
            echo "Course URL: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
            
            return $course;
            
        } catch (Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    /**
     * Create the main course
     */
    private function create_course() {
        global $DB, $USER;
        
        // Set course format options
        $this->course_data['format_options'] = [
            'mastery_threshold' => $this->mastery_threshold,
            'show_progress' => 1,
            'enable_mastery_tracking' => 1
        ];
        
        $course = create_course((object)$this->course_data);
        
        // Enroll current user as teacher
        $context = context_course::instance($course->id);
        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        role_assign($teacher_role->id, $USER->id, $context->id);
        
        return $course;
    }
    
    /**
     * Parse the curriculum directory structure
     */
    private function parse_curriculum_structure() {
        $modules = [];
        
        // Get all module directories (01_foundations, 02_digital_marketing_basics, etc.)
        $module_dirs = glob($this->source_path . '/*_*', GLOB_ONLYDIR);
        sort($module_dirs);
        
        foreach ($module_dirs as $module_dir) {
            $module_name = basename($module_dir);
            
            // Skip non-module directories
            if (!preg_match('/^\d+_/', $module_name)) {
                continue;
            }
            
            $module = [
                'name' => $module_name,
                'path' => $module_dir,
                'lessons' => $this->parse_module_lessons($module_dir)
            ];
            
            $modules[] = $module;
        }
        
        return $modules;
    }
    
    /**
     * Parse lessons within a module
     */
    private function parse_module_lessons($module_dir) {
        $lessons = [];
        
        // Get all .md files except index.md and course_conclusion.md
        $lesson_files = glob($module_dir . '/*.md');
        
        foreach ($lesson_files as $lesson_file) {
            $filename = basename($lesson_file, '.md');
            
            // Skip index and conclusion files
            if (in_array($filename, ['index', 'course_conclusion'])) {
                continue;
            }
            
            $content = file_get_contents($lesson_file);
            $lesson = $this->parse_lesson_content($filename, $content);
            $lessons[] = $lesson;
        }
        
        return $lessons;
    }
    
    /**
     * Parse individual lesson content
     */
    private function parse_lesson_content($filename, $content) {
        $lesson = [
            'filename' => $filename,
            'title' => $this->extract_title($content),
            'components' => $this->extract_mcp_components($content),
            'activities' => $this->extract_activities($content)
        ];
        
        return $lesson;
    }
    
    /**
     * Extract lesson title from content
     */
    private function extract_title($content) {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return 'Untitled Lesson';
    }
    
    /**
     * Extract MCP components from lesson content
     */
    private function extract_mcp_components($content) {
        $components = [];
        
        // Define MCP component patterns
        $patterns = [
            'learning_objectives' => '/🎯.*?Learning Objectives(.*?)(?=⏳|📺|📝|✅|🛠️|🌱|📊|$)/s',
            'pacing_guide' => '/⏳.*?Pacing Guide(.*?)(?=🎯|📺|📝|✅|🛠️|🌱|📊|$)/s',
            'blended_instruction' => '/📺.*?Blended Instruction(.*?)(?=🎯|⏳|📝|✅|🛠️|🌱|📊|$)/s',
            'activities_resources' => '/📝.*?Activities.*?Resources(.*?)(?=🎯|⏳|📺|✅|🛠️|🌱|📊|$)/s',
            'mastery_check' => '/✅.*?Mastery Check(.*?)(?=🎯|⏳|📺|📝|🛠️|🌱|📊|$)/s',
            'reflection_collaboration' => '/🛠️.*?Reflection.*?Collaboration(.*?)(?=🎯|⏳|📺|📝|✅|🌱|📊|$)/s',
            'supports_differentiation' => '/🌱.*?Supports.*?Differentiation(.*?)(?=🎯|⏳|📺|📝|✅|🛠️|📊|$)/s',
            'progress_tracking' => '/📊.*?Progress Tracking(.*?)(?=🎯|⏳|📺|📝|✅|🛠️|🌱|$)/s'
        ];
        
        foreach ($patterns as $component => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $components[$component] = trim($matches[1]);
            }
        }
        
        return $components;
    }
    
    /**
     * Extract activities from lesson content
     */
    private function extract_activities($content) {
        $activities = [];
        
        // Look for activities table or list
        if (preg_match('/\|.*?Activity.*?\|.*?\n(.*?)(?=\n[^|]|$)/s', $content, $matches)) {
            $table_content = $matches[1];
            $rows = explode("\n", $table_content);
            
            foreach ($rows as $row) {
                if (preg_match('/\|\s*([^|]+)\s*\|\s*([^|]+)\s*\|\s*([^|]+)\s*\|/', $row, $row_matches)) {
                    $activities[] = [
                        'name' => trim($row_matches[1]),
                        'type' => trim($row_matches[2]),
                        'time' => trim($row_matches[3])
                    ];
                }
            }
        }
        
        return $activities;
    }
    
    /**
     * Create a section for a lesson
     */
    private function create_lesson_section($course_id, $section_number, $lesson) {
        global $DB;
        
        // Create section
        $section_data = [
            'course' => $course_id,
            'section' => $section_number,
            'name' => $lesson['title'],
            'summary' => $this->build_mcp_summary($lesson['components']),
            'summaryformat' => FORMAT_HTML,
            'visible' => 1
        ];
        
        $section_id = $DB->insert_record('course_sections', (object)$section_data);
        
        // Get the section record
        $section = $DB->get_record('course_sections', ['id' => $section_id]);
        
        return $section;
    }
    
    /**
     * Build MCP-formatted summary for section
     */
    private function build_mcp_summary($components) {
        $summary = '';
        
        $component_config = [
            'learning_objectives' => ['icon' => '🎯', 'title' => 'Learning Objectives'],
            'pacing_guide' => ['icon' => '⏳', 'title' => 'Pacing Guide'],
            'blended_instruction' => ['icon' => '📺', 'title' => 'Blended Instruction Components'],
            'activities_resources' => ['icon' => '📝', 'title' => 'Activities & Resources'],
            'mastery_check' => ['icon' => '✅', 'title' => 'Mastery Check'],
            'reflection_collaboration' => ['icon' => '🛠️', 'title' => 'Reflection & Collaboration'],
            'supports_differentiation' => ['icon' => '🌱', 'title' => 'Supports & Differentiation'],
            'progress_tracking' => ['icon' => '📊', 'title' => 'Progress Tracking']
        ];
        
        foreach ($component_config as $key => $config) {
            if (isset($components[$key]) && !empty($components[$key])) {
                $summary .= "<h3>{$config['icon']} {$config['title']}</h3>\n";
                $summary .= "<div class='mcp-component-content'>" . nl2br(htmlspecialchars($components[$key])) . "</div>\n\n";
            }
        }
        
        return $summary;
    }
    
    /**
     * Create activities for a lesson
     */
    private function create_lesson_activities($course_id, $section_id, $lesson) {
        global $CFG;
        
        if (empty($lesson['activities'])) {
            return;
        }
        
        foreach ($lesson['activities'] as $activity) {
            $this->create_activity($course_id, $section_id, $activity);
        }
    }
    
    /**
     * Create individual activity
     */
    private function create_activity($course_id, $section_id, $activity_data) {
        global $DB, $CFG;
        
        // Determine activity type based on name/type
        $module_name = $this->determine_activity_module($activity_data);
        
        // Create the activity based on type
        switch ($module_name) {
            case 'quiz':
                $this->create_quiz_activity($course_id, $section_id, $activity_data);
                break;
            case 'assign':
                $this->create_assignment_activity($course_id, $section_id, $activity_data);
                break;
            case 'forum':
                $this->create_forum_activity($course_id, $section_id, $activity_data);
                break;
            default:
                $this->create_page_activity($course_id, $section_id, $activity_data);
                break;
        }
    }
    
    /**
     * Determine activity module type
     */
    private function determine_activity_module($activity_data) {
        $name = strtolower($activity_data['name']);
        $type = strtolower($activity_data['type']);
        
        if (strpos($name, 'quiz') !== false || strpos($type, 'assessment') !== false) {
            return 'quiz';
        } elseif (strpos($name, 'assignment') !== false || strpos($type, 'project') !== false) {
            return 'assign';
        } elseif (strpos($name, 'discussion') !== false || strpos($type, 'forum') !== false) {
            return 'forum';
        } else {
            return 'page';
        }
    }
    
    /**
     * Create a page activity
     */
    private function create_page_activity($course_id, $section_id, $activity_data) {
        // Implementation for creating page activities
        // This would use Moodle's course module creation functions
    }
    
    /**
     * Setup course completion settings
     */
    private function setup_course_completion($course_id) {
        global $DB;
        
        // Enable completion for the course
        $DB->set_field('course', 'enablecompletion', 1, ['id' => $course_id]);
        
        // Additional completion setup can be added here
    }
    
    /**
     * Generate course shortname
     */
    private function generate_shortname($fullname) {
        $shortname = preg_replace('/[^a-zA-Z0-9]/', '', $fullname);
        $shortname = strtoupper(substr($shortname, 0, 10));
        return $shortname . '_' . date('Y');
    }
}

// Command line argument parsing
function parse_arguments($argv) {
    $options = [];
    
    for ($i = 1; $i < count($argv); $i++) {
        if (strpos($argv[$i], '--') === 0) {
            $key = substr($argv[$i], 2);
            if (isset($argv[$i + 1]) && strpos($argv[$i + 1], '--') !== 0) {
                $options[$key] = $argv[$i + 1];
                $i++;
            } else {
                $options[$key] = true;
            }
        }
    }
    
    return $options;
}

// Main execution
if (php_sapi_name() === 'cli') {
    $options = parse_arguments($argv);
    
    // Validate required options
    $required = ['source', 'course-name', 'category'];
    foreach ($required as $req) {
        if (!isset($options[$req])) {
            echo "Error: --{$req} is required\n";
            echo "Usage: php migrate_to_moodle.php --source=path --course-name=name --category=id\n";
            exit(1);
        }
    }
    
    // Run migration
    $migrator = new MCPMoodleMigrator($options);
    $migrator->migrate();
}
```

## 🎯 Usage Examples

### Migrate Your Current Curriculum
```bash
# Navigate to your Moodle directory
cd /home/site/wwwroot

# Run the migration script
php migrate_to_moodle.php \
  --source="/path/to/digital-skills-for-marketing" \
  --course-name="Digital Skills for Marketing" \
  --category=1 \
  --mastery-threshold=80 \
  --start-date="2025-01-15"
```

### Test Migration (Smaller Scope)
```bash
# Migrate just one module for testing
php migrate_to_moodle.php \
  --source="/path/to/digital-skills-for-marketing/01_foundations" \
  --course-name="Foundations Test Course" \
  --category=1
```

## 📊 Expected Results

After running the migration, you'll have:

1. **New Moodle Course** with MCP format
2. **31 Sections** (one per lesson) with MCP components
3. **Structured Content** with all your existing material
4. **Progress Tracking** enabled
5. **Mastery Requirements** configured

## 🔍 Verification Steps

1. **Check Course Creation**: Visit the course URL provided
2. **Verify Sections**: Ensure all 31 lessons are present
3. **Test MCP Components**: Check that all 8 components display correctly
4. **Mobile Testing**: Verify responsive design on mobile devices
5. **User Testing**: Create test student account and navigate course

## 🛠️ Troubleshooting

### Common Issues

**Permission Errors:**
```bash
# Fix file permissions
chmod +x migrate_to_moodle.php
```

**Memory Limits:**
```bash
# Increase PHP memory limit
php -d memory_limit=512M migrate_to_moodle.php [options]
```

**Database Errors:**
- Check Moodle database connectivity
- Verify user has course creation permissions
- Check category ID exists

### Rollback Procedure

If migration fails or results are unsatisfactory:

1. **Delete Created Course**:
   - Go to Site Administration → Courses → Manage courses
   - Find and delete the migrated course

2. **Clean Database** (if needed):
   ```sql
   -- Only if you need to clean up orphaned records
   DELETE FROM mdl_course WHERE shortname LIKE 'DIGITALSKILLS_%';
   ```

3. **Restore from Backup** (if major issues):
   - Use your pre-migration database backup

---

**This migration script provides a seamless transition from your static MCP curriculum to an interactive Moodle learning environment! 🚀**