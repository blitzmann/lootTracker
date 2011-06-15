Install:

- Edit inc/db-sample.ini and move to a directory inaccessable to the outside.
- Edit inc/api-sample.ini and move to a directory inaccessable to the outside.
- Edit inc/config-sample.ini and rename it config.ini (keep in inc/ directory).
- Edit inc/lootTypes.php to include any loots you wish to eventually record via lootTracker.

- Run inc/lootTracker.sql to build database tables.
- Run inc/memberRefresh.php to fetch member list. This needs to run on a regular basis to keep up-to-date.
- Run inc/marketApi.php to fetch market data for loot. This also needs to be run on a regular basis.

Make sure HTTP Access to inc/ is restricted via .htaccess file or by other means. 
Eventually this will come standard with the lootTracker package.

Should be done after that. I'll write up a more complete README later...