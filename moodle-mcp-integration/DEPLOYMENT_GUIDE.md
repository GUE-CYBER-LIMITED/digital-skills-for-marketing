# Azure Moodle MCP Integration - Deployment Guide

## 🚀 Quick Deployment to Your Azure Moodle Instance

This guide will help you deploy the Modern Classroom Project (MCP) course format to your existing Azure Moodle instance.

## 📋 Prerequisites

Before deployment, ensure you have:
- Admin access to your Azure Moodle instance
- FTP/SFTP access or Azure File Share access
- Moodle version 4.1 or higher
- Backup of your current Moodle installation

## 📦 Files to Deploy

The MCP course format plugin consists of these files:

```
/course/format/mcp/
├── version.php                 # Plugin version information
├── lib.php                    # Main plugin library
├── format.php                 # Course display logic
├── lang/en/format_mcp.php     # Language strings
├── classes/output/content.php # Output renderer
└── templates/section_mcp.mustache # Display template
```

## 🔧 Deployment Steps

### Step 1: Access Your Azure Moodle Instance

**Option A: Via Azure App Service Editor**
1. Go to Azure Portal → App Services → Your Moodle App
2. Select "Development Tools" → "App Service Editor"
3. Navigate to `/site/wwwroot/course/format/`

**Option B: Via FTP/SFTP**
1. Get FTP credentials from Azure Portal
2. Connect using FileZilla or similar
3. Navigate to `/course/format/`

**Option C: Via SSH (if enabled)**
```bash
ssh your-username@your-moodle-site.azurewebsites.net
cd /home/site/wwwroot/course/format/
```

### Step 2: Upload MCP Plugin Files

1. Create the `mcp` directory in `/course/format/`
2. Upload all plugin files maintaining the directory structure
3. Set appropriate permissions (755 for directories, 644 for files)

### Step 3: Install the Plugin

1. Log into your Moodle as administrator
2. Go to **Site Administration** → **Notifications**
3. Moodle will detect the new plugin and prompt for installation
4. Click **"Upgrade Moodle database now"**
5. Confirm the installation

### Step 4: Verify Installation

1. Go to **Site Administration** → **Plugins** → **Course formats**
2. Verify "Modern Classroom Project Format" appears in the list
3. Check that the status shows "Enabled"

## 🎯 Testing the MCP Format

### Create a Test Course

1. Go to **Courses** → **Add a new course**
2. Set **Course format** to "Modern Classroom Project Format"
3. Configure MCP-specific settings:
   - **Mastery threshold**: 80% (default)
   - **Show progress**: Yes
   - **Enable mastery tracking**: Yes

### Add MCP Content

1. Turn editing on in your course
2. Add a new section (this becomes a "Lesson")
3. In the section summary, structure content using MCP format:

```markdown
🎯 Learning Objectives
By the end of this lesson, you will be able to:
- Understand basic computer components
- Identify different types of software
- Navigate the Windows operating system

⏳ Pacing Guide
- Self-paced: 45-60 minutes
- Instructor-paced: 30 minutes
- Review time: 15 minutes

📺 Blended Instruction Components
- Video tutorial: "Understanding Your Computer" (15 min)
- Interactive demo: Windows navigation
- Reading material: Computer basics guide

📝 Activities & Resources Table
| Activity | Type | Time | Resources |
|----------|------|------|-----------|
| Computer Identification | Practice | 10 min | Worksheet |
| Software Exploration | Hands-on | 20 min | Lab computers |
| Navigation Quiz | Assessment | 10 min | Online quiz |

✅ Mastery Check
- Score 80% or higher on the navigation quiz
- Complete all practice activities
- Demonstrate file management skills

🛠️ Reflection & Collaboration
- Discuss challenges with classmates in forum
- Reflect on learning in personal journal
- Share tips and discoveries

🌱 Supports & Differentiation
- Visual learners: Diagrams and infographics
- Auditory learners: Narrated tutorials
- Kinesthetic learners: Hands-on practice
- Additional resources for advanced learners

📊 Progress Tracking
- Activity completion: 0/3
- Mastery achievement: Not yet achieved
- Time spent: 0 minutes
```

## 🔒 Security Considerations

### File Permissions
```bash
# Set correct permissions
find /course/format/mcp -type d -exec chmod 755 {} \;
find /course/format/mcp -type f -exec chmod 644 {} \;
```

### Database Backup
Before installation, backup your Moodle database:
```bash
# Example for MySQL
mysqldump -u username -p database_name > moodle_backup_$(date +%Y%m%d).sql
```

## 🚨 Troubleshooting

### Common Issues

**Plugin Not Detected:**
- Check file permissions and ownership
- Ensure files are in correct directory structure
- Clear Moodle cache: Site Administration → Development → Purge caches

**Installation Fails:**
- Check Moodle logs: Site Administration → Reports → Logs
- Verify Moodle version compatibility (4.1+)
- Check PHP error logs in Azure

**Display Issues:**
- Clear browser cache
- Check CSS/JS loading in browser developer tools
- Verify template syntax in `section_mcp.mustache`

### Error Log Locations

**Azure App Service Logs:**
- `/home/LogFiles/Application/`
- View in Azure Portal → Monitoring → Log stream

**Moodle Logs:**
- Site Administration → Reports → Logs
- Site Administration → Development → Debugging

## 🎨 Customization Options

### Branding
Edit the CSS in `section_mcp.mustache` to match your organization's colors:

```css
.mcp-section-header {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
}
```

### Language Support
Add additional language packs in `/lang/[language-code]/format_mcp.php`

### Additional Components
Extend the MCP components by modifying the `get_mcp_components()` function in `lib.php`

## 📊 Performance Optimization

### Azure-Specific Optimizations

1. **Enable Azure CDN** for static assets
2. **Configure caching** in Moodle:
   - Site Administration → Plugins → Caching → Configuration
3. **Optimize database** queries with Azure SQL insights
4. **Use Azure Redis Cache** for session management

### Monitoring

Monitor plugin performance:
- Azure Application Insights
- Moodle performance reports
- User activity logs

## 🔄 Migration Strategy

### Migrating Existing Courses

1. **Backup existing course** content
2. **Change course format** to MCP
3. **Restructure content** using MCP components
4. **Test thoroughly** before going live
5. **Train instructors** on new format

### Content Migration Script

For bulk migration, you can use this approach:
1. Export course backup
2. Modify course structure programmatically
3. Import modified backup
4. Verify MCP formatting

## 📞 Support and Updates

### Getting Help
- Check Moodle community forums
- Review plugin documentation
- Contact your Azure support team for infrastructure issues

### Updates
Monitor for updates to:
- Moodle core (affects plugin compatibility)
- Azure platform updates
- Plugin security patches

## ✅ Post-Deployment Checklist

- [ ] Plugin installed successfully
- [ ] Test course created with MCP format
- [ ] Sample content displays correctly
- [ ] Mobile responsiveness verified
- [ ] User permissions configured
- [ ] Backup procedures updated
- [ ] Staff training scheduled
- [ ] Monitoring enabled
- [ ] Performance baseline established

---

**Your MCP-formatted Moodle is now ready for digital skills education! 🚀**

For additional support or customization needs, refer to the main [Moodle Customization Plan](MOODLE_CUSTOMIZATION_PLAN.md).