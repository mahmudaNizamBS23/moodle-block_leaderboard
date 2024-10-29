<?php
/**
 * Block definition class for the block_pluginname plugin.
 *
 * @package   block_leaderboard
 * @copyright 2024, Brain Station-23 PLC 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//this function takes the form data,and current user id  and calls another function
function block_leaderboard_process_form(dropdown_form $mform){
    global $USER;
    $data = $mform->get_data();
    $courseid = $data->course;
    $userid = $USER->id;
    if (!(is_siteadmin($userid))) {
        // Normal logic for non-admin users
        block_leaderboard_current_user_rank($courseid, $userid);
    }
    $ranks = block_leaderboard_return_rank($courseid);
    block_leaderboard_display_leaderboard_table($ranks);
    
}

//this function will retreive the points and ranks of the current user
function block_leaderboard_current_user_rank($courseid,$userid){
    

    $rankstr = get_string('myrank', 'block_leaderboard');
    $pointsstr = get_string('points', 'block_leaderboard');
    $nextrankstr = get_string('pointsneeded', 'block_leaderboard');

    $userpoints = 0;
    $nextrank = 0;
    $myrank = 0; 
    $ranks = block_leaderboard_return_rank($courseid);
    
    
    foreach ($ranks as $rank) {
        if ($userid == $rank['userid']) {
            $myrank = $rank['rank']; 
            $userpoints = $rank['points']; 
            continue; 
        }

        // Find the next rank if userpoints are set
        if ($rank['points'] > $userpoints) {
            $nextrank = $rank['points']; 
            continue; 
        }
        
    }

    // Calculate points needed to reach the next rank
    if($nextrank != 0){
        $pointsneeded = $nextrank - $userpoints;
    }
    else{
        $pointsneeded = 0;
    }
   
    // Output the rank and points
    echo '<div class="m-5">
           <div class="card border-primary">
           <div class="card-body">
           <div class="row">
           <div class="col"><h5 class="text-primary" >' .$rankstr. $myrank . '</h5></div>
           <div class="col"><h5 class="text-primary" >' .$pointsstr. $userpoints . '</h5></div>
           <div class="col"><h5 class="text-primary" >' .$nextrankstr.$pointsneeded . '</h5></div> <!-- Show points needed -->
           </div>
           </div>
           </div>
          </div>';

}

// this function will get the userids of all the students enrolled in a specific course
function block_leaderboard_get_users($courseid){
    global $DB;
    $sql = "SELECT u.id AS userid
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON ue.enrolid = e.id
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ra.contextid = ctx.id
            JOIN {role} r ON r.id = ra.roleid
            WHERE e.courseid = $courseid AND r.shortname = 'student'  AND ctx.contextlevel = 50   GROUP BY u.id;";
    $users = $DB->get_records_sql($sql);

    return $users;
}

// this function will get the grades of each userid and calculate the points for each user
function block_leaderboard_each_users_points($users,$courseid){
    global $DB;
    $points = [];
    foreach ($users as $user){
        $userid = $user->userid;

        $userrecord = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
        $username = $userrecord ? $userrecord->firstname . ' ' . $userrecord->lastname : 'Unknown User';

        $sql = "SELECT gi.id AS gradeid, gg.finalgrade AS grade, gi.grademax AS max_grade, gg.userid as userid
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gg.itemid = gi.id
                JOIN {course_modules} cm ON gi.iteminstance = cm.instance
                JOIN {modules} m ON gi.itemmodule = m.name AND cm.module = m.id
                WHERE cm.course = :courseid AND gg.userid = :userid AND gi.grademax != ''";
        $params = ['courseid' => $courseid, 'userid' => $userid];
        $results = $DB->get_records_sql($sql, $params);
        $userpoints = 0;
        
        foreach ($results as $result){
            $userpoints = $userpoints + $result->grade;
        }
        $badges = block_leaderboard_each_users_badges($userid,$courseid);
       
        $points[] = [
            'userid' => $result->userid,
            'points' => $userpoints,
            'badges' => $badges,
            'username' => $username
        ];
        
    }
    return $points;
}
//this function will retrieve the badges of each user
function block_leaderboard_each_users_badges($userid,$courseid){
    global $DB;
    $sql =  "SELECT sb.name AS badge_name, sbp.picurl AS badge_picture
             FROM {mod_supermathbadge_user} sbu
             JOIN {supermathbadge} sb ON sbu.course = sb.course
             JOIN {mod_supermathbadge_picurl} sbp ON sb.id = sbp.supermathbadgeid
             WHERE sbu.course = $courseid AND sbu.userid = $userid;";
    $badgearr = $DB->get_records_sql($sql);
    foreach($badgearr as $badge){
        $badges[] = [
            'badge_name' => $badge->name,
            'badge_picture' => (new moodle_url($badge->badge_picture))->out(),
        ];

    }
   
    
    return $badges;
}
// this function will get the ranks of each user enrolled in a course
function block_leaderboard_return_rank ($courseid){

    $users = block_leaderboard_get_users($courseid);
    $userpoints = block_leaderboard_each_users_points($users,$courseid);
    usort($userpoints, function($a, $b) {
        return $b['points'] <=> $a['points']; 
    });
    $rankedusers = [];
    $currentrank = 1;
    //print_r($userpoints);
    for ($i = 0; $i < count($userpoints); $i++) {
        if ($i > 0 && $userpoints[$i]['points'] == $userpoints[$i - 1]['points']) {
            // Same points as the previous user, same rank
            $rankedusers[] = [
                'userid' => $userpoints[$i]['userid'],
                'points' => $userpoints[$i]['points'],
                'badges' => $userpoints[$i]['badges'] ,
                'name' => $userpoints[$i]['username'] ,
                'rank' => $rankedusers[$i - 1]['rank'] // Assign the same rank
            ];
        } else {
            // Different points, assign the current rank
            $rankedusers[] = [
                'userid' => $userpoints[$i]['userid'],
                'points' => $userpoints[$i]['points'],
                'badges' => $userpoints[$i]['badges'],
                'name' => $userpoints[$i]['username'] ,
                'rank' => $currentrank
            ];
            $currentrank++; 
        }
    }
    return $rankedusers;
} 

//this function will append a leaderboard table;
function block_leaderboard_display_leaderboard_table($rankedusers){
    global $OUTPUT;

    // Prepare data for the template.
    $templatecontext = [
        'rankedusers' => $rankedusers
    ];

    // Render and display the template.
    echo $OUTPUT->render_from_template('block_leaderboard/leaderboard', $templatecontext);



}