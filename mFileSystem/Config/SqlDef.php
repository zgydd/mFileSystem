<?php

// Service sample SQL
// Initlize    20170124    Joe

define('insert.linkRecord', <<<SQL
    INSERT INTO md_stores_link (
        OPEN_ID, 
        STORE_URI,
        FILE_NAME,
        UPLOAD_DATE,
        FILE_TYPE
    ) VALUES (
        :open_id,
        :store_uri,
        :file_name,
        :upload_date,
        :file_type
    )
SQL
);

define('select.linkRecord', <<<SQL
    SELECT * FROM md_stores_link WHERE open_id = :open_id AND recyc_flg = 0
SQL
);

define('select.recycLinkRecord', <<<SQL
    UPDATE md_stores_link SET recyc_flg = 1 WHERE open_id = :open_id
SQL
);
