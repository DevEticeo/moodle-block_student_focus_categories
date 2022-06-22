<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block student_focus_categories settings
 *
 * @package     block_student_focus_categories
 * @copyright   2022 jan Eticeo <contact@eticeo.fr>
 * @author      2022 jan Guevara Gabrielle <gabrielle.guevara@eticeo.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$title = get_string('config_preview_image', 'block_student_focus_categories');
$description = get_string('config_preview_image_description', 'block_student_focus_categories');

$setting = new admin_setting_configstoredfile('block_student_focus_categories/image', $title, $description, 'content', 0, ['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]);
$setting->set_updatedcallback('theme_reset_all_caches');
$settings->add($setting);
