<?php

// Handle skin requests from our player rendering
if (isset($_GET['skin'])) {
	$username = $_GET['skin'];
	header('Content-type: image/png');
	header("Cache-Control: max-age=29030400, public");
	echo file_get_contents("http://skins.minecraft.net/MinecraftSkins/$username.png");
	exit();
}


/*
CREATE TABLE `sc_players` ( 
	`id` bigint(20), 
	`name` varchar(16) NOT NULL, 
	`leader` tinyint(1) default '0', 
	`tag` varchar(25) NOT NULL, 
	`friendly_fire` tinyint(1) default '0', 
	`neutral_kills` int(11) default NULL, 
	`rival_kills` int(11) default NULL, 
	`civilian_kills` int(11) default NULL, 
	`deaths` int(11) default NULL, 
	`last_seen` bigint NOT NULL, 
	`join_date` bigint NOT NULL, 
	`trusted` tinyint(1) default '0', 
	`flags` text NOT NULL, 
	`packed_past_clans` text, 
	uuid VARCHAR( 255 ) DEFAULT NULL, 

	PRIMARY KEY  (`id`), UNIQUE (`name`)
);

CREATE UNIQUE INDEX `uq_player_uuid` ON `sc_players` (`uuid`);

CREATE TABLE `sc_kills` ( 
	`kill_id` bigint(20), 
	`attacker` varchar(16) NOT NULL, 
	`attacker_tag` varchar(16) NOT NULL, 
	`victim` varchar(16) NOT NULL, 
	`victim_tag` varchar(16) NOT NULL, 
	`kill_type` varchar(1) NOT NULL, 
	attacker_uuid VARCHAR( 255 ) DEFAULT NULL, 
	victim_uuid VARCHAR( 255 ) DEFAULT NULL, 

	PRIMARY KEY  (`kill_id`)
);

CREATE TABLE `sc_clans` ( 
	`id` bigint(20), 
	`verified` tinyint(1) default '0', 
	`tag` varchar(25) NOT NULL, 
	`color_tag` varchar(25) NOT NULL, 
	`name` varchar(100) NOT NULL, 
	`friendly_fire` tinyint(1) default '0', 
	`founded` bigint NOT NULL, 
	`last_used` bigint NOT NULL, 
	`packed_allies` text NOT NULL, 
	`packed_rivals` text NOT NULL, 
	`packed_bb` mediumtext NOT NULL, 
	`cape_url` varchar(255) NOT NULL, 
	`flags` text NOT NULL, 
	`balance` double(64,2) default 0.0,  

	PRIMARY KEY  (`id`), UNIQUE (`tag`)
);
*/

$db = new PDO('sqlite:SimpleClans.db');

$player_leaderboard = <<<EOM
SELECT 
	sp.name,
	sc.name as clan_name,
	(sp.neutral_kills * 6) + (sp.civilian_kills * 3) - sp.deaths as score
FROM sc_players as sp
	INNER JOIN sc_clans as sc ON sp.tag = sc.tag
ORDER BY score DESC
EOM;

$clan_leaderboard = <<<EOM
SELECT 
	SUM((sp.neutral_kills * 6) + (sp.civilian_kills) - sp.deaths) as score,
	SUM(sp.neutral_kills) as clan_kills,
	SUM(sp.civilian_kills) as civilian_kills,
	SUM(sp.deaths) as deaths,
	sc.name,
	sc.color_tag
FROM sc_players as sp
	INNER JOIN sc_clans as sc ON sp.tag = sc.tag
GROUP BY sp.tag
ORDER BY score DESC
EOM;

$pvp_timeline = <<<EOM
SELECT 
	sk.attacker,
	sk.kill_type,
	sc.name,
	sk.victim
FROM sc_kills as sk
	INNER JOIN sc_players as sp ON sk.attacker_uuid = sp.uuid
	INNER JOIN sc_clans as sc ON sp.tag = sc.tag
EOM;

header('Content-type: application/json');
if (!isset($_GET['q'])) {
	echo json_encode(array());
	exit();
}

$sql = null;

switch ($_GET['q']) {
	case "timeline":
		$sql = $pvp_timeline;
		break;
	case "clan":
		$sql = $clan_leaderboard;
		break;
	case "player":
		$sql = $player_leaderboard;
		break;
}


if (!isset($sql)) {
	echo json_encode(array());
	exit();
}

$stmt = $db->prepare($sql);
// $stmt->bindValue(':id', 1, SQLITE3_INTEGER);
$stmt->execute();

$results = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	array_push($results, $row);
}
echo json_encode($results);

?>
