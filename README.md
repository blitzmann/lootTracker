lootTracker
===

This is a program designed to handle tracking of PVE site running and loot payments for EVE Online, specifically as it relates to wormhole corporations. 

The idea behind it is fairness, albiet it is complicated and I have since learned of more efficient and less complicated ways. At the start of each op, the operation owner selects the members that have joined the fleet for running PVE sites.  As members come and go, the operation owner modifies the members and creates groups, and whoever is in charge of looting and salvaging can choose which "group" had a hand if generating the loot. So if Bob joins an hour-long fleet and only participates for one site, the loot manager can apply that one sites loot to the group that Bob is in, rather than Bob getting a greater share of the entire hour's worth of loot.

In hindsight, it would have been easier to divvy the share based on time in fleet rather than what we have, but there it is.

Once an op is done, the loot is dumped into a controlled corporation hanger. A director can then go off an sell it. Once sold via the corp wallet, the application can import the corp wallet data from EVE Online's API and automatically generate payment amounts

We used this in M.DYN briefly before we left our C4 wormhole, and it worked out for us. But for a larger corporation I would suggest developing a better system based on time.

This is supplied AS-IS. I haven't touched it since I found it in the dusty corners of my old server, other than to remove private API keys and fix it up for the repo. I don't plan to work on this anymore, and it's here for interests sake.

Install / Usage
------

- Edit inc/db-sample.ini and move to a directory inaccessable to the outside.
- Edit inc/api-sample.ini and move to a directory inaccessable to the outside.
- Edit inc/config-sample.ini and rename it config.ini (keep in inc/ directory).
- Edit inc/lootTypes.php to include any loots you wish to eventually record via lootTracker.

- Run inc/lootTracker.sql to build database tables.
- Run inc/memberRefresh.php to fetch member list. This needs to run on a regular basis to keep up-to-date.
- Run inc/marketApi.php to fetch market data for loot. This also needs to be run on a regular basis.

Make sure HTTP Access to inc/ is restricted via .htaccess file or by other means. 

