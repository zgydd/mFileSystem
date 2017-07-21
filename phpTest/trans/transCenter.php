<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if ($_FILES["file"]["error"] > 0) {
    echo "Error: " . $_FILES["file"]["error"] . "<br />";
} else {
    /*
      echo "Upload: " . $_FILES["file"]["name"] . "<br />";
      echo "Type: " . $_FILES["file"]["type"] . "<br />";
      echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
      echo "Stored in: " . $_FILES["file"]["tmp_name"];
      echo disk_free_space('/');
      return;
     * 
     */
    /*
      $servicesHandle = array();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://localhost:16820/index.php/request_sizes");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      array_push($servicesHandle, $ch);

      $multiCurlMasterHandle = curl_multi_init();
      foreach ($servicesHandle as $handle) {
      curl_multi_add_handle($multiCurlMasterHandle, $handle);
      }
      $active = null;
      do {
      $multiCurlExecResult = curl_multi_exec($multiCurlMasterHandle, $active);
      } while ($multiCurlExecResult == CURLM_CALL_MULTI_PERFORM);
      while ($active && $multiCurlExecResult == CURLM_OK) {
      if (curl_multi_select($multiCurlMasterHandle) != -1) {
      do {
      $multiCurlExecResult = curl_multi_exec($multiCurlMasterHandle, $active);
      } while ($multiCurlExecResult == CURLM_CALL_MULTI_PERFORM);
      }
      }
      $response = array();
      foreach ($servicesHandle as $handle) {
      array_push($response, curl_multi_getcontent($handle));
      curl_multi_remove_handle($multiCurlMasterHandle, $handle);
      }
      curl_multi_close($multiCurlMasterHandle);
      var_dump($response);
      return;
     * 
     */

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:16820/index.php/request_size");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    if ($response === FALSE) {
        echo 'No store';
        exit();
    }
    //var_dump($response);
    //var_dump($_FILES["file"]["size"]);
    //return;
    if ($_FILES["file"]["size"] > $response * 0.8) {
        echo 'No enouth space';
        exit();
    }

    $upFile = array();

    if (class_exists('\CURLFile')) {
        $upFile['file'] = new CURLFile($_FILES["file"]["tmp_name"]);
    } else {
        $upFile['file'] = '@' . realpath($_FILES["file"]["tmp_name"]);
    }
    $upFile['fileName'] = $_FILES["file"]["name"];
    $upFile['fileType'] = $_FILES["file"]["type"];

    $curl = curl_init("http://localhost:16820/index.php/upload_file");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $upFile);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    if (curl_exec($curl) === FALSE) {
        echo "<br/>", "  cUrl Error:" . curl_error($curl);
    }
    curl_close($curl);
}
