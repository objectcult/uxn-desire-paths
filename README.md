uxn-desire-paths
================

Prototype web app for data entry and visualization of "digital desire paths", tracking usage of digital tools.


Installation & Configuration
----------------------------
* move files to server directory; all app HREFs are relative to home directory.
* edit file utilities/create_database.sql to insert your database name, then run to create database tables
* rename _config.php.example to _config.php, edit to insert your server and database info
* (optional) run utilities/makedata.php to generate test data;  review makedata.php to see how it works, adjust for your own needs.

Some Implementation Details
----------------------------

The “digital desire paths” application is a PHP/MySQL application that relies heavily on a Javascript library called jsPlumb to manage the display of interactive connected graphs.  Through its API, javascript functions in a web page can create nodes and connect them with lines, using a wide variety of line styles and connection types.   jsPlumb graphs are interactive; nodes can be dragged around on the screen, and jsPlumb maintains the connecting lines; nodes and lines are interactive, and can be scripted to trigger actions, such as displaying text, on mouse clicks or rollovers.

The source code and technical documentation is (will be) available on github at https://github.com/objectcult/uxn-desire-paths.

Application structure
The app has these pages
1. index.php:  home page, with links to view or enter data
2. login.php: username and password entry to sign in, or to create an account.
	- login, registration, and logout are also always available in the site header
3. user_day_journal.php: signed in users can add, edit, or delete their digital tool use activity for any day; the default is the current day; other dates may be selected on the home page
4. aggregate.php: visualization of the activity data; defaults to show all data for all groups; data can be filtered by group or individual user and/or a date range
5. tool_admin.php: users with admin privileges can add new tools to the tool menu used on the user_day_journal page.


Database schema
The app uses three database tables:
1. users: username  and password for each user
2. tools:  tool names
3. activity: each record is one instance of digital tool usage, and includes
	- the user
	- the date and time of the activity
	- whether the activity record is about a single tool, or how two tools work together
	- the tool or tools used
	- the user's numerical rating of how difficult the activity was
	- the user's numerical rating of how useful/effective the activity was
	- an optional comment about the activity
