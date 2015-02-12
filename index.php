<?php
	include_once("connect.php");
	
	ob_start();

	@session_start();
	
?>
	<style>
		body {
		}
	</style>
<?php

	if (!isset($_SESSION['active'])) {
		$_SESSION['active'] = true;
		$_SESSION['loggedIn'] = false;
		$_SESSION['userID'] = 0;
	}
	
	if (!isset($_GET['site'])) {
		header("LOCATION: ?site=home");
		exit();
	}
	
	switch($_GET['site']) {
	case "home":
		if ($_SESSION['loggedIn']) {
?>
	<a href="?site=conversations">Eine Liste der Konversationen</a>
<?php
		} else {
?>
	<a href="?site=login">Login</a><br />
	<a href="?site=register">Noch keinen Account?</a>
<?php
		}
		break;
	case "login":
		if ($_SESSION['loggedIn']) {
			header("LOCATION: ?site=home");
			exit();
		}
		if (isset($_GET['sent'])) {
			if (!isset($_POST['username']) || empty($_POST['username'])) {
				header("LOCATION: ?site=login&error=username");
				exit();
			}
			if (!isset($_POST['password']) || empty($_POST['password'])) {
				header("LOCATION: ?site=login&error=password");
				exit();
			}
			
			$sql = "SELECT `ID` FROM `users` WHERE `username`='" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "' AND `password`='" . hash("sha256", $_POST['password']) . "'";
			$result = mysql_query($sql);
			if (!mysql_num_rows($result)) {
				header("LOCATION: ?site=login&error=wrong");
				exit();
			}
			
			$_SESSION['loggedIn'] = true;
			$row = mysql_fetch_object($result);
			$_SESSION['userID'] = $row->ID;
			
			header("LOCATION: ?site=home");
			exit();
		}
		if (isset($_GET['error']))
			echo "<div class='error'>Error Typ: " . htmlspecialchars($_GET['error']) . "</div>";
?>
	<form action="?site=login&sent" method="POST">
		<input type="text" name="username"><br />
		<input type="password" name="password"><br />
		<input type="submit">
	</form>
<?php
		break;
	case "register":
		if ($_SESSION['loggedIn']) {
			header("LOCATION: ?site=home");
			exit();
		}
		if (isset($_GET['sent'])) {
			if (!isset($_POST['username']) || empty($_POST['username'])) {
				header("LOCATION: ?site=register&error=username");
				exit();
			}
			if (!isset($_POST['password']) || empty($_POST['password'])) {
				header("LOCATION: ?site=register&error=password");
				exit();
			}
			
			$sql = "SELECT `ID` FROM `users` WHERE `username`='" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "'";
			$result = mysql_query($sql);
			if (mysql_num_rows($result)) {
				header("LOCATION: ?site=register&error=existing");
				exit();
			}
			$sql = "INSERT INTO `users` (`username`, `password`, `regTime`) VALUES ('" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "', '" . hash("sha256", $_POST['password']) . "', " . time() . ")";
			$result = mysql_query($sql);
			$_SESSION['loggedIn'] = true;
			$sql = "SELECT `ID` FROM `users` WHERE `username`='" . mysql_real_escape_string(htmlspecialchars($_POST['username'])) . "'";
			$result = mysql_query($sql);
			$row = mysql_fetch_object($result);
			
			$_SESSION['userID'] = $row->ID;
			//echo mysql_error();
			header("LOCATION: ?site=home");
			exit();
		}
		if (isset($_GET['error']))
			echo "<div class='error'>Error Typ: " . htmlspecialchars($_GET['error']) . "</div>";
?>
	<form action="?site=register&sent" method="POST">
		<input type="text" name="username"><br />
		<input type="password" name="password"><br />
		<input type="submit"><br />
	</form>
<?php
		break;
	case "chat":
		if (!$_SESSION['loggedIn']) {
			header("LOCATION: ?site=login");
			exit();
		}
		if (!isset($_GET['id'])) {
			header("LOCATION: ?site=conversations");
			exit();
		}
		
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
			WHERE `p`.`userFK`=" . $_SESSION['userID'] . " AND `c`.`ID`=" . intval($_GET['id']);
		$result = mysql_query($sql);
		if (!mysql_num_rows($result)) {
			echo "Du bist bei dem Chat nicht dabei... : / ";
			exit();
		}
		
		$row = mysql_fetch_object($result);
		
		if (intval($_SESSION['userID']) == $row->iuID) {
?>
<div style="height: 20px; width: 100%;">
	Uh, du bist der Initiator? Cool... : )
	<input type="text" id="username">
	<button onclick="addUser()">Benutzer hinzuf&uuml;gen</button>
</div>
<?php
		}
?>
<div style="display: none">
	<style>
		.sender {
			text-decoration: underline;
			font-weight: bold;
		}
	</style>
	<script>
		var ret = function(v) {
			return v;
		}
		//var http;
		var reqGet = function(file, pars, bg, after) {
			var http = new XMLHttpRequest();
			http.open("GET", "ajax/" + file + ".php?" + pars, bg);
			if (bg) {
				http.onreadystatechange = function() {
					if (http.readyState == 4) {
						after(http.responseText);
					}
				};
			}
			http.send(null);
			if (!bg) 
				return after(http.responseText);
		}
		var reqPost = function(file, get, pars, bg, after) {
			var http = new XMLHttpRequest();
			http.open("POST", file + ".php?" + get, bg);
			http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			http.setRequestHeader("Content-length", pars.length);
			http.setRequestHeader("Connection", "close")
			if (bg) {
				http.onreadystatechange = function() {
					if (http.readyState == 4) {
						after(http.responseText);
					}
				};
			}
			http.send(pars);
			if (!bg) 
				return after(http.responseText);
		}
		var addUser = function() {
			try {
				//http.abort();
			} catch(e) {
				
			}
			var username = document.getElementById("username").value;
			reqPost("ajax", "addUser", "chat=<?php echo $row->id; ?>&username=" + encodeURIComponent(username), true, message);
			document.getElementById("username").value = "";
			//startLoader();
		}
		var lastId = 0;
		var lastPa = 0;
		var startLoader = function() {
			reqPost("ajax", "load", "chat=<?php echo $row->id; ?>&mid=" + lastId + "&pid=" + lastPa, true, load);
		}
		var load = function(result) {
			console.log(result);
			if (!result.length)
				return;
			result = JSON.parse(result);
			if (result.nic) {
				window.location = "kicked.html";
				return;
			}
			if (result.lastId)
				lastId = result.lastId;
			if (result.lastPa)
				lastPa = result.lastPa;
			if (result.messages) {
				var chat = document.getElementById("chat");
				var chattext = "";
				for (var i = 0; i < result.messages.length; i++) {
					chattext += "<tr>";
					chattext += '<td class="sender">';
				//	chattext += '<a href="?site=users&id=' + result.messages[i].userid + '">';
					chattext += result.messages[i].username;
				//	chattext += '</a>';
					chattext += "</td>";
					chattext += "<td>";
					chattext += result.messages[i].text;
					chattext += "</td>";
					chattext += "</tr>";
				}
				chat.innerHTML += chattext;
			}
			if (result.users) {
				var users = document.getElementById("users");
				users.innerHTML = "";
				var userstext = "";
				for (var i = 0; i < result.users.length; i++) {
					userstext += "<tr>";
					userstext += "<td>";
					//userstext += '<a href="?site=users&id=' + result.users[i].userid + '">';
					if (result.users[i].userid != <?php echo $_SESSION['userID'];?>) {
					<?php
					if (intval($_SESSION['userID']) == $row->iuID)
						echo "userstext += '<a href=\"javascript:remove(' + result.users[i].userid + ');\">';\n";
					echo "userstext += result.users[i].username;\n";
					if (intval($_SESSION['userID']) == $row->iuID)
						echo "userstext += '</a>';\n";
					?>
					} else {
						userstext += result.users[i].username;
					}
					userstext += "</td>";
					userstext += "</tr>";
				}
				users.innerHTML = userstext;
			}
			scrollDown();
			startLoader();
		}
		var remove = function(id) {
			reqPost("ajax", "removeUser", "chat=<?php echo $row->id; ?>&id=" + id, true, resetLastPa);
		}
		var resetLastPa = function(msg) {
			lastPa = 0;
		}
		var send = function() {
			try {
				//http.abort();
			} catch(e) {
				
			}
			var text = document.getElementById("chatInput").value;
			document.getElementById("chatInput").value = "";
			reqPost("ajax", "send", "text=" + encodeURIComponent(text) + "&chat=<?php echo $row->id; ?>", true, function (v) { console.dir(v); } );
			//startLoader();
		}
		var message = function(result) {
			console.log(result);
			result = JSON.parse(result);
			console.dir(result);
		}
		var scrollDown = function() {
			var obj = document.getElementById("chatcontainer");
			obj.scrollTop = obj.scrollHeight;
		}
		window.onunload = function() {
			document.getElementById("chat").innerHTML = "";
			//http.abort();
		}
		startLoader();
	</script>
</div>
<div style="height: 85%; width: 100%">
	<div id="chatcontainer" style="float: left; height: 100%; width: 75%; overflow-y: scroll; overflow-x: hidden">
		<table id="chat">
		</table>
	</div>
	<div style="float: right; height: 100%; width: 25%; overflow-y: scroll; overflow-x: hidden">
		<table id="users">
		</table>
	</div>
</div>
<div style="margin: 0px; padding: 0px; height: 5%; width: 100%; position: absolute; left: 0px; bottom: 0px;">
	<form>
		<input type="text" id="chatInput" style="margin: 0px; padding-left: 10px; width: 89%">
		<input type="submit" style="width: 10%" onclick="send(); return false;">
	</form>
</div>
<?php
		
		break;
	case "conversations":
		if (!$_SESSION['loggedIn']) {
			header("LOCATION: ?site=home");
			exit();
		}
		
		if (isset($_GET['new'])) {
			if (!isset($_POST['name']) || empty($_POST['name'])) {
				header("LOCATION: ?site=conversations&error=name");
				exit();
			}
			
			$sql = "INSERT INTO `conversations` (`name`, `initUserFK`, `initTime`) VALUES ('" .  mysql_real_escape_string(htmlspecialchars($_POST['name'])) . "', " . $_SESSION['userID'] . ", " . time() . ")";
			$result = mysql_query($sql);
			
			$sql = "SELECT `ID` FROM `conversations` WHERE `name`='" . mysql_real_escape_string(htmlspecialchars($_POST['name'])) . "'";
			$result = mysql_query($sql);
			$row = mysql_fetch_object($result);
			
			$sql = "INSERT INTO `participants` (`conversationFK`, `userFK`, `joinTime`) VALUES (" . $row->ID . ", " . $_SESSION['userID'] . ", " . time() . ")";
			$result = mysql_query($sql);
			
			header("LOCATION: ?site=conversations");
			exit();
		}
		
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
			WHERE `p`.`userFK`=" . $_SESSION['userID'];
			
		$result = mysql_query($sql);
		
		if (!mysql_num_rows($result)) {
			echo "Keine Konversationen... : (<br />";
		}
		if (isset($_GET['error']))
			echo "<div class='error'>Error Typ: " . htmlspecialchars($_GET['error']) . "</div>";
?>
	<table>
<?php
		while ($row = mysql_fetch_object($result)) {
?>
	<tr>
		<td colspan="2"><a href="?site=chat&id=<?php echo $row->id; ?>"><?php echo $row->name; ?></td>
	</tr>
	<tr>
		<td>von <a href="?site=users&id=<?php echo $row->iuID; ?>"><?php echo $row->initUser; ?></a></td>
		<td>um <?php echo $row->initTime; ?></td>
	</tr>
	<tr>
		<td>dabei seit <?php echo $row->joinTime; ?></td>
	</tr>
<?php
		}
?>
	</table>
	<div>
		Neue Konversation:<br />
		<form action="?site=conversations&new" method="POST">
			<input type="text" name="name"><br />
			<input type="submit">
		</form>
	</div>
<?php
		break;
	}
?>
