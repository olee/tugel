php ../composer.phar dump-autoload --optimize

echo Dropping database...
cmd.sh doctrine:schema:drop --force

echo Creating database...
cmd.sh doctrine:schema:create

echo Clearing cache...
cache-clear_dev.sh
cache-clear_prod.sh

echo Dumping assets...
assetic-dump_dev.sh
assetic-dump_prod.sh