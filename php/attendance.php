<?php
session_start();
require('cxn.php');

$event_id = $_REQUEST['eventID'];
$event_id = preg_replace("#[^0-9]#", "", $event_id);

if(@$_SESSION['signed_in'] == true) {
	$uid = $_SESSION['user_id'];
	$sql = "SELECT * FROM attendees 
			WHERE user_id = '$uid' 
			AND
			event_id = '$event_id'";
	$qry = mysqli_query($cxn, $sql)
		or die("failed to select ftom attendees table");
	$count = mysqli_num_rows($qry);
	//echo "count: $count";
	
	if($count == 0) {
		// user is not attending yet
		$sql = "INSERT INTO attendees
				(user_id, event_id)
				VALUES ('$uid', '$event_id')
				";
		$qry = mysqli_query($cxn, $sql)
			or die("failed to add attendance");
			
		/** Send email to host
		 * 
		 */
		$sql = "SELECT * FROM attendees WHERE event_id='$event_id'";
		$res = mysqli_query($cxn, $sql) or die("Failed to count attendees!");
		$num_attend = mysqli_num_rows($res);
		 
		 
		$sql = "SELECT email, user_id, first_name, (SELECT event_title FROM user_events WHERE 				event_id='$event_id') as event_name
				FROM user_list WHERE 
				user_id=(SELECT user_id FROM user_events 
					WHERE event_id='$event_id')";
					
		$res = mysqli_query($cxn, $sql)
			or die("Failed to add pull useremail for attendance");
		
		$row = mysqli_fetch_assoc($res);
		$owner_email = $row['email'];
		$first_name = $row['first_name'];
		$event_name = $row['event_name'];
		 
		$to = $owner_email;
		$subject = "Hi $first_name, a new user has RSVP'd for your event!!";
		$message = "The current RSVP count for event '$event_name' is now: $num_attend";
		$headers = "From: noreply@waanoo.com\r\n";
		// Or sendmail_username@hostname by default
		mail($to, $subject, $message, $headers);
		
		
		$arr = array("msg" => "you are now attending!", "status" => 2);
		echo json_encode($arr);
		}
	else if($count == 1) {
		// user already marked as attending. Remove from list.
		$sql = "DELETE FROM attendees
				WHERE  user_id = '$uid' 
				AND
				event_id = '$event_id'";
		$qry = mysqli_query($cxn, $sql)
			or die("failed to delete attendance record");

		$arr = array("msg" => "you are no longer attending", "status" => 1);
		echo json_encode($arr);
		}
	else {
		$arr = array("msg" => "more than one somehow?", "status" => 2);
		echo json_encode($arr);
		}
	}
else {
	$arr = array("msg" => "not signed in", "status" => 0);
	echo json_encode($arr);
	}

?>
