<?php
/**
 * @package Flynsarmy Queues
 * @version 1.0.0
 *
 * Plugin Name: Flynsarmy Queues
 * Description: Adds task queue functionality to WordPress
 * Author: Flynsarmy
 * Version: 0.1
 * Author URI: http://www.flynsarmy.com
 */

require __DIR__.'/vendor/autoload.php';

add_action('wp_ajax_flyn_queues_run_wp_cron', 'flyn_queues_run_wp_cron');
add_action('wp_ajax_nopriv_flyn_queues_run_wp_cron', 'flyn_queues_run_wp_cron');
function flyn_queues_run_wp_cron()
{
	set_time_limit(0);
	global $wpdb;

	$blogs = $wpdb->get_results("SELECT domain, path FROM $wpdb->blogs WHERE archived=0 AND deleted=0");

	foreach($blogs as $blog) {
		$url = "http://" . $blog->domain . ($blog->path ? $blog->path : '/') . 'wp-cron.php?doing_wp_cron';

		// Attempt to get around load balanacer timeouts by printing something
		echo $url . "<br/>\n";
		flush();

		wp_remote_get($url, [
			'timeout' => 0,
			'blocking' => false,
		]);
	}
	exit;
}

/**
 * Create a new queue item.
 *
 * Example Usage:
 * flyn_queues_push(function(array $args) {
 *     $fp = fopen(ABSPATH.'log.log', 'a');
 *     fprintf($fp, "timeAtCreate: %s, time now: %s\n", $args['timeAtCreate'], time());
 *     fclose($fp);
 * }, ['timeAtCreate' => time()]);
 *
 * @param  callable $callback   Callback to execute when the scheduled event is
 *                              triggered.
 *                              e.g function(array $args) {}
 * @param  array    $args       Array of argumens to pass to the callback
 * @param  int      $timestamp  Timestamp to trigger the task. Defaults to 'as soon as possible'.
 *
 * @return (bool|mull)          False if the event was cancelled by a plugin, null otherwise.
 */
function flyn_queues_push(callable $callback, array $args = [], $timestamp = 0)
{
	$callback = new Jeremeamia\SuperClosure\SerializableClosure($callback);

	// Scheduling an event to occur before 10 minutes after an existing event
	// of the same name will be ignored, unless you pass unique values for
	// $args. So do that.
	$unique_value = time()+uniqid();

	return wp_schedule_single_event( $timestamp ?: time(), 'flyn_queues_trigger', [
		serialize($callback), $args, $unique_value
	]);
}

add_action('flyn_queues_trigger', function($serializedCallback, array $args = []) {
	$callback = unserialize($serializedCallback);
	$callback($args);
}, 10, 2);