# Moodle Customization Plan for Modern Classroom Project (MCP) Format

## 📋 **Executive Summary**

This document outlines a comprehensive plan to customize Moodle LMS to perfectly match your Modern Classroom Project (MCP) curriculum structure. The customization will transform your existing 31-lesson digital skills curriculum into an interactive, mastery-based learning management system.

## 🎯 **Project Overview**

**Current State:**
- 31 lessons across 9 modules
- MCP format with standardized components
- Markdown-based content structure
- Static GitHub Pages delivery

**Target State:**
- Interactive Moodle LMS with custom MCP course format
- Dynamic progress tracking and mastery-based learning
- Automated assessment and certification
- Mobile-responsive design for Windows/Android focus

## 🏗️ **Technical Architecture**

### **Core Components to Develop**

1. **Custom Course Format Plugin** (`format_mcp`)
2. **MCP Lesson Activity Module** (`mod_mcplesson`)
3. **Mastery Check Activity Module** (`mod_masterycheck`)
4. **Progress Dashboard Block** (`block_mcprogress`)
5. **Custom Theme** (`theme_digitalskills`)

---

## 📦 **Phase 1: Custom Course Format Development**

### **1.1 Course Format Plugin Structure**

```
/course/format/mcp/
├── lang/en/format_mcp.php
├── version.php
├── lib.php
├── format.php
├── renderer.php
├── classes/
│   ├── output/
│   │   ├── courseformat/
│   │   │   ├── content/
│   │   │   │   ├── section.php
│   │   │   │   └── cm.php
│   │   │   └── state/
│   │   │       └── course.php
│   │   └── section_header.php
│   └── privacy/
│       └── provider.php
├── templates/
│   ├── course_format.mustache
│   ├── section_mcp.mustache
│   ├── lesson_components.mustache
│   └── progress_tracker.mustache
├── amd/src/
│   ├── course_format.js
│   ├── mastery_tracker.js
│   └── progress_manager.js
└── styles.css
```

### **1.2 MCP Section Structure Mapping**

**Each Moodle Section = One MCP Lesson**

```php
// lib.php - MCP format class
class format_mcp extends core_courseformat\base {
    
    public function get_mcp_components() {
        return [
            'learning_objectives' => '🎯 Learning Objectives',
            'pacing_guide' => '⏳ Pacing Guide',
            'blended_instruction' => '📺 Blended Instruction',
            'activities_resources' => '📝 Activities & Resources',
            'mastery_check' => '✅ Mastery Check',
            'reflection_collaboration' => '🛠️ Reflection & Collaboration',
            'supports_differentiation' => '🌱 Supports & Differentiation',
            'progress_tracking' => '📊 Progress Tracking'
        ];
    }
    
    public function uses_sections() {
        return true;
    }
    
    public function get_section_name($section) {
        // Custom naming for lessons (Lesson 1, Lesson 2, etc.)
        if ($section->section == 0) {
            return get_string('course_introduction', 'format_mcp');
        }
        return get_string('lesson_number', 'format_mcp', $section->section);
    }
}
```

### **1.3 Custom Section Renderer**

```php
// renderer.php
class format_mcp_renderer extends core_courseformat\output\section_renderer {
    
    public function render_mcp_section($section, $course, $displayoptions = []) {
        $data = [
            'section' => $section,
            'components' => $this->get_mcp_components_for_section($section),
            'progress' => $this->get_section_progress($section),
            'mastery_status' => $this->get_mastery_status($section)
        ];
        
        return $this->render_from_template('format_mcp/section_mcp', $data);
    }
    
    private function get_mcp_components_for_section($section) {
        // Extract MCP components from section summary or activities
        return [
            'objectives' => $this->extract_objectives($section),
            'pacing' => $this->extract_pacing_guide($section),
            'instruction' => $this->extract_blended_instruction($section),
            'activities' => $this->get_section_activities($section),
            'mastery' => $this->get_mastery_activities($section),
            'reflection' => $this->get_reflection_activities($section),
            'supports' => $this->extract_supports($section),
            'progress' => $this->calculate_progress($section)
        ];
    }
}
```

---

## 🎓 **Phase 2: Custom Activity Modules**

### **2.1 MCP Lesson Activity Module**

**Purpose:** Container for lesson content with MCP structure

```php
// mod/mcplesson/lib.php
function mcplesson_add_instance($mcplesson, $mform = null) {
    global $DB;
    
    $mcplesson->timecreated = time();
    $mcplesson->timemodified = time();
    
    // Store MCP components as JSON
    $components = [
        'objectives' => $mcplesson->objectives,
        'pacing_guide' => $mcplesson->pacing_guide,
        'instruction_content' => $mcplesson->instruction_content,
        'instruction_videos' => $mcplesson->instruction_videos,
        'activities' => json_decode($mcplesson->activities_json),
        'supports' => $mcplesson->supports,
        'mastery_criteria' => $mcplesson->mastery_criteria
    ];
    
    $mcplesson->components = json_encode($components);
    
    return $DB->insert_record('mcplesson', $mcplesson);
}

function mcplesson_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}
```

### **2.2 Mastery Check Activity Module**

**Purpose:** Automated assessment with mastery thresholds

```php
// mod/masterycheck/classes/mastery_engine.php
class mastery_engine {
    
    private $mastery_threshold = 80; // 80% default
    
    public function evaluate_mastery($user_id, $activity_id, $responses) {
        $score = $this->calculate_score($responses);
        $max_score = $this->get_max_score($activity_id);
        $percentage = ($score / $max_score) * 100;
        
        $mastery_achieved = $percentage >= $this->mastery_threshold;
        
        $this->record_attempt($user_id, $activity_id, $score, $percentage, $mastery_achieved);
        
        return [
            'score' => $score,
            'percentage' => $percentage,
            'mastery_achieved' => $mastery_achieved,
            'feedback' => $this->generate_feedback($percentage, $mastery_achieved)
        ];
    }
    
    private function generate_feedback($percentage, $mastery_achieved) {
        if ($mastery_achieved) {
            return get_string('mastery_achieved', 'mod_masterycheck', $percentage);
        } else {
            return get_string('mastery_not_achieved', 'mod_masterycheck', [
                'score' => $percentage,
                'required' => $this->mastery_threshold
            ]);
        }
    }
}
```

---

## 📊 **Phase 3: Progress Tracking & Analytics**

### **3.1 Progress Dashboard Block**

```php
// blocks/mcprogress/block_mcprogress.php
class block_mcprogress extends block_base {
    
    public function init() {
        $this->title = get_string('mcp_progress', 'block_mcprogress');
    }
    
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = $this->render_progress_dashboard();
        $this->content->footer = '';
        
        return $this->content;
    }
    
    private function render_progress_dashboard() {
        global $USER, $COURSE;
        
        $progress_data = [
            'overall_progress' => $this->calculate_overall_progress($USER->id, $COURSE->id),
            'modules' => $this->get_module_progress($USER->id, $COURSE->id),
            'mastery_status' => $this->get_mastery_overview($USER->id, $COURSE->id),
            'time_spent' => $this->get_time_analytics($USER->id, $COURSE->id),
            'achievements' => $this->get_achievements($USER->id, $COURSE->id)
        ];
        
        return $this->render_from_template('block_mcprogress/dashboard', $progress_data);
    }
}
```

### **3.2 Advanced Analytics Integration**

```javascript
// amd/src/analytics_tracker.js
define(['jquery', 'core/ajax'], function($, Ajax) {
    
    var AnalyticsTracker = {
        
        init: function() {
            this.setupEventTracking();
            this.initProgressVisualization();
        },
        
        setupEventTracking: function() {
            // Track lesson interactions
            $('.mcp-objective').on('click', this.trackObjectiveView);
            $('.mcp-activity').on('complete', this.trackActivityCompletion);
            $('.mastery-check').on('submit', this.trackMasteryAttempt);
            
            // Track time spent
            this.startTimeTracking();
        },
        
        trackActivityCompletion: function(event) {
            var activityData = {
                lesson_id: event.target.dataset.lessonId,
                activity_type: event.target.dataset.activityType,
                completion_time: Date.now(),
                user_id: M.cfg.userid
            };
            
            Ajax.call([{
                methodname: 'format_mcp_track_activity',
                args: activityData
            }]);
        },
        
        generateProgressReport: function() {
            // Create visual progress reports
            this.createProgressCharts();
            this.generateMasteryMatrix();
            this.updateLearningPath();
        }
    };
    
    return AnalyticsTracker;
});
```

---

## 🎨 **Phase 4: Custom Theme Development**

### **4.1 Theme Structure**

```
/theme/digitalskills/
├── config.php
├── version.php
├── lib.php
├── settings.php
├── lang/en/theme_digitalskills.php
├── layout/
│   ├── default.php
│   ├── course.php
│   └── lesson.php
├── templates/
│   ├── mcp_lesson_layout.mustache
│   ├── progress_indicator.mustache
│   └── mastery_badge.mustache
├── scss/
│   ├── preset/
│   │   └── default.scss
│   ├── mcp_components.scss
│   ├── progress_indicators.scss
│   └── mobile_responsive.scss
└── javascript/
    ├── mcp_interactions.js
    └── progress_animations.js
```

### **4.2 MCP Component Styling**

```scss
// scss/mcp_components.scss
.mcp-lesson-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin: 2rem 0;
    
    @media (min-width: 768px) {
        grid-template-columns: 2fr 1fr;
    }
}

.mcp-component {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border-left: 4px solid;
    
    &.objectives { border-left-color: #007bff; }
    &.pacing { border-left-color: #28a745; }
    &.instruction { border-left-color: #dc3545; }
    &.activities { border-left-color: #ffc107; }
    &.mastery { border-left-color: #17a2b8; }
    &.reflection { border-left-color: #6f42c1; }
    &.supports { border-left-color: #20c997; }
    &.progress { border-left-color: #fd7e14; }
}

.progress-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    
    .progress-circle {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        
        &.completed {
            background: #28a745;
            color: white;
        }
        
        &.current {
            background: #007bff;
            color: white;
        }
        
        &.locked {
            background: #6c757d;
            color: white;
        }
    }
}

.mastery-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    
    &.achieved {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    &.not-achieved {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    &.in-progress {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
}
```

---

## 📱 **Phase 5: Mobile Optimization**

### **5.1 Responsive Design Strategy**

```scss
// Mobile-first approach for Windows/Android focus
.mcp-mobile-layout {
    @media (max-width: 768px) {
        .mcp-component {
            margin-bottom: 1rem;
            padding: 1rem;
        }
        
        .activities-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .progress-tracker {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
            padding: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
    }
}

// Touch-friendly interactions
.mcp-interactive {
    min-height: 44px; // Minimum touch target
    padding: 0.75rem;
    
    &:hover, &:focus, &:active {
        transform: scale(1.02);
        transition: transform 0.2s ease;
    }
}
```

---

## 🔧 **Phase 6: Data Migration Strategy**

### **6.1 Content Migration Scripts**

```php
// admin/tool/mcp_migration/classes/migrator.php
class mcp_content_migrator {
    
    public function migrate_curriculum($curriculum_path) {
        $modules = $this->parse_curriculum_structure($curriculum_path);
        
        foreach ($modules as $module) {
            $course_id = $this->create_moodle_course($module);
            
            foreach ($module['lessons'] as $lesson) {
                $section_id = $this->create_course_section($course_id, $lesson);
                $this->populate_mcp_components($section_id, $lesson);
                $this->create_activities($section_id, $lesson['activities']);
                $this->setup_mastery_checks($section_id, $lesson['mastery_check']);
            }
            
            $this->configure_course_completion($course_id);
        }
    }
    
    private function parse_curriculum_structure($path) {
        // Parse your existing Markdown files
        $parser = new \Parsedown();
        $modules = [];
        
        foreach (glob($path . '/*/index.md') as $module_file) {
            $module_content = file_get_contents($module_file);
            $modules[] = $this->extract_module_data($module_content, $parser);
        }
        
        return $modules;
    }
    
    private function extract_mcp_components($markdown_content) {
        // Extract MCP components using regex patterns
        $components = [];
        
        // Extract Learning Objectives
        if (preg_match('/🎯.*?Learning Objectives.*?\n(.*?)(?=⏳|$)/s', $markdown_content, $matches)) {
            $components['objectives'] = trim($matches[1]);
        }
        
        // Extract Pacing Guide
        if (preg_match('/⏳.*?Pacing Guide.*?\n(.*?)(?=📺|$)/s', $markdown_content, $matches)) {
            $components['pacing'] = trim($matches[1]);
        }
        
        // Continue for all MCP components...
        
        return $components;
    }
}
```

### **6.2 Database Schema Extensions**

```sql
-- Custom tables for MCP functionality
CREATE TABLE mdl_format_mcp_lessons (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT NOT NULL,
    section_id BIGINT NOT NULL,
    lesson_number INT NOT NULL,
    objectives TEXT,
    pacing_guide TEXT,
    instruction_content LONGTEXT,
    instruction_videos TEXT,
    activities_json LONGTEXT,
    mastery_criteria TEXT,
    supports TEXT,
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,
    INDEX idx_course_section (course_id, section_id)
);

CREATE TABLE mdl_format_mcp_progress (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    lesson_id BIGINT NOT NULL,
    component VARCHAR(50) NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed', 'mastered') DEFAULT 'not_started',
    score DECIMAL(5,2) DEFAULT NULL,
    time_spent INT DEFAULT 0,
    attempts INT DEFAULT 0,
    last_access BIGINT NOT NULL,
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,
    INDEX idx_user_course (user_id, course_id),
    INDEX idx_lesson_component (lesson_id, component)
);

CREATE TABLE mdl_format_mcp_mastery (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    lesson_id BIGINT NOT NULL,
    mastery_achieved TINYINT(1) DEFAULT 0,
    score DECIMAL(5,2) NOT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    attempt_number INT NOT NULL,
    feedback LONGTEXT,
    timecreated BIGINT NOT NULL,
    INDEX idx_user_lesson (user_id, lesson_id)
);
```

---

## 📈 **Phase 7: Implementation Timeline**

### **Week 1-2: Foundation Setup**
- [ ] Moodle installation and environment setup
- [ ] Basic custom course format plugin structure
- [ ] Theme framework development
- [ ] Database schema design

### **Week 3-4: Core Functionality**
- [ ] MCP component rendering system
- [ ] Section layout customization
- [ ] Basic progress tracking
- [ ] Mobile responsive design

### **Week 5-6: Activity Modules**
- [ ] MCP Lesson activity module
- [ ] Mastery Check activity module
- [ ] Interactive components
- [ ] Assessment engine

### **Week 7-8: Advanced Features**
- [ ] Progress dashboard block
- [ ] Analytics integration
- [ ] Notification system
- [ ] Gamification elements

### **Week 9-10: Content Migration**
- [ ] Migration script development
- [ ] Content parsing and import
- [ ] Data validation
- [ ] Testing and refinement

### **Week 11-12: Testing & Deployment**
- [ ] User acceptance testing
- [ ] Performance optimization
- [ ] Security review
- [ ] Production deployment

---

## 💰 **Cost Estimation**

### **Development Costs**
- **Custom Plugin Development**: $15,000 - $25,000
- **Theme Customization**: $5,000 - $8,000
- **Migration Scripts**: $3,000 - $5,000
- **Testing & QA**: $2,000 - $4,000
- **Total Development**: $25,000 - $42,000

### **Infrastructure Costs (Annual)**
- **Hosting (VPS/Cloud)**: $1,200 - $2,400
- **SSL Certificate**: $100 - $300
- **Backup Solutions**: $300 - $600
- **Monitoring Tools**: $200 - $500
- **Total Annual**: $1,800 - $3,800

### **Maintenance Costs (Annual)**
- **Updates & Security**: $2,000 - $4,000
- **Content Updates**: $1,000 - $2,000
- **User Support**: $1,500 - $3,000
- **Total Annual**: $4,500 - $9,000

---

## 🎯 **Success Metrics**

### **Learning Effectiveness**
- **Mastery Achievement Rate**: Target 85%+ of learners achieve mastery
- **Completion Rate**: Target 90%+ lesson completion
- **Time to Mastery**: Track average time per lesson
- **Retention Rate**: 95%+ content retention after 30 days

### **User Experience**
- **Mobile Usage**: 70%+ of access from mobile devices
- **Session Duration**: Average 15-20 minutes per lesson
- **User Satisfaction**: 4.5+ stars average rating
- **Support Tickets**: <5% of users require assistance

### **Technical Performance**
- **Page Load Time**: <3 seconds on mobile
- **Uptime**: 99.9% availability
- **Mobile Responsiveness**: Perfect scores on mobile testing
- **Accessibility**: WCAG 2.1 AA compliance

---

## 🚀 **Next Steps**

### **Immediate Actions**
1. **Environment Setup**: Install Moodle development environment
2. **Team Assembly**: Identify developers familiar with Moodle
3. **Prototype Development**: Create proof-of-concept for one lesson
4. **Stakeholder Review**: Present this plan for approval and feedback

### **Quick Win Strategy**
- Start with Phase 1 (Custom Course Format) for immediate visual impact
- Implement basic MCP component display
- Migrate 2-3 lessons as proof of concept
- Gather user feedback for refinement

### **Risk Mitigation**
- **Technical Risk**: Use Moodle's stable APIs and established patterns
- **Content Risk**: Maintain parallel static site during transition
- **User Risk**: Gradual rollout with training materials
- **Budget Risk**: Phased development with clear milestones

---

**This comprehensive plan transforms your static MCP curriculum into a dynamic, interactive learning management system while preserving the pedagogical integrity of your Modern Classroom Project format.**