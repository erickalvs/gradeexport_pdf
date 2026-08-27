<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/export/grade_export_form.php');

class gradeexport_pdf_form extends grade_export_form {
    
    public function definition() {
        parent::definition();
        
        $mform = $this->_form;
        
        $mform->addElement('header', 'pdfoptions', get_string('pdfoptions', 'gradeexport_pdf'));
        
        $mform->addElement('checkbox', 'hideemptycols', get_string('hideemptycols', 'gradeexport_pdf'));
        $mform->setDefault('hideemptycols', 1); // Checked by default
    }
}
