# Changelog - Forms plugin for glFusion
## 0.4.2
Release 2019-03-25
- Fix permissions when mailing or viewing user's own results.

## 0.4.1
Release 2008-12-29
- Fix call to undefined function when exporting CSV
- Fix access check, disabled fields were affecting visibility of other fields
- Admin was unable to save edits to submissions
- Missing quote caused glFusion navigation menu to stop working

## 0.4.0
Release 2018-12-28
- Retire support for non-uikit themes
- Handle inline form editing, validation and saving via service functions
- Remove LGLib dependency, was only used for storeMessage()
- Better namespace for field classes
- Require PHP 7
- Implement glFusion caching
- Implement `privacy_export` function
- Separate form field types into classes
- Implement the AJAX form type, saving values to session vars only
- Implement autotags for checkbox and radio fields, updating session vars only
- Implement PHP class autoloader

## 0.3.0
Release 2017-07-23
- Implement "Forms" namespace
- Change Mootools elements to JQuery
- Use standard icons across themes instead of images
- Change AJAX from XML to JSON
- Add "popup" autotag function for a modal popup form

## 0.1.8
Released 2013-02-10
- 0000502: [Form Display] Field permissions aren't kept when copying a form (lee) - resolved.
- 0000500: [Form Display] Add messages for max submissions or user isn't allowed to resubmit (lee) - resolved.
- 0000501: [Form Display] Remove the Submit button for admin preview (lee) - resolved.
- 0000471: [General] Add option to limit submissions (lee) - resolved.
- 0000472: [Form Display] Limit the max characters on text fields (lee) - resolved.

## 0.1.6
Released 2011-05-15
- 0000457: [Saving] Edited data isn't saved properly (lee) - resolved.

## 0.1.5
Released 2011-05-14
- 0000456: [General] Times not properly saved as am or pm (lee) - resolved.
- 0000458: [Form Display] Form results can be displayed to anonymous users other than the submitter (lee) - resolved.

## 0.1.3
(Released 2011-05-08
Added help text for fields, time-only field, fixed duplication of forms.
- 0000453: [General] Some fields aren't copied when duplicating a form (lee) - resolved.
- 0000447: [Form Display] Add help text item for fields (lee) - resolved.
- 0000445: [General] Add an option to view HTML source of a form (lee) - resolved.
- 0000443: [General] Add a time field (lee) - resolved.
- 0000444: [General] Showtime option not working for date fields (lee) - resolved.

## 0.1.2
Released 2011-01-22
Added captcha support, other minor fixes
- 0000429: [Form Display] Display the form to the submitter upon completion (lee) - resolved.
- 0000435: [Saving] Add submitter IP address to the database (lee) - resolved.
- 0000432: [Form Display] Add captcha to the forms (lee) - resolved.
- 0000430: [General] When editing a field the default position is changed to the last (lee) - resolved.
- 0000428: [General] SQL error in admin form (lee) - resolved.
- 0000427: [Form Display] Form doesn't always display changes in default value (lee) - resolved.

## 0.1.1
Second beta release
- 0000426: [General] Allow default field value to pull from $_USER values (lee) - resolved.
- 0000422: [General] Add calculated field type (lee) - resolved.
- 0000423: [General] Add a static text/html field (lee) - resolved.
- 0000424: [General] Add a submission counter to the admin screen (lee) - resolved.
- 0000425: [General] Add the ability to edit submissions (lee) - resolved.
