<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Version details for Modern Classroom Project course format
 *
 * @package    format_mcp
 * @copyright  2025 Digital Skills for Marketing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025100601; // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2022112800; // Requires Moodle 4.1+
$plugin->component = 'format_mcp'; // Full name of the plugin (used for diagnostics)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
$plugin->dependencies = array();