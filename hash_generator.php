<?php

$superAdminPassword = 'superAdm1n';
$regularAdminPassword = 'userAdm1n';

$superAdminHash = password_hash($superAdminPassword, PASSWORD_DEFAULT);
$regularAdminHash = password_hash($regularAdminPassword, PASSWORD_DEFAULT);

echo "Hash for superAdminPassword ('" . $superAdminPassword . "'): " . $superAdminHash . "\n";
echo "Hash for regularAdminPassword ('" . $regularAdminPassword . "'): " . $regularAdminHash . "\n";

?>