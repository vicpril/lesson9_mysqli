<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
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
// Functions
//

function getListOfExplanations($explanations) {
    $list = array();
    foreach ($explanations as $key => $value) {
        $list[] = '<a href="index.php?show=' . $key . '">' . $value['title'] . '</a>';
        $list[] = $value['price'];
        $list[] = $value['seller_name'];
        $list[] = '<a href="index.php?delete=' . $key . '">Удалить</a>';
    }
    return $list;
}

function processingQuery($array) {
    global $mysqli;
    foreach ($array as $key => &$value) {
        $query[$key] = trim($mysqli->real_escape_string(strip_tags($value)), ' .,\|/*-+"');
    }
    $query['price'] = (float) $query['price'];
    return $query;
}

//
// Main block
//

$filename_user = 'user.php';

// Проверка существования файла с данными
if (!file_exists($filename_user)) {
    
    // переадресация, если фаил не существует
    header("Refresh:10; url=install.php");
    exit("Параметры подключения к БД не заданы. Через 10 сек. Вы будете перенаправлены на страницу INSTALL.</br>
            Если автоматического перенаправления не происходит, нажмите <a href='install.php'>здесь</a>.");
}

// Подключение к БД
if (!file_get_contents($filename_user)) {
    exit('Ошибка: неверный формат файла ' . $filename_user);
}
$user = unserialize(file_get_contents($filename_user));

$mysqli = new mysqli($user['s_name'], $user['u_name'], $user['pas'], $user['db_name']);
if ($mysqli->connect_errno) {
    exit("Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error
            . '<br><a href="#" onclick="history.go(-1)">Go Back</a>');
}
$mysqli->query("SET NAMES utf8");
$message = "Соединение с БД установлено.<br>";

$mysql_dir = $project_root;
include($mysql_dir . '/mysql.php');

// Проверка существования таблиц
$db_name = $user['db_name'];
$tables = array();
$query = "SHOW TABLES FROM $db_name";
$result = $mysqli->query($query);
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}
$result->free();
if (!in_array('explanations', $tables) ||
        !in_array('categories_list', $tables) ||
        !in_array('cities_list', $tables)) {

    // Переадресация, если таблиц нет
     
    header("Refresh:10; url=install.php");
    exit("Нарушена структура или отсутствуют таблицы в БД. Через 10 сек. Вы будете перенаправлены на страницу INSTALL.</br>
            Если автоматического перенаправления не происходит, нажмите <a href='install.php'>здесь</a>."); 
}

// Работа скрипта

$id = (isset($_GET['id'])) ? $_GET['id'] : '';

if (isset($_GET['delete'])) {
    delete_explanation_from_db($_GET['delete']);
}

if (isset($_POST['button_add'])) {
    $ads = processingQuery($_POST);
    add_explanation_into_db($ads, $id);
}

$explanations = get_explanations_from_db();

if (isset($_GET['show']) && isset($explanations[$_GET['show']])) {
    $show = $_GET['show'];
    $name = $explanations[$show];
    foreach ($name as &$value) {
        $value = htmlspecialchars($value);
    }
    $smarty->assign('header_tpl', 'header_exp');
    $smarty->assign('title', 'Объявление');
    $smarty->assign('show', $show);
    $smarty->assign('name', $name);
} else {
    $smarty->assign('header_tpl', 'header');
    $smarty->assign('title', 'Доска объявлений');
}


$listOfExplanations = getListOfExplanations($explanations);

$smarty->assign('private_radios', array('0' => 'Частное лицо', '1' => 'Компания'));
$smarty->assign('cities', getCitiesList());
$smarty->assign('categories', getCategoriesList());
$smarty->assign('list', $listOfExplanations);
$smarty->assign('tr', array('bgcolor="#ffffff"', 'bgcolor="#E7F5FE"'));

$smarty->display('index.tpl');
