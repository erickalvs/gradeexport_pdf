<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_pdf.php';
require_once 'gradeexport_pdf_form.php';

$id = required_param('id', PARAM_INT); // course id
$PAGE->set_url('/grade/export/pdf/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/pdf:view', $context);

$actionbar = new \core_grades\output\export_action_bar($context, null, 'pdf');
print_grade_page_head($COURSE->id, 'export', 'pdf',
    get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_pdf'),
    false, false, true, null, null, null, $actionbar);

$actionurl = new moodle_url('/grade/export/pdf/export.php');
$features = array(
    'idnumberrequired' => false, 
    'simpleui' => true,
    'multipledisplaytypes' => false,
    'publishing' => false
);
$mform = new gradeexport_pdf_form($actionurl, $features);

$groupmode = groups_get_course_groupmode($course);
$currentgroup = groups_get_course_group($course, true);

if (($groupmode == SEPARATEGROUPS) && (!$currentgroup) && (!has_capability('moodle/site:accessallgroups', $context))) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

$mform->display();
echo $OUTPUT->footer();
