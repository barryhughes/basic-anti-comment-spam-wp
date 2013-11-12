<?php
/*
 * Plugin name: Anti Comment Spam
 * Description: Simple mechanism that marks comments as spam where A) they were posted without a minimum period of time (4 seconds by default) elapsing and B) they do not have Javascript enabled.
 * Author: Forthrobot Software
 * Version: 1.0
 * Author URI: http://forthrobot.net
 */

// Do nothing if the required PHP/WP version requirements are not met
if (version_compare(PHP_VERSION, '5.3') < 0) return;

// Load
include __DIR__ . '/inc/antispam.php';
new AntiCommentSpam;