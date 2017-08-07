<?php

define('__FILEROOT__', 'fileStore/');
define('__ARCHIVEDIR__', 'document/');
define('__OPENDIR__', 'show/');

$showFiles = json_encode(array('image/', 'text/', 'video/'));
define('_SHOWFILETYPE_', $showFiles);

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

$router->add('/get_filestream', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    if (!array_key_exists('openid', $_GET) || empty($_GET['openid'])) {
        $result->ReturnCode = '10022';
        $result->ErrorMessage = 'Illegal param';
        echo json_encode($result);
        exit();
    }
    $openId = $_GET['openid'];
    $con = new \ZFrame_Service\ZConnect();
    $linkRecord = $con->getLinkRecord($openId);
    if (is_null($linkRecord) || empty($linkRecord) || count($linkRecord) <= 0) {
        $result->ReturnCode = '10013';
        $result->ErrorMessage = 'No record found';
        echo json_encode($result);
        exit();
    }
    $fileType = $linkRecord[0]['file_type'];
    $fileDir = $linkRecord[0]['upload_date'] . '/';

    $ext = pathinfo($linkRecord[0]['file_name'], PATHINFO_EXTENSION);
    $fileName = $linkRecord[0]['open_id'] . '.' . $ext;

    $arrShow = json_decode(_SHOWFILETYPE_);
    $inShow = FALSE;
    $realFliePath = __FILEROOT__;
    foreach ($arrShow as $value) {
        if (strrpos($fileType, $value) !== FALSE) {
            $inShow = TRUE;
            break;
        }
    }
    if ($inShow) {
        $realFliePath .= __OPENDIR__;
    } else {
        $realFliePath .= __ARCHIVEDIR__;
    }
    $realFliePath .= $fileDir . $fileName;
    if (!file_exists($realFliePath) || !is_file($realFliePath)) {
        $result->ReturnCode = '10014';
        $result->ErrorMessage = 'File does not exist';
        echo json_encode($result);
        exit();
    }
    $start = 0;
    $end = filesize($realFliePath);
    if (array_key_exists('begin', $_GET) && !empty($_GET['begin']) && intval($_GET['begin']) > 0) {
        $start = intval($_GET['begin']);
    }
    if (array_key_exists('length', $_GET) && !empty($_GET['length'])) {
        $c_end = $start + intval($_GET['length']);
        if ($c_end < $end) {
            $end = $c_end;
        }
    }
    $length = $end - $start + 1;
    $fp = @fopen($realFliePath, 'rb');
    fseek($fp, $start);
    echo fread($fp, $length);
    flush();
    fclose($realFliePath);
});

$router->add('/get_file', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    if (!array_key_exists('openid', $_GET) || empty($_GET['openid'])) {
        $result->ReturnCode = '10022';
        $result->ErrorMessage = 'Illegal param';
        echo json_encode($result);
        exit();
    }
    $querySize = 'N';
    $customSize = array();
    if (array_key_exists('size', $_GET) && !empty($_GET['size'])) {
        switch (strtoupper($_GET['size'])) {
            case 'C':
                if (array_key_exists('w', $_GET) && !empty($_GET['w'])) {
                    array_push($customSize, intval($_GET['w']));
                } else {
                    array_push($customSize, 0);
                }
                if (array_key_exists('h', $_GET) && !empty($_GET['h'])) {
                    array_push($customSize, intval($_GET['h']));
                } else {
                    array_push($customSize, 0);
                }
                if ((!array_key_exists('w', $_GET) || empty($_GET['w'])) && (!array_key_exists('h', $_GET) || empty($_GET['h']))) {
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
        $result->ReturnCode = '10013';
        $result->ErrorMessage = 'No record found';
        echo json_encode($result);
        exit();
    }
    $fileType = $linkRecord[0]['file_type'];
    $fileDir = $linkRecord[0]['upload_date'] . '/';

    $ext = pathinfo($linkRecord[0]['file_name'], PATHINFO_EXTENSION);
    $fileName = $linkRecord[0]['open_id'] . '.' . $ext;

    $arrShow = json_decode(_SHOWFILETYPE_);
    $inShow = FALSE;
    foreach ($arrShow as $value) {
        if (strrpos($fileType, $value) !== FALSE) {
            $inShow = TRUE;
            break;
        }
    }
    if ($inShow) {
        if (!file_exists(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName)) {
            $result->ReturnCode = '10014';
            $result->ErrorMessage = 'File does not exist';
            echo json_encode($result);
            exit();
        }
        if (strrpos($fileType, 'image/') !== FALSE && $querySize !== 'N') {
            $behavir = new behavior($querySize, $customSize);
            $fileDir = $behavir->queryFile($fileDir, $fileName);
            $fileName = $linkRecord[0]['open_id'] . '.png';
        }

        $size = filesize(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName); // File size
        header("Content-type: $fileType");

        if (isset($_SERVER['HTTP_RANGE'])) {
            $fp = @fopen(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName, 'rb');

            $length = $size;
            $start = 0;
            $end = $size - 1;
            header("Accept-Ranges: 0-$length");
            if (isset($_SERVER['HTTP_RANGE'])) {

                $c_start = $start;
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                if ($range0 == '-') {
                    $c_start = $size - substr($range, 1);
                } else {

                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: $length");

            $buffer = 1024 * 8;
            while (!feof($fp) && ($p = ftell($fp)) <= $end) {

                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(0);
                echo fread($fp, $buffer);
                flush();
            }

            fclose($fp);
        } else {
            header("Content-Length: $size");
            readfile(__FILEROOT__ . __OPENDIR__ . $fileDir . $fileName);
        }
    } else {
        if (!file_exists(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName)) {
            $result->ReturnCode = '10014';
            $result->ErrorMessage = 'File does not exist';
            echo json_encode($result);
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
            $dlFileName = $fileName;
        }

        Header("Content-Disposition: attachment; filename=" . $dlFileName);
        echo fread($file, filesize(__FILEROOT__ . __ARCHIVEDIR__ . $fileDir . $fileName));
        fclose($file);
        exit();
    }
});

$router->add('/upload_file', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    if (!array_key_exists('file', $_FILES)) {
        $result->ReturnCode = '10022';
        $result->ErrorMessage = 'No upload file';
        echo json_encode($result);
        exit();
    }
    $fileName = 'tmpFile.tmp';
    $fileType = 'application/octet-stream';
    if (is_array($_POST)) {
        if (array_key_exists('fileName', $_POST) && !empty($_POST['fileName'])) {
            $fileName = $_POST['fileName'];
        } else if (is_array($_FILES["file"]) && array_key_exists('name', $_FILES["file"]) && !empty($_FILES["file"]["name"])) {
            $fileName = $_FILES["file"]["name"];
        }
        if (array_key_exists('fileType', $_POST) && !empty($_POST['fileType'])) {
            $fileType = $_POST['fileType'];
        } else if (is_array($_FILES["file"]) && array_key_exists('type', $_FILES["file"]) && !empty($_FILES["file"]["type"])) {
            $fileType = $_FILES["file"]["type"];
        }
    }

    $con = new \ZFrame_Service\ZConnect();

    $skipFlg = false;
    $fileMd5 = md5_file($_FILES["file"]["tmp_name"]);
    $linkRecord = $con->getLinkRecordByMd5($fileMd5);
    if (count($linkRecord)) {
        $skipFlg = true;
        $openId = $linkRecord[0]['open_id'];
        if ($linkRecord[0]['recyc_flg'] === '1') {
            $recoverResult = $con->recoverLinkRecord($openId);
            if ($recoverResult) {
                $result->ReturnCode = '200';
                $result->ErrorMessage = 'OK';
                $result->OpenId = $openId;
                echo json_encode($result);
                exit();
            } else {
                $skipFlg = false;
            }
        }
        if ($skipFlg) {
            $result->ReturnCode = '10060';
            $result->ErrorMessage = 'File exists';
            $result->OpenId = $openId;
            echo json_encode($result);
            exit();
        }
    }

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
        $arrShow = json_decode(_SHOWFILETYPE_);
        $inShow = FALSE;
        foreach ($arrShow as $value) {
            if (strrpos($fileType, $value) !== FALSE) {
                $inShow = TRUE;
                break;
            }
        }
        if ($inShow) {
            $target = __OPENDIR__;
        }
        if (!file_exists(__FILEROOT__)) {
            mkdir(__FILEROOT__);
        }
        if (!file_exists(__FILEROOT__ . $target)) {
            mkdir(__FILEROOT__ . $target);
        }
        if (!file_exists(__FILEROOT__ . $target . date('Ymd'))) {
            mkdir(__FILEROOT__ . $target . date('Ymd'));
        }

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $movResult = move_uploaded_file($_FILES["file"]["tmp_name"], __FILEROOT__ . $target . date('Ymd') . '/' . $openID . '.' . $ext);
        $stat = $pdo->prepare(constant("insert.linkRecord"));
        $stat->execute(array(
            ':open_id' => $openID,
            ':store_uri' => $_SERVER["HTTP_HOST"],
            ':file_name' => $fileName,
            ':upload_date' => date('Ymd'),
            ':file_type' => $fileType,
            ':file_md5' => $fileMd5));
        //$pdo->lastInsertId();
        if (!$movResult) {
            $pdo->rollBack();
            $result->ReturnCode = '10026';
            $result->ErrorMessage = 'Move upload file failure';
            echo json_encode($result);
        } else {
            $pdo->commit();
            $result->ReturnCode = '200';
            $result->ErrorMessage = 'OK';
            $result->OpenId = $openID;
            echo json_encode($result);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $result->ReturnCode = '10000';
        $result->ErrorMessage = 'Undefined error - ' . $e;
        echo json_encode($result);
    } finally {
        $con->_destroyPdo();
        $con = null;
    }
});

$router->add('/del_file', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    if (!array_key_exists('openid', $_GET) || empty($_GET['openid'])) {
        $result->ReturnCode = '10022';
        $result->ErrorMessage = 'Illegal param';
        echo json_encode($result);
        exit();
    }
    $openId = $_GET['openid'];
    $con = new \ZFrame_Service\ZConnect();
    $delResult = $con->recycLinkRecord($openId);
    if ($delResult) {
        $result->ReturnCode = '200';
        $result->ErrorMessage = 'OK';
    } else {
        $result->ReturnCode = '10016';
        $result->ErrorMessage = 'Recycle fail';
    }
    echo json_encode($result);
});


$router->add('/get_video_thumb', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    if (!array_key_exists('openid', $_GET) || empty($_GET['openid'])) {
        $result->ReturnCode = '10022';
        $result->ErrorMessage = 'Illegal param';
        echo json_encode($result);
        exit();
    }
    $openId = $_GET['openid'];
    $con = new \ZFrame_Service\ZConnect();
    $linkRecord = $con->getLinkRecord($openId);
    if (is_null($linkRecord) || empty($linkRecord) || count($linkRecord) <= 0) {
        $result->ReturnCode = '10013';
        $result->ErrorMessage = 'No record found';
        echo json_encode($result);
        exit();
    }
    $fileType = $linkRecord[0]['file_type'];
    $fileDir = $linkRecord[0]['upload_date'] . '/';

    $ext = pathinfo($linkRecord[0]['file_name'], PATHINFO_EXTENSION);
    $fileName = $linkRecord[0]['open_id'] . '.' . $ext;

    if (strrpos($fileType, 'video/') === FALSE) {
        $result->ReturnCode = '10099';
        $result->ErrorMessage = 'Not video';
        echo json_encode($result);
        exit();
    }
    $srcFilePath = __FILEROOT__ . __OPENDIR__ . $fileDir . $fileName;
    if (!file_exists($srcFilePath)) {
        $result->ReturnCode = '10014';
        $result->ErrorMessage = 'File does not exist';
        echo json_encode($result);
        exit();
    }
    $videoThumbPath = __FILEROOT__ . __OPENDIR__ . $fileDir . 'videoThumb/';
    if (!file_exists($videoThumbPath)) {
        mkdir($videoThumbPath);
    }
    $videoThumbPath .= $linkRecord[0]['open_id'] . '.jpg';
    if (!file_exists($videoThumbPath)) {
        $behavir = new behavior('N', null);
        $result = $behavir->getVideoCover($srcFilePath, 1, $videoThumbPath);
    }
    $size = filesize($videoThumbPath); // File size
    header("Content-type: image/jpeg");
    header("Content-Length: $size");
    readfile($videoThumbPath);
});

$router->add('default', function() {
    $result = new \stdClass();
    $result->Date = date('Y-m-d H:i:s');
    $result->ReturnCode = '10001';
    $result->ErrorMessage = 'Illegal request';
    echo json_encode($result);
    exit();
});

$router->run();
