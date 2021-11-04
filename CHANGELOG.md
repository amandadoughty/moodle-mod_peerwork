r3.11.0
-------
- Refactored activity to used own setting for grouping instead of Common module settings.
- Refactored submission grade to allow float values
- Added grading before due date warning #59 (for group summary screen)
- Bumped release number to match latest major Moodle version supported
- Refactored settings to allow null calculator in order to allow for cases where
  all calculators are disabled/uninstalled.
- Moved PA weighting setting to calculator class.
- AMOS string fix #3 provided by https://github.com/izendegi.
- Table prefix fix #1 provided by https://github.com/izendegi.


r2.3.2
------
- Removed deprecated string grade/core
- Removed deprecated function user_picture::fields()

r2.3.1
------
- Added grading before due date warning #59
- Improved display of criteria scales #60

r2.3.0
------

- Added group restriction check #54
- Added group update observer to amend grades when group membership changes #37
- Made WebPA calculator a dependency
- Added download all submissions
- Changed icon
- Added first and last names to export #48
- Added student ID to export #57
- Fixed bug in calculation when self grade turned off #58

r2.2.0
------

- Fixed single calculator plugin error #55 #56
- Added support for v3.10

r2.1.0
------

- Added individual peer grade override
- Added site setting:
	Justification - disabled, hidden, visible anonymous, visible with usernames
- Added suite of Behat tests

r2.0.0
------

- Refactored the calculator as a subplugin
- Added course scales to selection

r1.1.0
------

- Added site settings:
	Default number of criteria - 1-5
	Default text for 5 criteria
	Default scale for 5 criteria
	Justification type - per peer or per criteria
- Added activity setting:
	Justification type - per peer or per criteria
- Some activity settings no longer editable after a student has submitted:
	Peer grades visibility
	Allow students to self grade
	Justification
	Justification type
	Justificaton max length
	Criteria
	Criteria scale
	Add criteria
- Style changes to improve responsiveness

r1.0.0
------

- Upgrade requirement to Moodle 3.6
- Change version number from 3.3+ to 1.0.0
- Rename plugin to mod_peerwork
- Various changes by Kevin Moore
- Forked from City University London's mod_peerassessment plugin v3.3
