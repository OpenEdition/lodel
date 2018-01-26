#!/bin/sh

USER_ID=${LOCAL_USER_ID:-9001}

echo "Starting with UID : $USER_ID"


cp /code/lodelconfig-default.php /code/lodelconfig.php
sed -i "s|exit()|// exit()|g" /code/lodelconfig.php
sed -i "s/^\$cfg\['database'\] = '';$/\$cfg['database'] = '$MYSQL_DATABASE';/g" /code/lodelconfig.php
sed -i "s/^\$cfg\['dbusername'\] = '';$/\$cfg['dbusername'] = '$MYSQL_USER';/g" /code/lodelconfig.php
sed -i "s/^\$cfg\['dbpasswd.*$/\$cfg['dbpasswd'] = '$MYSQL_PASSWORD';/g" /code/lodelconfig.php
sed -i "s/^\$cfg\['dbhost.*$/\$cfg['dbhost'] = 'db';/g" /code/lodelconfig.php
sed -i "s/^\$cfg\['debugMode.*$/\$cfg['debugMode'] = $DEBUG_MODE;/g" /code/lodelconfig.php

if [ $DEBUG_MODE == 2 ]; then
	sed -i "s/log_level = notice/log_level = debug/" /usr/local/etc/php-fpm.d/www.conf
fi


chmod 664 /code/lodelconfig.php
chown $USER_ID:$USER_ID /code/lodelconfig.php

composer install -d /code/lodel/scripts/

keyfile=$(sed -n 's/\$cfg\['\''install_key'\''] = '\''\(.*\)'\'';/\1/p' /code/lodelconfig.php)

touch /code/$keyfile
chmod 664 /code/$keyfile
chown $USER_ID:$USER_ID /code/$keyfile

exec php-fpm
