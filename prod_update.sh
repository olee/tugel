php composer.phar dump-autoload --optimize

echo Updating database...
./cmd.sh doctrine:schema:update --force

echo Clearing cache...
./cache-clear_prod.sh

echo Dumping assets...
./assetic-dump_prod.sh