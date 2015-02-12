<?php
	include_once("connect.php");
	
	@session_start();
	
	$json = array();
	
	session_write_close();
	
	if (isset($_GET['addUser'])) {
		if (!$_SESSION['loggedIn'])
			die("not logged in");
	
		$sql = "SELECT 
				`c`.`ID` AS `id`,
				`c`.`name` AS `name`,
				`c`.`initTime` AS `initTime`,
				`p`.`joinTime` AS `joinTime`,
				`iu`.`ID` AS `iuID`,
				`iu`.`username` AS `initUser` 
			FROM `participants` AS `p` 
			INNER JOIN `conversations` AS `c` ON `p`.`conversationFK`=`c`.`ID` 
			INNER JOIN `users` AS `iu` ON `c`.`initUserFK`=`iu`.`ID` 
			WHERE `c`.`ID`=" . intval($_POST['chat']);
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		if ($row->iuID != $_SESSION['userID']) {
			$json['error'] = "nicht erlaubt";
		} else {
			$sql = "SELECT 
				`p`.`ID` AS `id`
			FROM `participants` AS `p` 
			INNER JOIN `conversations` AS `c` ON `p`.`conversationFK`=`c`.`ID` 
			INNER JOIN `users` AS `u` ON `p`.`userFK`=`u`.`ID` 
			WHERE `c`.`ID`=" . intval($_POST['chat']) . " 
			AND `u`.`username`='" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "'";
			$result = mysql_query($sql);
			
			echo mysql_error();
			
			if (!mysql_num_rows($result)) {
				$sql = "SELECT * FROM `users` WHERE `username`='" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "'";
				$result = mysql_query($sql);
				$row = mysql_fetch_object($result);
				$sql = "INSERT INTO `participants` (`conversationFK`, `userFK`, `joinTime`) 
					VALUES (" . intval($_POST['chat']) . ", " . $row->ID . ", " . time() . ")";
				$result = mysql_query($sql);
			}
			
			$json['succes'] = true;
		}
	} else if (isset($_GET['load'])) {
	
		if (!$_SESSION['loggedIn'])
			die("not loggedIn");

		$json['pid'] = $_POST['pid'];
		$json['mid'] = $_POST['mid'];
		
		$sql = "SELECT * FROM `participants` WHERE `conversationFK`=" . intval($_POST['chat']) . " AND `userFK`=" . $_SESSION['userID'];
		$result = mysql_query($sql);
		if (!mysql_num_rows($result)) {
			echo '{"nic": true}';
			exit();
		}

	
		$i = 0;
		for (; $i < 55; $i++) {
			$sql = "SELECT 
				`m`.`ID` AS `id`,
				`u`.`username` AS `username`, 
				`u`.`ID` AS `userid`,
				`m`.`sentTime` AS `time`,
				`m`.`text` AS `text`
			FROM `messages` AS `m`
			INNER JOIN `users` AS `u` ON `m`.`userFK`=`u`.`ID`
			WHERE `m`.`conversationFK`=" . intval($_POST['chat']) . "
			AND `m`.`ID`>" . intval($_POST['mid']) . " 
			ORDER BY `m`.`sentTime`";
			$resultm = mysql_query($sql);
			
			$sql = "SELECT 
				`p`.`ID` AS `id`,
				`u`.`ID` AS `userid`,
				`u`.`username` AS `username`
			FROM `participants` AS `p`
			INNER JOIN `users` AS `u` ON `p`.`userFK`=`u`.`ID`
			WHERE `p`.`conversationFK`=" . intval($_POST['chat']) . " 
			AND `p`.`ID`>" . intval($_POST['pid']);
			$resultp = mysql_query($sql);
			
			if (mysql_num_rows($resultm) || mysql_num_rows($resultp)) {
				$json['messages'] = array();
		
				while ($row = mysql_fetch_object($resultm)) {
					$tmp = array();
					$tmp['text'] = $row->text;
					$tmp['username'] = $row->username;
					$tmp['userid'] = $row->userid;
					$json['messages'][] = $tmp;
					$json['lastId'] = $row->id;
					$sql = "INSERT INTO `seenTimes` (`time`, `userFK`, `messageFK`) VALUES (" . time() . ", " . $_SESSION['userID'] . ", " . $row->id . ")";
					$result = mysql_query($sql);
				}
				
				$json['users'] = array();
				
				$sql = "SELECT 
					`p`.`ID` AS `id`,
					`u`.`ID` AS `userid`,
					`u`.`username` AS `username`
				FROM `participants` AS `p`
				INNER JOIN `users` AS `u` ON `p`.`userFK`=`u`.`ID`
				WHERE `p`.`conversationFK`=" . intval($_POST['chat']);
				$resultp = mysql_query($sql);
				
				while ($row = mysql_fetch_object($resultp)) {
					$tmp = array();
					$tmp['username'] = $row->username;
					$tmp['userid'] = $row->userid;
					$json['users'][] = $tmp;
					$json['lastPa'] = $row->id;
				}
				
				break;
			}
			usleep(30 * 1000);
		}
		if ($i >= 55) {
			$json['succes'] = false;
		} else {
			$json['succes'] = true;
		}
	} else if (isset($_GET['send'])) {
		$sql = "INSERT INTO `messages` (`userFK`, `conversationFK`, `sentTime`, `text`) 
			VALUES (" . $_SESSION['userID'] . ", " . intval($_POST['chat']) . ", " . time() . ", 
			'" . mysql_real_escape_string(htmlspecialchars($_POST['text'])) . "')";
		$result = mysql_query($sql);
		
		$json['success'] = true;
	} else if (isset($_GET['removeUser'])) {
		if (!isset($_POST['id']) || !isset($_POST['chat']))
			die();
		$id = intval($_POST['id']);
		$chat = intval($_POST['chat']);
		
		if (!$_SESSION['loggedIn'])
			die("not logged in");
		
		$sql = "SELECT * FROM `conversations` WHERE `initUserFK`=" . $_SESSION['userID'] . " AND `ID`=" . $chat;
		$result = mysql_query($sql);
		if (!mysql_num_rows($result))
			die("fatal!");
		$sql = "DELETE FROM `participants` WHERE `userFK`=" . $id . " AND `conversationFK`=" . $chat;
		$result = mysql_query($sql);
		echo mysql_error();
	}
	
	echo json_encode($json);
?>
