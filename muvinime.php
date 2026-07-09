<?php

/**
 * Plugin Name: MuviNime
 * Description: Anime uploader
 * Version: 2.1.2
 * Author: Z
 * Author URI:  https://t.me/kenzo_id
 */

if (!\defined('WPINC') || !\defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/autoload.php';

define('MVNIME_BASEFILE', WP_PLUGIN_DIR . '/muvinime/muvinime.php');

$plugin = new Muvinime\Plugin();
$plugin->boot();
