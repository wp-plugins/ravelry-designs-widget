<?php
require_once PLUGIN_PATH . 'class-rdw-widget.php';

class Ravelry_Design_Widget
{
	private static $ins = null;

	private static function instance()
	{
		is_null(self::$ins) && self::$ins = new self();
		return self::$ins;
	}

	public static function init()
	{
		add_action('plugins_loaded', array(self::instance(), 'pluginSetup'));
	}

	public function pluginSetup()
	{
		add_action('widgets_init', array(self::instance(), 'registerWidget'));
	}	

	public function registerWidget()
	{
		return register_widget('Rdw_Widget');
	}

}

Ravelry_Design_Widget::init();
