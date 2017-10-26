<?php

// Service sample SQL
// Initlize    20170124    Joe

define('insert.linkRecord', <<<SQL
    INSERT INTO md_stores_link (
        OPEN_ID, 
        STORE_URI,
        FILE_NAME,
        UPLOAD_DATE,
        FILE_TYPE,
        FILE_MD5
    ) VALUES (
        :open_id,
        :store_uri,
        :file_name,
        :upload_date,
        :file_type,
        :file_md5
    )
SQL
);

define('select.linkRecord', <<<SQL
    SELECT * FROM md_stores_link WHERE open_id = :open_id AND recyc_flg = 0
SQL
);

define('update.recycLinkRecord', <<<SQL
    UPDATE md_stores_link SET recyc_flg = 1 WHERE open_id = :open_id
SQL
);

define('update.recoverLinkRecord', <<<SQL
    UPDATE md_stores_link SET recyc_flg = 0 WHERE open_id = :open_id
SQL
);

define('update.registerTokenList', <<<SQL
    UPDATE md_stores_link SET token_list = :token_list WHERE open_id = :open_id
SQL
);

define('select.linkRecordByMd5', <<<SQL
    SELECT * FROM md_stores_link WHERE file_md5 = :file_md5
SQL
);


define('update.updateOpenId', <<<SQL
    UPDATE md_stores_link SET open_id = :new_open_id WHERE open_id = :open_id
SQL
);
