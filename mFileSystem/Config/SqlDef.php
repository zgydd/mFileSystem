<?php

// Service sample SQL
// Initlize    20170124    Joe

define('insert.linkRecord', <<<SQL
    INSERT INTO files_link (
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
    SELECT * FROM files_link WHERE open_id = :open_id
SQL
);
