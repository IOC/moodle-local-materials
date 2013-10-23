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
 * Materials lib .
 *
 * @package    local_materials
 * @copyright  2013 IOC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function add_search_form($searchquery = '') {
    $search  = html_writer::start_tag('form', array('id' => 'searchmaterialquery', 'method' => 'get'));
    $search .= html_writer::start_tag('div');
    $search .= html_writer::label(get_string('searchmaterial', 'local_materials'), 'material_search_q'); // No : in form labels!
    $search .= html_writer::empty_tag('input', array('id' => 'material_search_q',
                                                     'type' => 'text',
                                                       'name' => 'search',
                                                     'value' => $searchquery));
    $search .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('search')));
    $search .= html_writer::end_tag('div');
    $search .= html_writer::end_tag('form');
    echo $search;
}

function search_courses($searchquery = '') {
    if (!empty($searchquery)) {
        $searchcoursesparams = array();
        $searchcoursesparams['search'] = $searchquery;
        $courses = coursecat::search_courses($searchcoursesparams);
        return $courses;
    } else {
        return false;
    }
}

function get_materials($searchquery, $page) {

    global $DB;

    $materials = array();
    $params = array();

    if (empty($searchquery)) {
        $materials = $DB->get_records('local_materials');
        $total = $DB->count_records('local_materials');
        return array('records' => $materials, 'total' => $total);
    }

    if ($courses = search_courses($searchquery)) {
        $in = '(';
        foreach ($courses as $course) {
            $in .= $course->id.',';
        }
        $in = rtrim($in, ',').')';
        $wherecondition = "courseid IN $in";
    } else {
        return array();
    }

    $fields = 'SELECT *';
    $sql = " FROM {local_materials}";

    if (!empty($wherecondition)) {
        $sql .= " WHERE $wherecondition";
    }
    $order = ' ORDER BY path ASC';
    $materials = $DB->get_records_sql($fields . $sql . $order, $params, $page * PAGENUM, PAGENUM);
    $total = $DB->count_records('local_materials');
    return array('records' => $materials, 'total' => $total);
}

function create_category_list($categoryid) {
    $list = coursecat::make_categories_list();
    $select = new single_select(new moodle_url('./edit.php', array()), 'categoryid', $list, $categoryid, null, 0);
    $select->nothing = array();
    $select->set_label(get_string('isactive', 'filters'), array('class' => 'accesshide'));
    return $select;
}

function save_serialized_sources($context, $material) {
    global $DB;

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_materials', 'attachment', $material->id, "timemodified", false);
    $sources = array();
    foreach ($files as $file) {
        $sources[] = $file->get_source();
    }
    $material->sources = serialize($sources);

    $DB->update_record('local_materials', $material);
}

function make_secret_url($path) {
    global $CFG;

    $time = sprintf("%08x", time());
    $token = md5($CFG->local_materials_secret_token.'/'.$path.$time);
    $url = $CFG->local_materials_secret_url.'/'.$token.'/'.$time.'/'.$path;
    return $url;
}