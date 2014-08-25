dbmigrator
==========

Compares the schemas of two databases and generates migration SQL to be ran on the production server.

Usage
======

Run the script from the command line, ensure datbase credentials are set in the top of the script. 

The script will connect to database 1 and compare against database 2 and will produce delta SQL statements
for missing columns and tables that are missing.
