# com.example.membershipperiods

This extension adds Membership Periods: with every membership renewal or edit, separate membership periods are stored. They can be viewed on membership's details page. Additionally, membership's periods have links to contribution that renewed it.

![Screenshot](/images/screenshot.png) 


The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM 4.7+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.example.membershipperiods@https://github.com/octo-happiness/com.example.membershipperiods/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/octo-happiness/com.example.membershipperiods.git
cv en membershipperiods
```

## Usage

This extension does not require any changes in how the memberships are edited/renewed - membership periods will be stored automatically. Initially, every membership will have only one period (created during extension's installation) with dates copied from entire membership duration.

Membership periods are visible on a membership's details form for every contact. There's also a link for a contribution, if it was done during renewal.

## Known Issues

- shortening membership (moving membership end date back or membership start date forward) removes link between contribution and changed membership period