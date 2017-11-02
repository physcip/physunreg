#!/bin/bash

DIR=/Volumes/home

if [ "$1" != "--skip" ]; then
	sudo quotacheck -u $DIR
	#sudo repquota $DIR | tail -n+3 | awk '$3>10000000' | awk '{print $1" "$3/1048576"GiB"}'
fi

USERS=$(sudo repquota $DIR | tail -n+3 | awk '$3>5000000' | awk '{print $1}')

for u in $USERS; do
	if [ "$u" = "_unknown" ]; then
		continue
	fi
	email=$(dscl localhost -read /Search/Users/$u EMailAddress | awk '{print $2}')
	if [ "$email" = "" ]; then
		email=$u@physcip.uni-stuttgart.de
	fi
	echo $u $email $(sudo repquota /Volumes/home | grep "^$u[ -]" | awk '{print $3/1048576"GiB"}') $(pwpolicy -authentication-allowed -u $u 2>&1 | grep -o disabled)
done

cd $DIR

echo '###################'
echo '#!/bin/bash'

for u in *; do
	id $u 2>/dev/null >/dev/null
	if [ "$?" != "0" ]; then
		continue
	fi
	pwpolicy -authentication-allowed -u $u 2>&1 | grep -qo disabled
	if [ "$?" != "0" ]; then
		continue
	fi
	if [ -d "$u/Dropbox" ]; then
		echo sudo -u $u rm -r $DIR/$u/Dropbox
	fi
	if [ -d "$u/.Trash" ]; then
		echo sudo -u $u rm -r $DIR/$u/.Trash
	fi
	if [ -d "$u/Library/Mail" ]; then
		echo sudo -u $u rm -r $DIR/$u/Library/Mail
	fi
	if [ -d "$u/Library/Thunderbird" ]; then
		echo sudo -u $u rm -r $DIR/$u/Library/Thunderbird
	fi
	if [ -d "$u/Library/Application Support/Steam" ]; then
		echo sudo -u $u rm -r \"$DIR/$u/Library/Application Support/Steam\"
	fi
	if [ "$(sudo ls $u/Downloads 2>/dev/null | grep -v '\.localized\|DS_Store' | wc -l)" -gt "0" ]; then
		echo sudo -iu $u find $DIR/$u/Downloads/ -mindepth 1 -delete
	fi
done
