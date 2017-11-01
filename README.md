# Physunreg
Physunreg is a collection of scripts that automatically disable user accounts that are no longer allowed to have physcip computer lab accounts (`unreg.php`) and delete irrelevant user data of users that exceed their disk storage quota (`quota.sh`).

## Disable user accounts
* Clone this repository recursively (`git clone https://github.com/physcip/physunreg --recursive`) so that the `physreg` submodule is included. Make sure the `physreg` git submodule is up-to-date.
* Get `confic_secret.inc.php` from [physreg](https://github.com/physcip/physreg) and copy that into the `physreg` directory
* Copy `/etc/phyreg-allowed` from the server running [physreg](https://github.com/physcip/physreg) or run physunreg on the same machine
* Execute the physunreg script:
```bash
php -c php.ini unreg.php
```
* `unreg.php` **will prompt you** for every user in `/etc/phyreg-allowed` that might be disabled and then once again before actually disabling any user accounts.

## Delete user data
* Run `quota.sh` on the server providing the home directory mounts. Make sure the `DIR` setting in `quota.sh` points to the directory containing the home directories.
* `quota.sh` will output a list of users exceeding the 5GB limit with the note `disabled` if the user account is disabled in Active Directory
* `quota.sh` will generate a bash script that deletes some (usually irrelevant) data from the users' home directories and print that to stdout
