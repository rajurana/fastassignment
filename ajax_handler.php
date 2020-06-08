<?php

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $USER, $SESSION;

////////////////////////////////////////////
//   TRACK NUMBER OF API HITS OF STUDENTS
////////////////////////////////////////////

if(isset($_GET["api_triggered"])){
    $activity = $SESSION->activity;
    $user = $USER->id;
    $role = 5;
    $grammar_hits = $_GET["grammar_hits"];
    $autoeval_hits = $_GET["autoeval_hits"];
    
    $check_prev_records = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
    

    if(!empty($check_prev_records)) {
        $record = new stdClass();
        $record->id = $check_prev_records->id;                     // update for this where clause
        $record->user      = $user;
        $record->activity  = $activity;
        $record->role      = $role;
        $record->grammar_hits = ($check_prev_records->grammar_hits + $grammar_hits);
        $record->autoeval_hits = ($check_prev_records->autoeval_hits + $autoeval_hits);
        
        $record->created_at    = $check_prev_records->created_at;
        $record->updated_at    = time();
        
        $DB->update_record("fastassignment_api_validtn",$record);
        
         
    } else {
        $record            = new StdClass();
        $record->user      = $user;
        $record->activity  = $activity;
        $record->role      = $role;
        $record->grammar_hits  = $grammar_hits;
        $record->autoeval_hits = $autoeval_hits;
        $record->created_at    = time();
        $record->updated_at    = time();
        
        $insertquery = $DB->insert_record('fastassignment_api_validtn', $record);
    }
    
    
    // Sending remaing API left info
    $get_api_teacher = $DB->get_record_sql("SELECT maxnoofchecks, maxnoofchecksstudent, maxnoofchecksteacher FROM {fastassignment} WHERE id = $activity");
    $check_prev_records_again = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
        
        
    $data = [
        "remaining_grammar_hits" => ($get_api_teacher->maxnoofchecks - $check_prev_records_again->grammar_hits) ,
        "remaining_autoeval_hits" => ($get_api_teacher->maxnoofchecksstudent - $check_prev_records_again->autoeval_hits)
    ];
    
    echo json_encode($data);
}

////////////////////////////////////////////
//   TRACK NUMBER OF API HITS OF TEACHERS
////////////////////////////////////////////

if(isset($_GET["api_triggered_teacher"])){
    $user = $USER->id;
    $role = 3;
    $grammar_hits = $_GET["grammar_hits"];
    $autoeval_hits = $_GET["autoeval_hits"];
    $courseMod = $_GET["courseModule"];
    
    $get_activity = $DB->get_record_sql("SELECT instance FROM {course_modules} WHERE id= $courseMod");
    $activity = $get_activity->instance;
    
    $check_prev_records = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
    

    if(!empty($check_prev_records)) {
        $record = new stdClass();
        $record->id = $check_prev_records->id;                     // update for this where clause
        $record->user      = $user;
        $record->activity  = $activity;
        $record->role      = $role;
        $record->grammar_hits = ($check_prev_records->grammar_hits + $grammar_hits);
        $record->autoeval_hits = ($check_prev_records->autoeval_hits + $autoeval_hits);
        
        $record->created_at    = $check_prev_records->created_at;
        $record->updated_at    = time();
        
        $DB->update_record("fastassignment_api_validtn",$record);
        
         
    } else {
        $record            = new StdClass();
        $record->user      = $user;
        $record->activity  = $activity;
        $record->role      = $role;
        $record->grammar_hits  = $grammar_hits;
        $record->autoeval_hits = $autoeval_hits;
        $record->created_at    = time();
        $record->updated_at    = time();
        
        $insertquery = $DB->insert_record('fastassignment_api_validtn', $record);
    }
    
    
    // Sending remaing API left info
    $get_api_teacher = $DB->get_record_sql("SELECT maxnoofchecks, maxnoofchecksstudent, maxnoofchecksteacher FROM {fastassignment} WHERE id = $activity");
    $check_prev_records_again = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
        
        
    $data = [
        "remaining_grammar_hits" => ($get_api_teacher->maxnoofchecks - $check_prev_records_again->grammar_hits) ,
        "remaining_autoeval_hits" => ($get_api_teacher->maxnoofchecksstudent - $check_prev_records_again->autoeval_hits)
    ];
    
    echo json_encode($data);
}


//////////////////////////////////////////
//   GET NECESSARY DATA FOR STUDENT VIEW
//////////////////////////////////////////

if(isset($_GET["studentView"])){
    $activity = $SESSION->activity;
    $user = $USER->id;
    $role = 5;
    
    /* $get_api = $DB->get_record_sql("SELECT value FROM {config_plugins} WHERE plugin = 'fastassignment' AND name = 'apikey'");
    $admin_api_key = $get_api->value; */
    
    $get_api_teacher = $DB->get_record_sql("SELECT api_key, maxnoofchecks, maxnoofchecksstudent, maxnoofchecksteacher FROM {fastassignment} WHERE id = $activity");
    
    $test_links_sql = $DB->get_record_sql("SELECT test_links, test_name, category_name FROM {fastassignment} WHERE id = $activity");
    
    $check_user_hits = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
    
    // echo "<pre>";
    // print_r($check_user_hits->grammar_hits);die;
    
    if(!empty($check_user_hits)) { 
        $grammer_used_hits = $check_user_hits->grammar_hits;
        $autoeval_used_hits = $check_user_hits->autoeval_hits;
    } else {
        $grammer_used_hits = $autoeval_used_hits = 0;
    }
    
    if(is_siteadmin()){ $admin = 1; } else { $admin = 0; }
    
    $data = [
        "api_key" => $get_api_teacher->api_key,
        "admin" => $admin,
        "grammar_hits" => $get_api_teacher->maxnoofchecks,
        "auto_eval_student" => $get_api_teacher->maxnoofchecksstudent,
        "auto_eval_teacher" => $get_api_teacher->maxnoofchecksteacher,
        "test_links" => $test_links_sql->test_links,
        "test_name" => $test_links_sql->test_name,
        "category_name" => $test_links_sql->category_name,
        "grammer_used_hits" => $grammer_used_hits,
        "autoeval_used_hits" => $autoeval_used_hits
    ];
    
    echo json_encode($data);
    
    
}


//////////////////////////////////////////
//   GET NECESSARY DATA FOR TEACHER VIEW
//////////////////////////////////////////


if(isset($_GET["teacherView"])){
    $user = $USER->id;
    $student = $_GET["studentId"];
    $role = 3;
    $courseMod = $_GET["courseModule"];
    
    $get_activity = $DB->get_record_sql("SELECT instance FROM {course_modules} WHERE id= $courseMod");
    $activity = $get_activity->instance;
    
    
    $get_api_teacher = $DB->get_record_sql("SELECT intro, api_key, maxnoofchecks, maxnoofchecksstudent, maxnoofchecksteacher, teacheraccessibleautoeval FROM {fastassignment} WHERE id = $activity");
    
    $test_links_sql = $DB->get_record_sql("SELECT test_links, test_name, category_name FROM {fastassignment} WHERE id = $activity");
    
    $check_user_hits = $DB->get_record_sql("SELECT * FROM {fastassignment_api_validtn} WHERE activity = $activity AND user = $user AND role = $role");
    
    // Fetching submitted fast assignment of specific student
    $get_description_sql = $DB->get_record_sql(
        "SELECT id FROM {fastassignment_submission} WHERE assignment = $activity AND userid = $student"
    );
    $submission_id = $get_description_sql->id;
    
    $get_submitteddesc_sql = $DB->get_record_sql(
        "SELECT onlinetext FROM {fastasgnsubmsn_onlinetext} WHERE assignment = $activity AND submission = $submission_id"
    );
    
    if(!empty($get_submitteddesc_sql)) {
        $content = $get_submitteddesc_sql->onlinetext;
    } else {
        $content = null;
    }
	$content = preg_replace("/<br>/","\n", $content);
	$etxt = htmlspecialchars_decode($content);
	$eTxt = html_entity_decode(strip_tags($etxt));
	$etxt = preg_replace('/<p\b[^>]*>(.*?)<\/p>/i', '', $eTxt);
	$essyTxt = preg_replace("/\n\r/", "\n", $etxt);
	$essyTxt = preg_replace("/\r\n/", " ", $essyTxt);
	$submission_desc = str_replace("\xc2\xa0", "", $essyTxt);
    
    if(!empty($check_user_hits)) { 
        $grammer_used_hits = $check_user_hits->grammar_hits;
        $autoeval_used_hits = $check_user_hits->autoeval_hits;
    } else {
        $grammer_used_hits = $autoeval_used_hits = 0;
    }
    
    if(is_siteadmin()){ $admin = 1; } else { $admin = 0; }
    
    $data = [
        "api_key" => $get_api_teacher->api_key,
        "admin" => $admin,
        "grammar_hits" => $get_api_teacher->maxnoofchecks,
        "auto_eval_student" => $get_api_teacher->maxnoofchecksstudent,
        "auto_eval_teacher" => $get_api_teacher->maxnoofchecksteacher,
        "test_links" => $test_links_sql->test_links,
        "test_name" => $test_links_sql->test_name,
        "category_name" => $test_links_sql->category_name,
        "grammer_used_hits" => $grammer_used_hits,
        "autoeval_used_hits" => $autoeval_used_hits,
        "activity" => $activity,
        "teacher_permission" => $get_api_teacher->teacheraccessibleautoeval,
        "description" => $submission_desc
    ];

    echo json_encode($data);
}


////////////////////////////////////////////////
//   GET MAIN API FOR FASTASSIGNMENT SETTINGS
////////////////////////////////////////////////

if($_GET["settingsApi"]) {
    $get_api = $DB->get_record_sql("SELECT value FROM {config_plugins} WHERE plugin = 'fastassignment' AND name = 'apikey'");
    $data = ["main_api" => $admin_api_key = $get_api->value];
    echo json_encode($data);
}