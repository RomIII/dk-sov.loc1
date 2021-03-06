<?php
session_start();
include_once('../config.php');
if ($_SESSION['kod'] !== md5(md5(DB_PREFIX))) exit ("Ошибка регистрации");

$action = 'guestbook.php'; // название этого файла
$table_name = DB_PREFIX . '_guestbook'; // название таблицы с которой работаем
$folder = '';

//text,textarea,spaweditor,hidden,date
$formItems = array(
    array('title' => 'Имя', 'type' => 'text', 'name' => 'name', 'value' => '', 'save' => 'text'),
    array('title' => 'Почта', 'type' => 'text', 'name' => 'mail', 'value' => '', 'save' => 'text'),
    array('title' => 'Текст', 'type' => 'textarea', 'name' => 'text', 'value' => '', 'save' => 'text')
);

$js='';

$content='';
$arrColumns = array('id', 'nom');
foreach ($formItems as $arr) {
    $arrColumns[] = $arr['name'];
}

if (isset($_GET['a'])) {
    $a = Save::number($_GET['a']);
} else {
    $a = '';
}

//форма добавления
if ($a == 1) {
    $form = new FormBilder($action, $withFile = false);
    $form->buildForm($formItems);
    $content = $form->drawForm();
}
//добавление/обноление данных в таблице
if ($_POST) {
    $countPost = 0;
    $arrSavedPost = array();
    foreach ($_POST as $key => $value) {
        $key = Save::text($key);
        if ($key == 'id') {
            $value = Save::number($value);
        } else {
            $t=(string)$formItems[$countPost]['save'];
            $value = Save::$t($value);
        }
        $arrSavedPost[$key] = $value;
        $countPost++;
    }
    if (isset($arrSavedPost['id']) && $arrSavedPost['id'] != '') {
        $db->queryUpdate($table_name, $arrSavedPost, '`id`=' . $arrSavedPost['id']);
        $content = 'Изменения сохранены';
    } else {
        $maxnom = $db->simple_query("SELECT MAX(`nom`) FROM `" . $table_name . "`");
        if ($maxnom) {
            $m = $maxnom->fetch_row();
            $max = $m[0] + 1;
        } else {
            $max = 1;
        }
        $arrSavedPost['nom'] = $max;
        $db->queryInsert($table_name, $arrSavedPost);
        $content = 'Данные добавлены';
    }
}
// форма для редактирования
if ($a == 2) {
    $id = Save::number($_GET['id']);
    $arrRes = $db->querySelect($table_name, $arrColumns, '`id`=' . $id . ' LIMIT 1')->fetch_assoc();
    $countArr = count($formItems);
    $i = 0;
    while ($i < $countArr) {
        $formItems[$i]['value'] = $arrRes[$formItems[$i]['name']];
        $i++;
    }
    $formItems[] = array('title' => '', 'type' => 'hidden', 'name' => 'id', 'value' => $id);
    $form = new FormBilder($action, $withFile = true, $class = $folder, $id = $id);
    $form->buildForm($formItems);
    $content = $form->drawForm();
}
//удаление
if ($a == 3) {
    $id = Save::number($_GET['id']);
    $arrRes = $db->simple_query("DELETE FROM `" . $table_name . "` WHERE `id`='" . $id . "' LIMIT 1");
    header('location: ' . $action);
}
//вывод списка
if ($a == '') {
    $class = 'item2';
    $pagination = new Pagination($table_name,20,array('moduleName'=>$action));
    $pagination->createPagination();
    $content .= $pagination->show();
    if ($result = $db->mysqli->query('SELECT * FROM `' . $table_name . '` ORDER BY `id` DESC LIMIT ' . $pagination->start . ',' . $pagination->countOnPage)) {
        while ($row = $result->fetch_assoc()) {
            $class == 'item1' ? $class = 'item2' : $class = 'item1';
            $row['class'] = $class;
            $content .= Guestbook::admItem($row, $action, $folder);
        }
    }
    $content .= $pagination->show();
}



$templater = array(
    '%_TITLE_%' => 'Администрирование : гостевая книга',
    '%_JS_%' => TemplatesAdm::js_template().$js,
    '%_LOGO_%' => '',
    '%_SITE_%' => SITE_URL,
    '%_EXIT_%' => '<div id="exit"><a href="index.php?act=0">Выход</a></div>',
    '%_UP_MENU_%' => TemplatesAdm::up_menu_template($up_menu),
    '%_LEFT_MENU_%' => '<a href=./' . $action . '?a=1>Добавить</a>',
    '%_CONTENT_%' => $content,
);
TemplatesAdm::main_template($templater);
?>