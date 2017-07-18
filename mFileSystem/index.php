<?php

define('__SHOWFILEPATH__', 'http://localhost/');
define('__FILEROOT__', 'fileStore/');
define('__ARCHIVEDIR__', 'document/');
define('__OPENDIR__', 'show/');

require_once 'classes/router.class.php';
require_once 'Config/SqlDef.php';
require_once 'ZConnect/PDO.php';
require_once 'Config/SqlDef.php';

$router = new router();

$router->add('/request_size', function() {
    echo disk_free_space('/');
    exit();
});

$router->add('/get_file', function() {
    if (!array_key_exists('openid', $_GET) || empty($_GET['openid'])) {
        echo 'Illegal param';
        exit();
    }
    $openId = $_GET['openid'];
    $con = new \ZFrame_Service\ZConnect();
    $linkRecord = $con->getLinkRecord($openId);
    if (is_null($linkRecord) || empty($linkRecord) || count($linkRecord) <= 0) {
        echo 'No record';
        exit();
    }
    $fileType = $linkRecord[0]['file_type'];
    $fileDir = $linkRecord[0]['upload_date'] . '/';
    $fileName = $linkRecord[0]['open_id'] . $linkRecord[0]['file_name'];
    if (strrpos($fileType, 'image/') !== FALSE || strrpos($fileType, 'text/') !== FALSE) {
        echo __SHOWFILEPATH__ . $fileDir . $fileName;
        exit();
    } else {
        if (!file_exists(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName)) {
            echo 'No file';
            exit();
        }
        $file = fopen(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName, "r");
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName));
        Header("Content-Disposition: attachment; filename=" . $fileName);
        echo fread($file, filesize(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName));
        fclose($file);
    }
});

$router->add('/upload_file', function() {
    if (!array_key_exists('file', $_FILES)) {
        echo 'noFile';
        exit();
    }
    $fileName = 'tmpFile.tmp';
    $fileType = 'application/octet-stream';
    if (is_array($_POST)) {
        if (array_key_exists('fileName', $_POST) && !empty($_POST['fileName'])) {
            $fileName = $_POST['fileName'];
        }
        if (array_key_exists('fileType', $_POST) && !empty($_POST['fileType'])) {
            $fileType = $_POST['fileType'];
        }
    }
    $con = new \ZFrame_Service\ZConnect();

    $openID = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < 24; $i++) {
        $openID .= $strPol[rand(0, $max)];
    }
    $openID .= date('Ymd');
    $openID .= md5($openID);

    $pdo = $con->_getPdo();
    $pdo->beginTransaction();
    try {
        $target = __ARCHIVEDIR__;
        if (strrpos($fileType, 'image/') !== FALSE || strrpos($fileType, 'text/') !== FALSE) {
            $target = __OPENDIR__;
        }
        if (!file_exists('fileStore/')) {
            mkdir('fileStore/');
        }
        if (!file_exists('fileStore/' . $target)) {
            mkdir('fileStore/' . $target);
        }
        if (!file_exists('fileStore/' . $target . date('Ymd'))) {
            mkdir('fileStore/' . $target . date('Ymd'));
        }
        $result = move_uploaded_file($_FILES["file"]["tmp_name"], 'fileStore/' . $target . date('Ymd') . '/' . $openID . $fileName);
        $stat = $pdo->prepare(constant("insert.linkRecord"));
        $stat->execute(array(
            ':open_id' => $openID,
            ':store_uri' => $_SERVER["HTTP_HOST"],
            ':file_name' => $fileName,
            ':upload_date' => date('Ymd'),
            ':file_type' => $fileType));
        //$pdo->lastInsertId();
        if (!$result) {
            $pdo->rollBack();
            echo 'No result';
        } else {
            $pdo->commit();
            echo $openID;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo $e;
    } finally {
        $con->_destroyPdo();
        $con = null;
    }
});

$router->add('default', function() {
    echo 'Illegal request';
    exit();
});

$router->run();
