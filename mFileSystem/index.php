<?php

define('__FILEROOT__', 'fileStore/');
define('__ARCHIVEDIR__', 'document/');
define('__OPENDIR__', 'show/');

require_once 'classes/router.class.php';
require_once 'classes/behavior.class.php';
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
    $querySize = 'N';
    $customSize = array();
    if (array_key_exists('size', $_GET) && !empty($_GET['size'])) {
        switch (strtoupper($_GET['size'])) {
            case 'C':
                if (array_key_exists('w', $_GET) && !empty($_GET['w']) && array_key_exists('h', $_GET) && !empty($_GET['h'])) {
                    array_push($customSize, intval($_GET['w']));
                    array_push($customSize, intval($_GET['h']));
                }
                if (!is_int($customSize[0]) || !is_int($customSize[1]) || $customSize[0] <= 0 || $customSize[1] <= 0) {
                    break;
                }
            case 'L':
            case 'M':
            case 'S':
                $querySize = strtoupper($_GET['size']);
                break;
            default:break;
        }
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

    $ext = pathinfo($linkRecord[0]['file_name'], PATHINFO_EXTENSION);
    $fileName = $linkRecord[0]['open_id'] . '.' . $ext;

    if (strrpos($fileType, 'image/') !== FALSE || strrpos($fileType, 'text/') !== FALSE) {
        if (!file_exists(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName)) {
            echo 'No file';
            exit();
        }
        if (strrpos($fileType, 'image/') !== FALSE && $querySize !== 'N') {
            $behavir = new behavior($querySize, $customSize);
            $fileDir = $behavir->queryFile($fileDir, $fileName);
            $fileName = $linkRecord[0]['open_id'] . '.png';
        }
        Header("Content-type: " . $fileType);
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName));
        Header("Content-Transfer-Encoding: binary");
        readfile(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName);
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
        $dlFileName = '';
        if (!is_null($linkRecord[0]['file_name']) && !empty($linkRecord[0]['file_name'])) {
            $dlFileName = $linkRecord[0]['file_name'];
        } else {
            $dlFileName = $file;
        }

        Header("Content-Disposition: attachment; filename=" . $dlFileName);
        echo fread($file, filesize(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName));
        fclose($file);
        exit();
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

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        $result = move_uploaded_file($_FILES["file"]["tmp_name"], 'fileStore/' . $target . date('Ymd') . '/' . $openID . '.' . $ext);
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
