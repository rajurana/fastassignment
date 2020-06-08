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
 * Definition of log events
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'fastassignment', 'action'=>'add', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'delete mod', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'download all submissions', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'grade submission', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'lock submission', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'reveal identities', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'revert submission to draft', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'set marking workflow state', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'submission statement accepted', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'submit', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'submit for grading', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'unlock submission', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'update', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'upload', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view all', 'mtable'=>'course', 'field'=>'fullname'),
    array('module'=>'fastassignment', 'action'=>'view confirm submit assignment form', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view grading form', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view submission', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view submission grading table', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view submit assignment form', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view feedback', 'mtable'=>'fastassignment', 'field'=>'name'),
    array('module'=>'fastassignment', 'action'=>'view batch set marking workflow state', 'mtable'=>'fastassignment', 'field'=>'name'),
);
