<?php

# Physreg is included as a git submodule since physunreg depends on many of
# physreg's configuration options
require_once "config.inc.php";
require_once "physreg/config.inc.php";

# $PRESERVEGROUPS: Groups whose member accounts to preserve because they are either
# allowed to register CIP pool accounts ($ALLOWEDGROUPS, physreg setting) or their
# accounts are not supposed to be disabled ($KEEPGROUPS, physunreg setting)
# The equivalent for specific users is $KEEPUSERS
$PRESERVEGROUPS = array_merge($ALLOWEDGROUPS, $KEEPGROUPS);

# Connect to TIK Active Directory
$tik_conn = ldap_connect($TIK_LDAPSERVER);
ldap_set_option($tik_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($tik_conn, LDAP_OPT_REFERRALS, FALSE);
if (!@ldap_bind($tik_conn, $TIK_LDAPSPECIALUSER, $TIK_LDAPSPECIALUSERPW))
{
	echo "Error: Could not bind to TIK Active Directory LDAP server at " . $TIK_LDAPSERVER . "\n";
	exit;
}

# Connect to physcip Active Directory
$physcip_conn = ldap_connect($PHYSCIP_SERVER);
ldap_set_option($physcip_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($physcip_conn, LDAP_OPT_REFERRALS, FALSE);
if (!@ldap_bind($physcip_conn, $PHYSCIP_PHYREGGER_DN, $PHYSCIP_PHYREGGER_PW))
{
	echo "Error: Could not bind to physcip Active Directory LDAP server at " . $PHYSCIP_SERVER . "\n";
	exit;
}


# Get all active users
$result = ldap_search($physcip_conn, $PHYSCIP_USER_CONTAINER, "(&(objectClass=user)(userAccountControl=512))");
$active_users = ldap_get_entries($physcip_conn, $result);
unset($active_users["count"]);

$to_disable = array();

foreach ($active_users as $user)
{
	# Generate full user name for debug output
	$uid = $user["uid"][0];
	$uidnumber = (int) $user["uidnumber"][0];

	if (!array_key_exists("givenname", $user))
		$fullname = $user["cn"][0] . " (" . $uid . ")" ;
	else
		$fullname = $user["sn"][0] . ", " . $user["givenname"][0] . " (" . $uid . ")";

	echo sprintf("Checking %s\n", $fullname);

	# Don't disable users in $KEEPUSERS or with uidNumbers < 10000
	if (in_array($uid, $KEEPUSERS))
		continue;
	
	if ($uidnumber < 10000)
		continue;

	# Get user information from TIK
	$result = ldap_search($tik_conn, $TIK_LDAPSEARCHBASE, "(&(samaccountname=" . $uid . "))");
	$tik_user_entries = ldap_get_entries($tik_conn, $result);

	if ($tik_user_entries["count"] != 1)
	{
		echo sprintf("--> %s (%s) does not exist, disabling user\n", $uid, $fullname);
		$to_disable[$uid] = array(
			dn => $user["dn"],
			fullname => $fullname
		);
		continue;
	}

	# Don't disable users with a `memberOf` attribute for an allowed group or
	# if an allowed group has a `member` attribute for the specific user
	$allowed = false;
	if (count(array_intersect($PRESERVEGROUPS, $tik_user_entries[0]["memberof"])) > 0)
		$allowed = TRUE;
	else
	{
		foreach ($PRESERVEGROUPS as $group)
		{
			$result = ldap_search($tik_conn, $group, "(&(objectClass=*))");
			$members = ldap_get_entries($tik_conn, $result);
			if (in_array($user["dn"], $members[0]["member"]))
			{
				$allowed = TRUE;
				break;
			}
		}
	}


	if (!$allowed)
	{
		echo sprintf("--> %s (%s) should be disabled.\n", $uid, $fullname);
		$to_disable[$uid] = array(
			dn => $user["dn"],
			fullname => $fullname
		);

		#$i = 0;
		#foreach($tik_user_entries[0]["memberof"] as $g)
		#{
		#	$g = reset(explode(",", $g));
		#	if (is_numeric($g) || in_array($g,array("CN=UniS-Studenten", "CN=" . substr($uid,0,3), "CN=CIP-Benutzer", "CN=Drucken", "CN=SubRosa")) || (strpos($g, "CN=DreamSpark") === 0))
		#		continue;
		#	if (!$i); echo " Only in ";
		#	echo $g . " ";
		#	$i++;
		#}
		#echo "\n";
	}
}

# Students with a physics minor or other exceptional cases get a special permission to create
# an account by manually adding them to $ALLOWEDUSERS in physreg. Since these permissions are
# usually only given for the duration of a single semester, ask users on whether we should
# disable the users in $ALLOWEDUSERS or not.
foreach ($to_disable as $uid => $user)
{
	if (in_array($uid, $ALLOWEDUSERS))
	{
		$ans = readline(":: Account " . $user["fullname"] . " is manually specified in " . $ALLOWFILE . ". Disable anyway? [y/N] ");
		if (strtolower($ans) !== "y")
			unset($to_disable[$uid]);
	}
}

# Actually perform deletion after asking for permission
if (count($to_disable) > 0)
{
	echo sprintf("\nList of accounts to disable:\n");
	foreach ($to_disable as $uid => $user)
		echo "* " . $user["fullname"] . "\n";

	$ans = readline("\n:: Continue? [y/N] ");
	if (strtolower($ans) === "y")
	{
		echo sprintf("\n##### Disabling users #####\n");

		foreach ($to_disable as $uid => $user)
		{
			ldap_modify($physcip_conn, $user["dn"], array("userAccountControl" => 514));
			echo $user["fullname"] . " disabled\n";
		}
	}
}

?>
