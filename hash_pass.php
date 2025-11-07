<?php
// La contraseña en texto plano 
$contraseña_plana = 'adm123';

// Hashear la contraseña usando password_hash
$contraseña_hasheada = password_hash($contraseña_plana, PASSWORD_DEFAULT);

// Imprimir la contraseña hasheada
echo $contraseña_hasheada;
