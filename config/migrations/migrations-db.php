<?php

$envConfig = require '/etc/ents/unified-config.php';

return [
    'host'     => $envConfig['postgres-frisk.host'],
    'port'     => $envConfig['postgres-frisk.port'],
    'user'     => $envConfig['postgres-frisk.user'],
    'password' => $envConfig['postgres-frisk.password'],
    'dbname'   => $envConfig['postgres-frisk.dbname'],
    'driver'   => $envConfig['postgres-frisk.driver']
];
