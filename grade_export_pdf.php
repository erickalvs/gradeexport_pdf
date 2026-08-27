<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->libdir.'/pdflib.php');

class gradeexport_pdf_document extends pdf {
    public $institution_name;
    public $institution_subtitle;
    public $course_name;
    public $teacher_names;
    public $group_name;

    public function Header() {
        $this->SetFont('freesans', 'B', 12);
        $this->Cell(0, 6, $this->institution_name, 0, 1, 'C');
        $this->SetFont('freesans', '', 10);
        $this->Cell(0, 5, $this->institution_subtitle, 0, 1, 'C');
        $this->Ln(3);
        
        $this->SetFont('freesans', 'B', 10);
        $this->Cell(0, 5, get_string('discipline', 'gradeexport_pdf') . ': ' . $this->course_name, 0, 1, 'L');
        $this->Cell(0, 5, get_string('teacher', 'gradeexport_pdf') . ': ' . $this->teacher_names, 0, 1, 'L');
        $this->Cell(0, 5, get_string('group_label', 'gradeexport_pdf') . ': ' . $this->group_name, 0, 1, 'L');
        $this->Ln(3);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('freesans', '', 8);
        
        // Left
        $this->SetX(10);
        $this->Cell(0, 10, get_string('report_footer', 'gradeexport_pdf'), 0, 0, 'L');
        
        // Center
        $this->SetX(10);
        $date = userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig'));
        $this->Cell(0, 10, get_string('generated_at', 'gradeexport_pdf', $date), 0, 0, 'C');
        
        // Right
        $this->SetX(10);
        $str = get_string('page_of', 'gradeexport_pdf', (object)['current' => $this->getAliasNumPage(), 'total' => $this->getAliasNbPages()]);
        $this->Cell(0, 10, $str, 0, 0, 'R');
    }
}

class grade_export_pdf extends grade_export {
    public $plugin = 'pdf';
    public $hideemptycols = false;

    public function __construct($course, $groupid, $formdata) {
        parent::__construct($course, $groupid, $formdata);
        $this->usercustomfields = false;
        if (isset($formdata->hideemptycols)) {
            $this->hideemptycols = $formdata->hideemptycols;
        }
    }

    public function print_grades() {
        global $CFG, $DB;

        $export_tracking = $this->track_exports();
        
        // If this file was requested from a form, then mark download as complete
        if (class_exists('\core_form\util')) {
            \core_form\util::form_download_complete();
        }

        // Initialize PDF document
        $pdf = new gradeexport_pdf_document('L', 'mm', 'A4');
        
        // Set document metadata
        $pdf->institution_name = get_config('gradeexport_pdf', 'institution_name') ?: 'UNIVERSIDADE ESTADUAL DE MONTES CLAROS';
        $pdf->institution_subtitle = get_config('gradeexport_pdf', 'institution_subtitle') ?: 'CENTRO DE EDUCAÇÃO A DISTÂNCIA';
        $pdf->course_name = format_string($this->course->fullname);
        
        // Fetch teachers
        $context = context_course::instance($this->course->id);
        
        // Obter usuários inscritos e filtrar pelo papel de professor (editingteacher)
        $enrolled_users = get_enrolled_users($context, '', 0, 'u.*');
        $teacher_names = [];
        
        global $DB;
        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        
        if ($teacher_role) {
            $role_users = get_role_users($teacher_role->id, $context, true, 'u.id');
            foreach ($enrolled_users as $user) {
                if (array_key_exists($user->id, $role_users)) {
                    $teacher_names[] = fullname($user);
                }
            }
        }
        $pdf->teacher_names = empty($teacher_names) ? '-' : implode(', ', $teacher_names);
        


        // Set PDF layout settings
        $pdf->SetMargins(10, 45, 10); // Left, Top, Right. Top is 45 to leave space for header.
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->SetFont('freesans', '', 9);
        
        // Organize columns (items to export)
        $base_activities = [];
        if (!empty($this->columns)) {
            foreach ($this->columns as $itemid => $item) {
                $base_activities[] = $item;
            }
        }
        
        // Fetch ALL students once to avoid multiple DB calls
        $all_users = get_enrolled_users($context, 'moodle/grade:view', $this->groupid, 'u.*', 'firstname ASC, lastname ASC');
        if ($this->onlyactive) {
            $active_users = get_enrolled_users($context, 'moodle/grade:view', $this->groupid, 'u.id', null, 0, 0, true);
            $all_users = array_intersect_key($all_users, $active_users);
        }

        // Determine groups to process
        $groups_to_process = [];
        $user_group_memberships = [];
        if ($this->groupid) {
            $group = $DB->get_record('groups', ['id' => $this->groupid]);
            $groups_to_process[] = $group ? $group : (object)['id' => $this->groupid, 'name' => get_string('all_groups', 'gradeexport_pdf')];
        } else {
            $allgroups = groups_get_all_groups($this->course->id);
            if ($allgroups) {
                foreach ($allgroups as $g) {
                    $groups_to_process[] = $g;
                    $members = groups_get_members($g->id);
                    if ($members) {
                        foreach ($members as $m) {
                            $user_group_memberships[$m->id][$g->id] = true;
                        }
                    }
                }
            }
            $groups_to_process[] = (object)['id' => -1, 'name' => get_string('nogroup', 'gradeexport_pdf')];
        }

        // Calculate grades for all users beforehand to avoid multiple DB calls
        $grades_data = [];
        $no_grade_str = get_string('no_grade', 'gradeexport_pdf');
        foreach ($all_users as $user) {
            $user_grades = [];
            foreach ($base_activities as $item) {
                $grade = grade_grade::fetch(array('itemid' => $item->id, 'userid' => $user->id));
                if ($grade) {
                    $grade->grade_item = $item; // Inject item to avoid DB calls inside format_gradevalue
                    
                    // Fallback to real if displaytype is an array just in case
                    $dtype = is_array($this->displaytype) ? reset($this->displaytype) : $this->displaytype;
                    
                    $formatted_grade = grade_format_gradevalue($grade->finalgrade, $item, true, $dtype, $this->decimalpoints);
                    if ($grade->finalgrade === null) {
                        $formatted_grade = $no_grade_str;
                    } else {
                        $formatted_grade = strip_tags($formatted_grade); // ensure pure text for PDF
                    }
                } else {
                    $formatted_grade = $no_grade_str;
                }
                $user_grades[$item->id] = $formatted_grade;
            }
            $grades_data[$user->id] = $user_grades;
        }

        $rendered_any = false;

        foreach ($groups_to_process as $current_group) {
            $current_group_id = $current_group->id;
            
            // Filter users for this group
            $users = [];
            if ($this->groupid) {
                $users = $all_users;
            } else {
                if ($current_group_id == -1) {
                    foreach ($all_users as $uid => $u) {
                        if (empty($user_group_memberships[$uid])) {
                            $users[$uid] = $u;
                        }
                    }
                } else {
                    foreach ($all_users as $uid => $u) {
                        if (!empty($user_group_memberships[$uid][$current_group_id])) {
                            $users[$uid] = $u;
                        }
                    }
                }
            }
            
            // Skip empty groups unless it's the only one we're processing
            if (empty($users) && count($groups_to_process) > 1) {
                continue;
            }
            
            $rendered_any = true;
            $pdf->group_name = format_string($current_group->name);
            $activities = $base_activities;
            
            // Hide empty columns if requested
            if (!empty($this->hideemptycols)) {
                $filtered_activities = [];
                foreach ($activities as $item) {
                    $has_grade = false;
                    foreach ($users as $user) {
                        if ($grades_data[$user->id][$item->id] !== $no_grade_str) {
                            $has_grade = true;
                            break;
                        }
                    }
                    if ($has_grade) {
                        $filtered_activities[] = $item;
                    }
                }
                $activities = $filtered_activities;
            }
            
            $slice_size = 15; // Max activities per page slice
            $chunks = array_chunk($activities, $slice_size);
            
            // If activities are empty after filtering but we have students
            if (empty($chunks)) {
                $chunks = [[]]; // Create an empty chunk just to render names
            }

            // Draw tables
            $w_num = 10;
            $w_name = 60;
            $remaining_width = 277 - $w_num - $w_name;
            
            foreach ($chunks as $chunk_idx => $chunk) {
                $pdf->AddPage();
                
                $col_count = count($chunk);
                $w_col = $col_count > 0 ? $remaining_width / $col_count : 0;
                
                // Calculate max height for the header row
                $pdf->SetFont('freesans', 'B', 9);
                $max_h = 10; // minimum height
                
                // Check height for all columns in this chunk
                foreach ($chunk as $item) {
                    $itemname = format_string($item->get_name());
                    if ($item->itemtype == 'course') {
                        $itemname = get_string('course_total', 'gradeexport_pdf');
                    }
                    // getStringHeight returns the height needed for the text at current font & width
                    $h = $pdf->getStringHeight($w_col, $itemname);
                    if ($h > $max_h) {
                        $max_h = $h;
                    }
                }
                $max_h += 2; // Add a little vertical padding
                
                // Draw table header using MultiCell
                $pdf->MultiCell($w_num, $max_h, get_string('student_number', 'gradeexport_pdf'), 1, 'C', false, 0, '', '', true, 0, false, true, $max_h, 'M');
                $pdf->MultiCell($w_name, $max_h, get_string('student_name', 'gradeexport_pdf'), 1, 'C', false, 0, '', '', true, 0, false, true, $max_h, 'M');
                
                foreach ($chunk as $item) {
                    $itemname = format_string($item->get_name());
                    if ($item->itemtype == 'course') {
                        $itemname = get_string('course_total', 'gradeexport_pdf');
                    }
                    
                    // MultiCell wraps text. $ln=0 moves cursor to the right
                    $pdf->MultiCell($w_col, $max_h, $itemname, 1, 'C', false, 0, '', '', true, 0, false, true, $max_h, 'M');
                }
                $pdf->Ln();
                
                // Draw table body
                $pdf->SetFont('freesans', '', 9);
                $counter = 1;
                foreach ($users as $user) {
                    $name = fullname($user);
                    
                    $pdf->Cell($w_num, 6, $counter++, 1, 0, 'C');
                    $pdf->Cell($w_name, 6, $name, 1, 0, 'L', false, '', 1);
                    
                    foreach ($chunk as $item) {
                        $g = $grades_data[$user->id][$item->id];
                        $pdf->Cell($w_col, 6, $g, 1, 0, 'C', false, '', 1);
                    }
                    $pdf->Ln();
                }
            }
        }
        
        if (!$rendered_any) {
            $pdf->group_name = get_string('all_groups', 'gradeexport_pdf');
            $pdf->AddPage();
            $pdf->Cell(0, 10, get_string('no_students', 'gradeexport_pdf'), 0, 1, 'C');
        }

        $this->output_pdf($pdf);
    }
    
    private function output_pdf($pdf) {
        $shortname = format_string($this->course->shortname, true, array('context' => context_course::instance($this->course->id)));
        $filename = 'notas_' . clean_filename($shortname) . '_' . date('Ymd_Hi') . '.pdf';
        
        $pdf->Output($filename, 'D');
    }
}
