# WordPress Locales Translation Consistency Checker

## Install

* Install and activate `plugin/wp-locales-translation-consistency-checker/`.
* Copy `etc/config.example.php` into `etc/config.php` and set configuration.
* CLI run `test.php` for configuration test.
* Run or add to crontab `cron/wp-consistency.php` to check Translation Consistency.
* Open `Translation Consistency` archive page (I suggest to add it to menu).
* Run `tools/export.php` to export Consistent Translations `po` file (to import on project translation file.

## How to add to crontab?

Use `crontab -e` and add line below. It means run checker everyday on 4:10.

`
10 4 * * * [your-path-here]/wp-locales-translation-consistency-checker/cron/wp-consistency.php >/dev/null 2>&1
`

