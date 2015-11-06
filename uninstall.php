<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit();

require_once dirname( __FILE__ ).'/wpusavevideos.php';

$WPUSaveVideos->uninstall();
