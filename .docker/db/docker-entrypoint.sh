#!/bin/sh
    
if [ -d /app/mysql ]; then
    echo "$(date +'%n%Y-%m-%d %I:%M:%S %s%N') [Info] MySQL directory already present, skipping creation"
else
    echo "$(date +'%n%Y-%m-%d %I:%M:%S %s%N') [Info] MySQL data directory not found, creating initial DBs"

    mysql_install_db --user=root > /dev/null

    text=""

    if [ "$MYSQL_ROOT_PASSWORD" = "" ]; then
        MYSQL_ROOT_PASSWORD="e6c583774fadef03eebbfa45d920ab6b"
        text="$text$(date +'%n%Y-%m-%d %I:%M:%S') \033[31m[Warning] MySQL root password not set, defaulting to: $MYSQL_ROOT_PASSWORD\033[0m"
    fi

    MYSQL_DATABASE=${MYSQL_DATABASE}
  
    if [ "$MYSQL_USER" = "" ]; then
        MYSQL_USER="admin"
  	    text="$text$(date +'%n%Y-%m-%d %I:%M:%S') [Info] MySQL user not set, defaulting to: $MYSQL_USER"
    fi
  
    if [ "$MYSQL_PASSWORD" = "" ]; then
  	    MYSQL_PASSWORD="17f1290ffe42362640815bbb18618599"
  	    text="$text$(date +'%n%Y-%m-%d %I:%M:%S') \033[31m[Warning] MySQL user password not set, defaulting to: $MYSQL_PASSWORD\033[0m"
    fi
  
    if [ "$MYSQL_DATABASE" = "" ]; then
       MYSQL_DATABASE="database"
  	    text="$text$(date +'%n%Y-%m-%d %I:%M:%S') [Info] MySQL database name not set, defaulting to: $MYSQL_DATABASE"
    fi

    if [ ! -d "/run/mysqld" ]; then
        mkdir -p /run/mysqld
    fi

    tfile=`mktemp`
    if [ ! -f "$tfile" ]; then
        return 1
    fi


    for f in /docker-entrypoint-initdb.d/*; do
        case "$f" in
            *.sql)  text="$text$(date +'%n%Y-%m-%d %I:%M:%S %s%N') [Info] Executed SQL script $(basename $f)" ;
                ( echo "cat <<EOF" ; cat $f ; echo EOF ) | sh >> $tfile ;;
            *)        echo "$0: ignoring $f" ;;
        esac
    done

    /usr/bin/mysqld --user=root --bootstrap --verbose=0 < $tfile
  
    rm -f $tfile
fi


echo -e "${text}\n"

exec /usr/bin/mysqld --user=root --console
