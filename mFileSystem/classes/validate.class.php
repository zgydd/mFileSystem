<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validate
 *
 * @author bartonjoe
 */
class validate {

    private $_trustedIp_ = array('127.0.0.1');
    private $_tokenTimeOut_ = array('y' => 0, 'm' => 0, 'd' => 0, 'h' => 0, 'i' => 0, 's' => 20);
    private $_strPol_ = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    private $_randomLength_ = 24;

    //put your code here
    public function __construct() {
        
    }

    public function noteOpenIdChanged($oldId, $newId) {
        //IMPORTANT!!!!!!!!!!!!!!!!!!!!!!!!Note anyone who used the openId!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!IMPORTANT
    }

    public function getOpenId() {
        $openID = null;
        $max = strlen($this->_strPol_) - 1;
        for ($i = 0; $i < $this->_randomLength_; $i++) {
            $openID .= $this->_strPol_[rand(0, $max)];
        }
        $openID .= date('Ymd');
        $openID .= md5($openID);
        return $openID;
    }

    public function trustIp() {
        if (in_array($this->getRealIp(), $this->_trustedIp_)) {
            return true;
        }
        return false;
    }

    public function isTokenTimeOut($theTime) {
        $dateDiff = date_diff(date_create(date("Y-m-d H:i:s", time())), date_create(date("Y-m-d H:i:s", $theTime)));
        if (($dateDiff->y > $this->_tokenTimeOut_['y']) || ($dateDiff->m > $this->_tokenTimeOut_['m']) ||
                ($dateDiff->d > $this->_tokenTimeOut_['d']) || ($dateDiff->h > $this->_tokenTimeOut_['h']) ||
                ($dateDiff->i > $this->_tokenTimeOut_['i']) || ($dateDiff->s > $this->_tokenTimeOut_['s'])) {
            return TRUE;
        }
        return FALSE;
    }

    private function getRealIp() {
        $ip = false;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi('^(10|172.16|192.168).', $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

}
