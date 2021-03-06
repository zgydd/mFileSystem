<?php

// Service sample PDO
// Initlize    20170124    Joe

namespace ZFrame_Service;

require_once 'Config/Constant.php';
require_once 'Config/SqlDef.php';
require_once 'classes/validate.class.php';

class ZConnect {

    private $pdo;

    public function __construct() {
        $objConst = new \ZFrame_Service\CONSTANT();

        $conCfg = $objConst->getDBCon();

        $conStr = $conCfg->type . ":host=" . $conCfg->host . ";port="
                . $conCfg->port . ";dbname=" . $conCfg->dbname;

        $this->pdo = new \PDO($conStr, $conCfg->user, $conCfg->pwd);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public function __destruct() {
        $this->pdo = NULL;
    }

    public function _getPdo() {
        return $this->pdo;
    }

    public function _destroyPdo() {
        $this->pdo = NULL;
    }

    public function getLinkRecord($open_id) {
        $stat = $this->pdo->prepare(constant("select.linkRecord"));
        $stat->execute(array(':open_id' => $open_id));
        $record = $stat->fetchAll();
        $stat->closeCursor();
        return $record;
    }

    public function getLinkRecordByMd5($file_md5) {
        $stat = $this->pdo->prepare(constant("select.linkRecordByMd5"));
        $stat->execute(array(':file_md5' => $file_md5));
        $record = $stat->fetchAll();
        $stat->closeCursor();
        return $record;
    }

    public function recycLinkRecord($open_id) {
        $stat = $this->pdo->prepare(constant("update.recycLinkRecord"));
        $record = $stat->execute(array(':open_id' => $open_id));
        return $record;
    }

    public function recoverLinkRecord($open_id) {
        $stat = $this->pdo->prepare(constant("update.recoverLinkRecord"));
        $record = $stat->execute(array(':open_id' => $open_id));
        return $record;
    }

    public function regLinkRecordToken($open_id, $newTokenList) {
        $tokenList = [];
        $validate = new \validate();
        foreach ($newTokenList as $key => $value) {
            if ($validate->isTokenTimeOut($value->value)) {
                continue;
            }
            $tokenList[$key] = $value;
        }

        $stat = $this->pdo->prepare(constant("update.registerTokenList"));
        $record = $stat->execute(array(':open_id' => $open_id, ':token_list' => json_encode($tokenList)));
        return $record;
    }

    public function updateOpenId($openId, $newOpenID) {
        $stat = $this->pdo->prepare(constant("update.updateOpenId"));
        $record = $stat->execute(array(':open_id' => $openId, ':new_open_id' => $newOpenID));
        return $record;
    }

}
