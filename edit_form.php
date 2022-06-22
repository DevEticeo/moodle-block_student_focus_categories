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
 * Block student_focus_categories configuration form definition 
 *
 * @package     block_student_focus_categories
 * @copyright   2022 jan Eticeo <contact@eticeo.fr>
 * @author      2022 jan Guevara Gabrielle <gabrielle.guevara@eticeo.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_student_focus_categories_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
        global $CFG, $DB, $PAGE;

       /**
         *      CSS
         */
        $PAGE->requires->css(new moodle_url("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"), true);
        $PAGE->requires->css(new moodle_url("https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.min.css"), true);

        // Load defaults.
        $blockconfig = get_config('block_student_focus_categories');

        /**
         *      Block settings
         */
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'block_student_focus_categories'));
        $mform->setDefault('config_title',  get_string('config_title_default', 'block_student_focus_categories'));
        $mform->setType('config_title', PARAM_RAW);
        
        // if Edumy theme is installed, we wil take their tool to select a icon, else we only put a text input
        if (is_file($CFG->dirroot . '/theme/edumy/ccn/font_handler/ccn_font_select.php')) {
            $ccnFontList = include($CFG->dirroot . '/theme/edumy/ccn/font_handler/ccn_font_select.php');
            $select = $mform->addElement('select', 'config_bullet_icon', get_string('config_bullet_icon', 'block_student_focus_categories'), $ccnFontList, array('class' => 'ccn_icon_class'));
            $select->setSelected(get_string('config_bullet_icon_default', 'block_student_focus_categories'));
        } else {
            $mform->addElement('text', 'config_bullet_icon', get_string('config_bullet_icon', 'block_student_focus_categories'));
            $mform->setDefault( 'config_bullet_icon', get_string('config_bullet_icon_default', 'block_student_focus_categories'));
        }
        $mform->setType('config_bullet_icon', PARAM_RAW);
        // Display progression
        $mform->addElement('selectyesno', 'config_display_progression', get_string('config_display_progression', 'block_student_focus_categories'));
        $mform->setDefault('config_display_progression', 1);


        // Display success
        $mform->addElement('selectyesno', 'config_display_success_change', get_string('config_display_success_change', 'block_student_focus_categories'));
        $mform->setDefault('config_display_success_change', 1);

        $mform->addElement('text', 'config_success_message', get_string('config_success_message', 'block_student_focus_categories'));
        $mform->setDefault('config_success_message',  get_string('config_success_message_default', 'block_student_focus_categories'));
        $mform->setType('config_success_message', PARAM_RAW);

        // restricted access by role
        $userRoleList = array(0 => get_string('everybody', 'block_student_focus_categories'));
        $userRoles = get_all_roles();
        foreach($userRoles as $role) {
            if ($role->shortname != '') {
                $userRoleList[$role->id] = $role->shortname.($role->name != '' ? ' ('.$role->name.')' : '');
            }
        }

        $select = $mform->addElement('select', 'config_user_role', get_string('config_user_role', 'block_student_focus_categories'), $userRoleList);
        $select->setSelected('5');
        $select->setMultiple(true);


        // COHORT ROLE
        $cohortList = array();
        $cohorts = $DB->get_records_sql('SELECT coho.id, coho.name FROM {cohort} coho');
        foreach($cohorts as $cohort) {
            if ($cohort->name != '') {
                $cohortList[$cohort->id] = $cohort->name;
            }
        }

        $select = $mform->addElement('select', 'config_cohorts_enabled', get_string('config_cohorts_enabled', 'block_student_focus_categories'), $cohortList, array('class' => 'ccn_icon_class'));
        $select->setMultiple(true);

        // CATEGORIES
        $eticeo_categories = isset($this->block->config->eticeo_categories) ? $this->block->config->eticeo_categories : array();
        $categories = $this->get_category_options();
        foreach ($categories as $id => $cat) {
            $default = isset($eticeo_categories[$id]) && $eticeo_categories[$id] ? $eticeo_categories[$id] : '{"order":0}';
            $mform->addElement('hidden', 'config_eticeo_categories['.$id.']', "cat ".$id);
            $mform->setDefault('config_category_'.$id.'', $default);
            $mform->setType('config_eticeo_categories['.$id.']', PARAM_RAW);
        }

        $options = array(                                                                                                           
            'class'       => 'category-select-list',
            'onchange'    => 'studentFocusCategories_addCategory()',
            'placeholder' => get_string('config_select_categories', 'block_student_focus_categories'),
        );
        $mform->addElement('select', 'config_array_categories', get_string('config_array_categories', 'block_student_focus_categories'), $categories, $options);

        //  COL ORDER
        $dataOrderOptions = array();
        $paramName = 'student_focus_categories_params';
        $configParamName = 'config_'.$paramName ;

        foreach (BLOCK_STUDENT_FOCUS_CATEGORIES_DEFAULT as $name => $num) {
            $default = isset($this->block->config->{$paramName}[$name]) ? $this->block->config->{$paramName}[$name] : $num;
            $mform->addElement('hidden', $configParamName."[".$name."]", $name);
            $mform->setDefault($configParamName."[".$name."]", $default);
            $mform->setType($configParamName."[".$name."]", PARAM_RAW);
            $dataOrderOptions[$name] = get_string($name, 'block_student_focus_categories');
        }
        ksort($dataOrderOptions);

        $options = array(
            'class'       => 'drag-and-drop-select-list dd-'.$configParamName,
            'onchange'    => 'studentFocusCategories_addOption("'.$configParamName.'")',
            'placeholder' => get_string('order_desc', 'block_student_focus_categories'),
        );
        $mform->addElement('select', 'config_student_focus_categories_columns', get_string('order_desc', 'block_student_focus_categories'), $dataOrderOptions, $options);

        /**
         *      JavaScript
         */
        // BEFORE ALL we load chosenlib
        $PAGE->requires->js_call_amd('block_student_focus_categories/chosenlib', 'init');

        // Organise access to JS.
        $jsmodule = array(
            'name' => 'block_student_focus_categories',
            'fullpath' => '/blocks/student_focus_categories/js/edit-form.js',
            'requires' => array(),
            'strings' => array(),
        );
        $PAGE->requires->js($jsmodule['fullpath'], false);

        $PAGE->requires->js(new moodle_url("https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"), true);
        $PAGE->requires->js(new moodle_url("https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.jquery.min.js"), true);

        $PAGE->requires->js(new moodle_url("https://code.jquery.com/ui/1.13.0/jquery-ui.js"), true);

        $PAGE->requires->js("/blocks/student_focus_categories/js/drag-and-drop-select.js", true);
    }

    /**
     * Return an array with all the category
     * @return array
     */
    protected function get_category_options() {
        $categoryList = core_course_category::get_all();
        
        $options = array();
        foreach ($categoryList as $category) {
            $options[$category->id] = $category->name;
        }
        
        return $options;
    }


}