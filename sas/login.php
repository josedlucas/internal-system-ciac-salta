<?php
require_once ("../engine/config.php");
requerir_class("tpl","mysql","querys","estructura");

unset($_SESSION["USUARIO"]);
session_destroy();

$obj_estructura = new Estructura();
$htm_gral = $obj_estructura->html("gral_login");

$htm_login = $obj_estructura->html("sas/login");

switch ($_GET['err']) {
    case "1": $error = 'La sesi&oacute;n ha expirado. Por favor identif&iacute;quese nuevamente. Muchas Gracias!'; break;
    default: $error = ''; break;
}
$htm_gral->Asigna("ERROR", $error);

$htm_gral->Asigna("TITULO_SISTEMA", "SAS - Sistema de Administraci&oacute;n de Secretar&iacute;a");
$htm_gral->Asigna("CUERPO", $htm_login->Muestra());

CargarVariablesGrales($htm_gral);

echo utf8_decode($htm_gral->Muestra());
