<?php
require_once ("../engine/config.php");
require_once ("../engine/restringir_acceso.php");
requerir_class("tpl","mysql","querys","estructura");

//requerir_class("dias_semana");
$this_db = new MySQL();

$keys = array_keys($_POST);

$fechahora = date("Y-m-d H:i:s");

for ($i = 0; $i < count($_POST['id_turnos_estudios']); $i++) {
    $cnct = "\nSET";

    $deja_deposito = <<<SQL
        SELECT deja_deposito
        FROM turnos_estudios
        WHERE id_turnos_estudios = '{$_POST['id_turnos_estudios'][$i]}'
        LIMIT 1
SQL;
    $deja_deposito = $this_db->consulta($deja_deposito);

    $query_string = <<<SQL
UPDATE
    turnos_estudios
SQL;
    $query_historico_string = <<<SQL
INSERT INTO
    turnos_estudios_historicos
SQL;

    $query_historico_string.=
        $cnct."\n    `deja_deposito_fecha` = '".date("Y-m-d")."'"
    ;
    if ($deja_deposito = $this_db->fetch_array($deja_deposito)) {
        $query_historico_string.=
            ",\n    `deja_deposito_diferencia` = ".(
                $_POST['deja_deposito'][$i] -
                $deja_deposito['deja_deposito']
            );
        ;
    } else {
        $query_historico_string.=
            ",\n    `deja_deposito_diferencia` = '0'";
        ;
    }

    foreach ($keys AS $k) {
        if (!in_array($k, array('id_turno', 'id_turnos_estudios'))) {
            $query_string.= $cnct."\n    {$k} = ";
            $query_historico_string.= ",\n    {$k} = ";
            if ($k == 'fecha_presentacion' and $_POST[$k][$i]) {
                $_POST[$k][$i] = implode("-", array_reverse(explode("/", $_POST[$k][$i])));
            } elseif ($k == 'fecha_presentacion') {
                $_POST[$k][$i] = null;
            }
            if (isset($_POST[$k][$i]) and $_POST[$k][$i] !== '' and $_POST[$k][$i] !== '0' and $_POST[$k][$i] !== 0) {
                $query_string.= "'{$_POST[$k][$i]}'";
                $query_historico_string.= "'{$_POST[$k][$i]}'";
            } else {
                $query_string.= "NULL";
                $query_historico_string.= "NULL";
            }
            $cnct = ",";
        }
    }
    $query_string.= <<<SQL
\nWHERE
    id_turnos_estudios = '{$_POST['id_turnos_estudios'][$i]}'
SQL;
    $query_historico_string.= <<<SQL
\n,
    id_turnos_estudios = '{$_POST['id_turnos_estudios'][$i]}',
    id_usuarios = '{$_SESSION['ID_USUARIO']}',
    fechahora = '{$fechahora}'
SQL;
    #print "<pre>{$query_string}</pre>";
    $this_db->consulta($query_string);
    #print "<pre>{$query_historico_string}</pre>";
    $this_db->consulta($query_historico_string);
}
