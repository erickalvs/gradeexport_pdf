<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'gradeexport_pdf/institution_name',
        get_string('institution_name', 'gradeexport_pdf'),
        get_string('institution_name_desc', 'gradeexport_pdf'),
        'UNIVERSIDADE ESTADUAL DE MONTES CLAROS',
        PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
        'gradeexport_pdf/institution_subtitle',
        get_string('institution_subtitle', 'gradeexport_pdf'),
        get_string('institution_subtitle_desc', 'gradeexport_pdf'),
        'CENTRO DE EDUCAÇÃO A DISTÂNCIA',
        PARAM_TEXT
    ));
}
