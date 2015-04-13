<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 1);
header("Content-Type: text/html; charset=utf-8");


// Подключение к БД

$filename_user = user_initialization($page_from);           //инициализация пользовотеля
$user = unserialize(file_get_contents($filename_user));

$mysqli = new mysqli($user['s_name'], $user['u_name'], $user['pas'], $user['db_name']);
if ($mysqli->connect_errno) {
    exit("Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error
    . unlink($filename_user)
    . '<br><a href="#" onclick="history.go(-1)">Go Back</a>');
}
if ($user['db_name'] == '') {
    exit("Введено пустое имя БД" . $mysqli->connect_error
    . unlink($filename_user)
    . '<br><a href="#" onclick="history.go(-1)">Go Back</a>');
}

$mysqli->query("SET NAMES utf8");
$message = "Соединение с БД установлено.<br>";

// Функции

function user_initialization($page_from) {
    global $smarty;
    $filename_user = 'user.php';
    if (!file_exists($filename_user)) {

        if (!isset($_POST['button_install'])) {
            $smarty->assign('title', 'Вход в базу данных');
            $smarty->assign('message', 'Введите данные для подключения к БД');
            $smarty->assign('action', $page_from);
            $smarty->display('user_ini.tpl');
            exit;
        } else {
            $user['db_name'] = $_POST['database_name'];
            $user['s_name'] = $_POST['server_name'];
            $user['u_name'] = $_POST['user_name'];
            $user['pas'] = $_POST['password'];
            if (!file_put_contents($filename_user, serialize($user))) {
                exit('Ошибка: не удалось записать фаил ' . $filename_user);
            }
        }
    } else {
        if (!file_get_contents($filename_user)) {
            exit('Ошибка: неверный формат файла ' . $filename_user);
        }
    }
    return $filename_user;
}

function db_setup() {
    global $mysqli;
    global $message;
    global $user;
    $db_name = $user['db_name'];
    $tables = array();
    $query = "SHOW TABLES FROM $db_name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    $result->free();
    if (in_array('explanations', $tables) &&
            in_array('categories_list', $tables) &&
            in_array('cities_list', $tables)) {
        $message .= "<br>Таблицы установлены.";
        
    } else {
        header('Location: ' . 'install.php');
    }
}

//function db_connect($page_from) {
//    global $smarty;
//    global $message;
//    global $mysqli;
//
//    $filename_user = user_initialization($page_from);
//    $user = unserialize(file_get_contents($filename_user));
//
//    $mysqli = new mysqli($user['s_name'], $user['u_name'], $user['pas'], $user['db_name']);
//    if ($mysqli->connect_error) {
//        echo "Ошибка соединения с базой данных. " . $mysqli->connect_error;
//        unlink($filename_user);
//    }
//    $mysqli->query("SET NAMES utf8");
//    $message = "Соединение с БД установлено.<br>";
//
//    return $user;
//}

function getCitiesList() {
    global $mysqli;
    $query = "SELECT * FROM cities_list";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $cities [$row['index']] = $row['city'];
    }
    $result->free();
    return $cities;
}

function getCategoriesList() {
    global $mysqli;
    $query = "SELECT t2.index, t2.category AS cat, t1.category AS groupe
                        FROM categories_list AS t1
                        LEFT JOIN categories_list AS t2 ON t2.parent_id = t1.index
                        WHERE t2.parent_id is not null";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $categories [$row['groupe']][$row['index']] = $row['cat'];
    }
    $result->free();
    return $categories;
}

function get_explanations_from_db() {
    global $mysqli;
    $query = "SELECT * FROM explanations ORDER BY id";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $explanations[$row['id']] = $row;
    }
    $result->free();
    if (isset($explanations)) {
        return $explanations;
    } else {
        return array();
    }
}

function add_explanation_into_db($exp, $id) {
    global $mysqli;
    $query = "REPLACE INTO explanations (`id`, `private`, `seller_name`, `email`, `allow_mails`, `phone`, `location_id`, `category_id`, `title`, `description`, `price`)
                    VALUES ('" . $id . "', '" . $exp['private'] . "', '" . $exp['seller_name'] . "' , '" . $exp['email'] . "', '" . $exp['allow_mails'] . "', '"
                    . $exp['phone'] . "', '" . $exp['location_id'] . "', '" . $exp['category_id'] . "', '" . $exp['title'] . "', '"
                    . $exp['description'] . "', '" . $exp['price'] . "')";
    $mysqli->query($query) or die("REPLACE abort " . $mysqli->connect_error);
}

function delete_explanation_from_db($id) {
    global $mysqli;
    $mysqli->query("delete from explanations where id = $id") or die("Не удалось удалить объявление" . $mysqli->connect_error);
}
