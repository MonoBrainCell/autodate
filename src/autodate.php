<?php
/**
 * Plugin Name: Autodate
 * Description: This plugin allows you to add dates to your site that will update automatically according to the interval you specify
 * Author: Sergei Sahapov
 * Version: 1.0
 * 
 * Text Domain: autodate
 * Domain Path: /languages/
 
 * License: GPLv2 or later
*/

// Проверяем наличие wp-функции по добавлению хуков, если нет - прерываем выполнение php
if ( !function_exists( 'add_action' ) ) {
	echo "It's just a plugin";
	exit;
}
// Определяем путь до файла, содержащего класс плагина и подключаем его
$dir=plugin_dir_path( __FILE__ )."php_inc/";
include_once("{$dir}autodate.php");

$main_obj=new Autodate();

// Добавляем хук на событие загрузки всех активных плагинов, а в качестве действия для хука передаём функцию, которая зарегистрируют путь до файлов перевода контента плагина
add_action( 'plugins_loaded', 'register_autodate_textdomain' );

function register_autodate_textdomain(){
	load_plugin_textdomain( 'autodate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Регистрируем функции, которые будут вызываться во время активации и деактивации плагина
register_activation_hook( __FILE__, array($main_obj,Autodate::ACTIV_DEACTIV_DATA["activation"][1]) );
register_deactivation_hook( __FILE__, array($main_obj, Autodate::ACTIV_DEACTIV_DATA["deactivation"][1]) );

// Обходим циклом список всех хуков, к которым нужно прикрепить определённые действия
foreach (Autodate::ACT_AND_FUNC_DATA as $group_name=>$data) {
	// Если хук, соответствуют регистрации js-файла для админки, то регистрируем этот файл в самом конце (99)
	if($group_name=="js_add"){
		add_action( $data[0], array($main_obj,$data[1]), 99 );
	}
	else {
		add_action( $data[0], array($main_obj,$data[1]) );
	}
}

// Регистрируем шорткод и функцию, которая будет генерировать контент и заменять им шорткод в контенте
add_shortcode(Autodate::SHORTCODE_DATA[0],"Autodate::".Autodate::SHORTCODE_DATA[1]);
