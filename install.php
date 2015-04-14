<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: text/html; charset=utf-8");

$project_root = __DIR__;
$smarty_dir = $project_root . '/smarty/';


// put full path to Smarty.class.php
require($smarty_dir . '/libs/Smarty.class.php');
$smarty = new Smarty();
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->template_dir = $smarty_dir . 'templates';
$smarty->compile_dir = $smarty_dir . 'templates_c';
$smarty->cache_dir = $smarty_dir . 'cache';
$smarty->config_dir = $smarty_dir . 'configs';

//
// Main block
//

$mysql_dir = $project_root;
$page_from = 'install.php';
$filename_user = 'user.php';

if (!isset($_POST['button_install'])) {
    // Стартовая страница index.php
    $smarty->assign('title', 'Вход в базу данных');
    $smarty->assign('message', 'Введите данные для подключения к БД');
    $smarty->assign('action', 'install.php');
    $smarty->display('user_ini.tpl');
    exit;
} else {
    // Подключение к БД
    if ($_POST['button_install'] == 'Вход в базу данных') {
        $user['db_name'] = $_POST['database_name'];
        $user['s_name'] = $_POST['server_name'];
        $user['u_name'] = $_POST['user_name'];
        $user['pas'] = $_POST['password'];

        if (!file_put_contents($filename_user, serialize($user))) {
            exit('Ошибка: не удалось записать фаил ' . $filename_user);
        }
    }
    
    $user = unserialize(file_get_contents($filename_user)); 

    $mysqli = new mysqli($user['s_name'], $user['u_name'], $user['pas'], $user['db_name']);
    if ($mysqli->connect_errno) {
        exit("Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error
                . '<br><a href="#" onclick="history.go(-1)">Go Back</a>');
    }
    $mysqli->query("SET NAMES utf8");
    $message = "Соединение с БД установлено.<br>";

    include 'mysql.php';

    // Проверка существования таблиц
    $db_name = $user['db_name'];
    $tables = array();
    $query = "SHOW TABLES FROM $db_name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    $result->free();
    if (!in_array('explanations', $tables) &&
        !in_array('categories_list', $tables) &&
        !in_array('cities_list', $tables)) {

        // Установка таблиц, если таблиц нет
        $message .=install_dump($user['db_name']);
    } else {

        // Диалог восстановления из дампа
        if ($_POST['button_install'] == 'Вход в базу данных') {
            $smarty->display('install_dump.tpl');
            exit;
        } else {
            if ($_POST['button_install'] == 'Да') {
                $message .=install_dump($user['db_name']);
            }
            // Если 'Нет' - то отображаем 'install_ok.tpl'
        }
    }

    // Страница ОК
    $smarty->assign('action', 'index.php');
    $smarty->assign('message', $message);
    $smarty->display('install_ok.tpl');
}