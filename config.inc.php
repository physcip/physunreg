<?php

# $KEEPGROUPS: Groups whose member's accounts won't be deleted (despite not being a member of physreg's $ALLOWEDGROUPS)
# Doktoranden sind bei der Fakultät geführt. Mathe-Doktoranden sollen sich aber nicht registrieren können.
# Deswegen müssen neue Physik-Doktoranden manuell freigeschaltet werden, werden aber nicht automatisch deaktiviert.
$KEEPGROUPS=array(
	"CN=Stg1590-08-P82,OU=OrgGroups,OU=IDMGroups,OU=SIAM,DC=stud,DC=uni-stuttgart,DC=de",
);

# $KEEPUSERS: List of some special cases of users that are allowed to keep their account despite not being
# physics students, students minoring in physics or PhD candidates, such as administrative staff etc.
# These accounts won't be deleted.
$KEEPUSERS=array(
	"ac102610",
	"phy31642",
	"phy53542",
	"st141629"
);

?>
