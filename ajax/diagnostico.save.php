<?php
require_once ("../engine/config.php");
require_once ("../engine/restringir_acceso.php");
requerir_class("tpl","mysql","querys","estructura");

//requerir_class("dias_semana");
$this_db = new MySQL();

$keys = array_keys($_POST);

for ($i = 0; $i < count($_POST['id_turnos_estudios']); $i++) {
    $cnct = "\nSET";
    $query_string = <<<SQL
UPDATE
    turnos_estudios
SQL;
    foreach ($keys AS $k) {
        if (!in_array($k, array('id_turno', 'id_turnos_estudios'))) {
            $query_string.= $cnct."\n    {$k} = ";
            if ($k == 'fecha_presentacion' and $_POST[$k][$i]) {
                $_POST[$k][$i] = implode("-", array_reverse(explode("/", $_POST[$k][$i])));
            } elseif ($k == 'fecha_presentacion') {
                $_POST[$k][$i] = null;
            }
            if (isset($_POST[$k][$i]) and $_POST[$k][$i] !== '' and $_POST[$k][$i] !== '0' and $_POST[$k][$i] !== 0) {
                $query_string.= "'{$_POST[$k][$i]}'";
            } else {
                $query_string.= "NULL";
            }
            $cnct = ",";
        }
    }
    $query_string.= <<<SQL
\nWHERE
    id_turnos_estudios = '{$_POST['id_turnos_estudios'][$i]}'
SQL;
    print "<pre>{$query_string}</pre>";
    $this_db->consulta($query_string);
}