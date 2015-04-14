<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 1);
header("Content-Type: text/html; charset=utf-8");

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

// Очистка таблиц, установка дампа
function install_dump($db_name) {
    global $project_root;
    $dump_dir = $project_root . '/dump_db/';
    $filename = $dump_dir . 'test.sql';

    if (!file_exists($filename)) {
        exit('Дамп базы не найден');
    }
    if (!file($filename)) {
        exit('Ошибка: неверный формат файла ' . $filename);
    } else {
        dropOldTables($db_name);
        parceDump($filename);
    }
    $message = "Базы данных установлены.<br>";
    return $message;
}

function dropOldTables($db) {
    global $mysqli;
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    $query = "SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') AS `drop` "
            . "FROM information_schema.tables "
            . "WHERE table_schema = '$db'";
    $result = $mysqli->query($query) or die(mysql_error());
    while ($row = $result->fetch_assoc()) {
        $mysqli->query($row['drop']) or die(mysql_error());
    }
    $result->free();
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
}

function parceDump($dump_filename, $i = 0, $j = 0) {
    global $mysqli;
    $dump = file($dump_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($dump as $key => $value) {
        if (substr($value, 0, 2) == '--') {
            unset($dump[$key]);
        }
    }
    $str = implode('', $dump);
    while ($i <= strlen($str) - 1) {
        if ($str[$i] == ';') {
            $query = substr($str, $j, $i - $j);
            $mysqli->query($query) or die("<br>Не удалось выполнить запрос " . mysql_error());
            $j = $i + 1;
        }
        $i++;
    }
}
