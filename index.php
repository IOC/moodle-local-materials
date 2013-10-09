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
 * Materials main page.
 *
 * @package    local_materials
 * @copyright  2013 IOC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("PAGENUM", "20");

require_once(dirname(__FILE__) . '/../../config.php');

require_login();

$categoryid = optional_param('categoryid', 1, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery = optional_param('search', '', PARAM_RAW);

$context = context_system::instance();
require_capability('local/materials:manage', $context);

$strheading = get_string('plugin_pluginname', 'local_materials');

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/local/materials/index.php'));
$PAGE->set_title($strheading);
$PAGE->add_body_class('path-admin');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add(get_string('plugin_pluginname', 'local_materials'));
$PAGE->navbar->add($strheading, new moodle_url('/local/materials/index.php'));

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

// Add search form.
$search  = html_writer::start_tag('form', array('id' => 'searchmaterialquery', 'method' => 'get'));
$search .= html_writer::start_tag('div');
$search .= html_writer::label(get_string('searchmaterial', 'local_materials'), 'material_search_q'); // No : in form labels!
$search .= html_writer::empty_tag('input', array(
    'id' => 'material_search_q',
    'type' => 'text',
    'name' => 'search',
    'value' => $searchquery));
$search .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('search')));
$search .= html_writer::end_tag('div');
$search .= html_writer::end_tag('form');
echo $search;

if (!empty($searchquery)) {
    $searchcoursesparams = array();
    $searchcoursesparams['search'] = $searchquery;
    $courses = coursecat::search_courses($searchcoursesparams);
}

if (isset($courses) && !empty($courses)) {
    $in = '(';
    foreach ($courses as $course) {
        $in .= $course->id.',';
    }
    $in = rtrim($in, ',').')';
}

$materials = array();
$params = array();
if (!empty($searchquery)) {
    $conditions = array(
       'path',
    );
    $searchparam = '%' . $searchquery . '%';
    foreach ($conditions as $key => $condition) {
        $conditions[$key] = $DB->sql_like($condition, "?", false);
        $params[] = $searchparam;
    }
    $tests[] = '(' . implode(' OR ', $conditions) . ')';

    $wherecondition = implode(' AND ', $tests);
    if (isset($in) && !empty($in)) {
        $wherecondition .= " OR courseid IN $in";
    }
}

$fields = 'SELECT *';
$sql = " FROM {local_materials}";

if (!empty($wherecondition)) {
    $sql .= " WHERE $wherecondition";
}
$order = ' ORDER BY path ASC';

$materials = $DB->get_records_sql($fields . $sql . $order, $params, $page * PAGENUM, PAGENUM);
$totalmaterials = $DB->count_records('local_materials');

echo $OUTPUT->paging_bar($totalmaterials, $page, PAGENUM, new moodle_url('/local/materials/index.php'));

$data = array();

if ($materials) {
    foreach ($materials as $material) {
        $line = array();
        $course = $DB->get_record('course', array('id' => $material->courseid));
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $line[] = html_writer::link($courseurl, $course->shortname);
        $line[] = html_writer::link($courseurl, $course->fullname);

        $line[] = format_text($material->path);
        $buttons = array();
        $editlink = new moodle_url('./edit.php', array('id' => $material->id, 'categoryid' => $course->category));
        $editicon = html_writer::empty_tag('img',
            array('src' => $OUTPUT->pix_url('t/edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall'));
        $deletelink = new moodle_url('./edit.php', array('id' => $material->id, 'categoryid' => $course->category, 'delete' => 1));
        $deleteicon = html_writer::empty_tag('img',
            array('src' => $OUTPUT->pix_url('t/delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall'));
        $buttons[] = html_writer::link($editlink, $editicon);
        $buttons[] = html_writer::link($deletelink, $deleteicon);
        $line[] = implode(' ', $buttons);
        $data[] = $line;
    }
}

$table = new html_table();
$table->head = array(get_string('shortname'), get_string('course'), get_string('path'), get_string('edit'));
$table->data = $data;
$table->id = 'materials';
$table->attributes['class'] = 'admintable generaltable';
echo html_writer::table($table);
echo $OUTPUT->paging_bar($totalmaterials, $page, PAGENUM, new moodle_url('/local/materials/index.php'));

echo $OUTPUT->single_button(new moodle_url('./edit.php', array('categoryid' => $categoryid)), get_string('add'));
echo $OUTPUT->footer();

