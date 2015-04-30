<?php

/**
 * Automatically create a username according to a given pattern
 * Default: username of creator plus prefix
 * 
 * @param  $creator string user who creates new user
 * @return string username
 */
function block_userprofile_update_create_username($creator) {
	(string)$username = '';
	(array)$existingusers = array();
	(array)$usernumber = array();
	
	// check which usernames already exists with pattern
	$existingusers = block_userprofile_update_get_matchingusers($creator->username);
	if(!empty($existingusers)){
		foreach($existingusers as $user){
			$success = preg_match('/m(\d+?)_.+?/', $user->username, $matches);
			if($success){
				$usernumber[] = $matches[1];
			}
		}
	}
	if(!empty($usernumber)){
		$newusernumber = max($usernumber) + 1;
		$username = 'm' . $newusernumber . "_" . $creator->username;
	} else {
		$username = 'm1_'. $creator->username;
	}
	// define new username
	return $username;
}

/**
 * get all users who match a username pattern and return them as array
 * @param string $username
 * @return array users indexed by user id
 */
function block_userprofile_update_get_matchingusers($username) {
	global $DB;
	$sql = 'username LIKE \'m%' . $username . "'";
	$existingusers = $DB->get_records_select('user', $sql);
	return $existingusers;
}
