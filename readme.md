# Flyn Queues (Multisite supported)

Flyn Queues is a WordPress plugin that adds task queue functionality to WordPress.

## Requirements

* Composer
* PHP 5.4+

## Installation

* Run `git clone https://github.com/Flynsarmy/wp-flyn-queues.git /path/to/wp-content/plugins/flyn-queues`
* `composer update` in the new plugin directory
* Network Activate the plugin


### Optional (Recommended) - Replace wp-cron with cron

This plugin uses the built in `wp_schedule_single_event` function, so disabling WP cron and calling it manually is recommended.

* In *wp-config.php* add `define('DISABLE_WP_CRON', 'true');`
* If on multisite, in your servers crontab (or cpanel) add a minutely cron for `wget http://yoursite.com/wp-admin/admin-ajax.php?action=flyn_queues_run_wp_cron >/dev/null 2>&1`
* If not on multisite, in your servers crontab (or cpanel) add a minutely cron for `wget http://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

## Usage

To push a task to the queue:

> (bool|null) flyn\_queues\_push(callable $callback, [array $args = array(), [int $timestamp = 0]])

Type     | Argument   | Description
-------- | ---------- | -----------------------------------------------
callable | $callback  | Callback to be executed. Accepts an array $args parameter.
array    | $args      | (optional) Array of arguments to pass to $callback.
int      | $timestamp | (optional) Earliest time to be executed. Defaults to immediately.

### Examples

1. Creates a *log.log* file in WP root folder containing the time the task was created and the time it was run:
```php
flyn_queues_push(function(array $args) {
	$fp = fopen(ABSPATH.'log.log', 'a');
	fprintf($fp, "timeAtCreate: %s, time now: %s\n", $args['timeAtCreate'], time());
	fclose($fp);
}, ['timeAtCreate' => time()]);
```