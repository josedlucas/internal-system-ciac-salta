<?php
function upper($str)
{
    $arrAcentos = array('á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü');
    $arrReemplz = array('Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü');
    $str = str_replace($arrAcentos, $arrReemplz, $str);

    $str = utf8_encode($str);

    $arrAcentos = array('á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü');
    $arrReemplz = array('Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü');
    $str = str_replace($arrAcentos, $arrReemplz, $str);

    $str = utf8_decode($str);

    return strtoupper($str);
}

function lower($str)
{
    $arrAcentos = array('Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü');
    $arrReemplz = array('á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü');
    $str = str_replace($arrAcentos, $arrReemplz, $str);
    return strtolower($str);
}

function doSaludo($rsMedico, $prefix = true) {
    $str = "";
    if ($prefix) {
        switch (lower($rsMedico['saludo'])) {
            case "dr.":
                $str.= "el ";
                break;
            case "dra.":
                $str.= "la ";
                break;
        }
    }
    $str.= ucwords(lower(trim($rsMedico['saludo'])));
    $str.= " ";
    $str.= upper(trim($rsMedico['apellidos']));
    $str.= ", ";
    $str.= ucwords(lower(trim($rsMedico['nombres'])));
    return $str;
}

interface iQuerys{

}

class Querys implements iQuerys{

	function __construct(){

    }

	function Registro($tabla,$id){
		switch ($tabla){
		    case 'consultorios':
				$query = "SELECT *
					FROM medicos_horarios
					WHERE
                        nro_consultorio = '".$id."' AND
                        nro_consultorio IS NOT NULL
                    GROUP BY nro_consultorio";
                break;
		    case 'disponibilidades':
				$query = "SELECT *
					FROM dias_semana
					WHERE
                        id_dias_semana = ".$id;
                break;
			default:
				$query = "SELECT *
					FROM ".$tabla."
					WHERE id_".$tabla." = ".$id;
                break;
		}
		//echo $query;
		return $query;
	}

	function TodosRegistros($tabla, $orden, $inicio = "", $cantidad = ""){

		switch($tabla){
			case 'medicos':
			case 'usuarios':
				$query = "SELECT * FROM ".$tabla." WHERE estado = 1 ORDER BY apellidos ".$orden;
			break;
			case 'obras_sociales':
			case 'estudios':
				$query = "SELECT * FROM ".$tabla." WHERE estado = 1 ORDER BY nombre ".$orden;
			break;
			default:
				$query = "SELECT * FROM ".$tabla." ORDER BY id_".$tabla." ".$orden;
		}

		if ($inicio != ""){
			$query .= " LIMIT $inicio, $cantidad";

		}
		return $query;

	}

	function Registros($tabla, $atributo, $valor, $ordenar = "", $inicio = "", $final = "", $orden = "DESC"){
		if ($ordenar == "")
			$query = "SELECT * FROM ".$tabla." WHERE ".$atributo."=".$valor." ORDER BY id_".$tabla." ".$orden;
		else
			$query = "SELECT * FROM ".$tabla." WHERE ".$atributo."=".$valor." ORDER BY ".$ordenar." ".$orden;
		if (($inicio != "") && ($final != "")){
			$query .= " LIMIT ".$inicio.",".$final;
		}
		return $query;
	}

	function RegistroXAtributo($tabla, $atributo, $valor, $tipo){
		if ($tipo == ""){
            if ($tabla == "pacientes" and $atributo == "nro_documento") {
                $query = "SELECT * FROM ".$tabla." WHERE ".$atributo." LIKE '%".mysql_real_escape_string(utf8_decode($valor))."%' AND estado = '1'";
            } else {
    			$query = "SELECT * FROM ".$tabla." WHERE ".$atributo." = '".mysql_real_escape_string(utf8_decode($valor))."'";
            }
		} else {
			$query = "SELECT * FROM ".$tabla." WHERE ".$atributo." LIKE '%".mysql_real_escape_string(utf8_decode($valor))."%'";
		}
		return $query;
	}

    function ValidaLogueo($tabla, $usuario, $pass){
            $query = "SELECT * FROM ".$tabla."
                WHERE
                    usuario = '". mysql_real_escape_string($usuario)."' AND
                    usuario LIKE '". mysql_real_escape_string($usuario)."' AND
                    pass = '". mysql_real_escape_string($pass) ."'";
            error_log($query);
            return utf8_decode($query);
    }

    function usuariosLogueados()
    {
        $query =   "SELECT
                    u.id_usuarios, 
                    CONCAT(u.apellidos,' ',u.nombres) as nombre_completo , 
                    u.session_state
                    FROM usuarios as u
                    LEFT JOIN chats ON u.id_usuarios = chats.id_usuarios
                    GROUP BY u.id_usuarios
                    ORDER BY u.session_state DESC, nombre_completo ASC";
        return $query;
    }

    function contarMensajesSinLeerMedico($id_medico, $id_usuario){
        $query =    "SELECT 
                    COUNT(view_medico) as count
                    FROM chats
                    WHERE id_medicos = ".$id_medico." AND id_usuarios =".$id_usuario;
        return $query;
    }

    function contarMensajesSinLeerUsuario($id_medico, $id_usuario){
        $query =    "SELECT 
                    COUNT(view_usuario) as count
                    FROM chats
                    WHERE id_medicos = ".$id_medico." AND id_usuarios =".$id_usuario;
        return $query;
    }

    function loadChat($id_medico, $id_usuario)
    {
        $query = "SELECT * FROM chats WHERE id_usuarios = ".$id_usuario." AND id_medicos = ".$id_medico;
        return $query;
    }

    function medicosLogueados()
    {
        $query =   "SELECT 
                    m.id_medicos, 
                    CONCAT(m.apellidos,' ',m.nombres) as nombre_completo , 
                    m.session_state
                    FROM medicos as m
                    LEFT JOIN chats ON m.id_medicos = chats.id_medicos
                    GROUP BY m.id_medicos
                    ORDER BY m.session_state DESC, nombre_completo ASC";
        return $query;
    }

    function session_state_change($tabla, $id, $state){
        if ($state == 'null'){
            $query = "UPDATE ".$tabla." SET session_state = null WHERE id_".$tabla." = ".$id;
        }else{
            $query = "UPDATE ".$tabla." SET session_state = '".$state."' WHERE id_".$tabla." = ".$id;
        }

        return $query;
    }

	function CambiarEstado($tabla, $id, $id_estado){
		$query = "UPDATE ".$tabla." SET id_".$tabla."_estados = $id_estado WHERE id_".$tabla." = $id";
		return $query;

	}
	function Alta($tabla, $columnas, $valores){
		$hora_actual = date("H:i:s");
		$fecha_actual = date('Y-m-d');
		$fecha_hora_actual = date("Y-m-d H:i:s");

		switch ($tabla){
			case "turnos":
			case "turnos_estudios":
			case "mensajes":
			case "pacientes":
			case "pacientes_observaciones":
			case "medicos":
			case "medicosext":
			case "cobros":
			case "egresos":
			case "especialidades":
			case "estudios":
			case "obras_sociales":
			case "medicos_especialidades":
			case "medicos_horarios":
			case "medicos_estudios":
			case "obras_sociales_estudios":
			case "obras_sociales_planes":
			case "medicos_obras_sociales":
			case "horarios_inhabilitados":
			case "turnos_cambios_estados":
            case "sectores":
            case "subsectores":
            case "novedades_diarias":
            case "agendas":
            case "mantenimientos":
            case "mantenimhistoricos":
            case "usuarios":
            case "notas_impresion":
            case "planes_de_contingencia":
            case "tareas_configuracion":
            case "tareas_requisitos":
            case "tareas_pedidos":
				$query = "INSERT INTO ".$tabla." ".$columnas." VALUES ".$valores;
			break;
			case "medicos_cancelaciones":

				parse_str($valores, $datosv);

				$valores =
					$datosv["id_turno"].",
					'".$fecha_actual."',
					'".$hora_actual."',
					'INHABILITACION DE HORARIO',".
					$_SESSION['ID_USUARIO'].",
					1"
				;
				$query = "INSERT INTO ".$tabla." (".$columnas.") VALUES (".$valores.")";
			break;
		}
		//echo ($query);
		return $query;
	}

	function Modificaciones($tabla, $asignacion, $id){
		$hora_actual = date("H:i:s");
		$fecha_actual = date('Y-m-d');
		$fecha_hora_actual = date("Y-m-d H:i:s");

		switch ($tabla){
            case "medicos_especialidades":
                $query = "UPDATE ".$tabla." SET ".$asignacion." WHERE id_medicos = ".$id[0]. " AND id_especialidades = ".$id[1];
            break;
			case "pacientes":
			case "pacientes_observaciones":
			case "medicos":
			case "medicosext":
			case "medicos_horarios":
			case "medicos_estudios":
			case "medicos_obras_sociales":
			case "especialidades":
			case "estudios":
			case "obras_sociales":
			case "obras_sociales_estudios":
			case "obras_sociales_planes":
            case "sectores":
            case "subsectores":
            case "novedades_diarias":
            case "agendas":
            case "turnos_tipos":
            case "mantenimientos":
            case "usuarios":
            case "notas_impresion":
            case "planes_de_contingencia":
            case "tareas_configuracion":
            case "tareas_requisitos":
            case "tareas_pedidos":
            case "tareas_realizadas":
				$query = "UPDATE ".$tabla." SET ".$asignacion." WHERE id_".$tabla." = ".$id;
			break;
		}
		return $query;
	}

	function Baja($tabla, $id){
		$query = "DELETE FROM ".$tabla." WHERE id_".$tabla." = ".$id;
		//echo $query;
		return $query;
	}

	function Inhabilitar($tabla, $id){
		$query = "UPDATE ".$tabla." SET estado = 0 WHERE id_".$tabla." = ".$id;
		//echo $query;
		return $query;
	}

	function BajaCobrosxMedicos($id_turno){
		$query = 'DELETE FROM cobros WHERE id_turnos = '.$id_turno;
		return $query;
	}

	function DelDia($id, $tabla_padre){
		$fecha_actual = date('Y-m-d');
		/*$query = "SELECT DISTINCT R.id_recordatorios,  R.nombre, CA.id_casos, CA.carpeta_legajo, CA.responsable, CL.nombre as NOMBRE_CLIENTE
		FROM recordatorios R
		INNER JOIN casos  CA ON R.id_casos = CA.id_casos
		INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes
		WHERE (R.fecha_recordatorio = '".$fecha_actual."'";*/

		$query = "SELECT DISTINCT R.id_recordatorios, R.fecha_recordatorio,  R.nombre, CA.id_casos, CA.carpeta_legajo, CA.responsable, CL.nombre as NOMBRE_CLIENTE
		FROM recordatorios R
		INNER JOIN casos  CA ON R.id_casos = CA.id_casos
		INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes
		WHERE (R.estado = 0 AND R.fecha_recordatorio <= '".$fecha_actual."' ";



		switch ($tabla_padre){
			case "empresas":
				$query .= "AND CL.id_".$tabla_padre." = $id";
			break;

			case "clientes":
				$query .= "AND CA.id_".$tabla_padre." = $id";
		}

		$query .= ") ORDER BY R.fecha_recordatorio";

		////error_log(print_r($query,1));
		return $query;
	}

	function ExisteCarpeta($carpeta_legajo, $id_cliente){
		$query = "SELECT id_casos FROM casos WHERE carpeta_legajo = '".$carpeta_legajo."' AND id_clientes= $id_cliente";
		return $query;
	}

	function ConsultaPagosXClientes($id_cliente, $fecha_inicio, $fecha_fin){
		$query = "SELECT
		CA.responsable AS responsable,
		CA.carpeta_legajo AS carpeta_legajo,
		CA.id_tipos_conceptos,
		P.*
		FROM pagos P INNER JOIN casos CA ON P.id_casos = CA.id_casos
		INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes
		WHERE CL.id_clientes = ".$id_cliente." AND P.fecha >= '".$fecha_inicio."'  AND P.fecha <= '".$fecha_fin."' ORDER BY P.fecha_pago DESC";
		return $query;

	}

	/*ESTE ANDA BIEN, PERO NO LE SIRVE A MACARENA... PORQUE TIENE EL RENDIDO O NO, Y YA LO DEJO DE USAR...
	function ConsultaPagosXClientes($id_cliente, $fecha_inicio, $fecha_fin){
		$query = "SELECT
		CA.responsable AS responsable,
		CA.carpeta_legajo AS carpeta_legajo,
		CA.id_tipos_conceptos,
		P.*
		FROM pagos P INNER JOIN casos CA ON P.id_casos = CA.id_casos
		INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes
		WHERE CL.id_clientes = ".$id_cliente." AND P.fecha >= '".$fecha_inicio."'  AND P.fecha <= '".$fecha_fin."' AND P.rendido = 0 ORDER BY P.fecha_pago DESC";
		return $query;

	}*/

	function ConsultaGestionesXClientes($id_cliente, $fecha_inicio, $fecha_fin){
		$query = "SELECT
		CA.responsable AS responsable,
		CA.carpeta_legajo AS carpeta_legajo,
		CA.id_tipos_conceptos,
		G.*
		FROM gestiones G INNER JOIN casos CA ON G.id_casos = CA.id_casos
		INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes
		WHERE CL.id_clientes = ".$id_cliente." AND G.fecha >= '".$fecha_inicio."'  AND G.fecha <= '".$fecha_fin."' ORDER BY CA.responsable, G.fecha_gestion DESC";
		return $query;

	}

	function ActualizarAccesos($tabla, $id, $datos){
		$query = "UPDATE ".$tabla." SET accesos = '".$datos."' WHERE id_".$tabla." = $id";
		return $query;
	}

	function ListadoReportes($tabla, $id, $tipos_conceptos){
		switch ($tabla){
			case "clientes":
				$query = "SELECT * FROM casos WHERE id_".$tabla." = $id AND id_tipos_conceptos = ".$tipos_conceptos;
			break;
			case "empresas":
				$query = "SELECT *
						FROM casos CA INNER JOIN clientes CL ON CA.id_clientes = CL.id_clientes WHERE CL.id_".$tabla." = $id AND CA.id_tipos_conceptos = ".$tipos_conceptos;
			break;
		}
		return $query;
	}

	function Buscar($tabla, $termino){
		switch ($tabla){
			case "pacientes":
				$query = "SELECT * FROM ".$tabla." WHERE estado = 1 AND nro_documento = '".$termino."'";
			break;
			case "medicos":
				$query = "SELECT * FROM ".$tabla." WHERE (nombres LIKE '%".$termino."%' OR apellidos LIKE '%".$termino."%') AND estado = 1";
			break;
		}

		return $query;

	}

	function DiasTrabajo($id_medico, $id_especialidad){
		$query = "SELECT DISTINCT id_dias_semana FROM medicos_horarios WHERE id_medicos = $id_medico AND id_especialidades = $id_especialidad AND estado = 1";
		return $query;
	}

	function GrillaTurnos($id_medico, $id_especialidad, $id_dia){
		$query = <<<SQL
            SELECT
                MH.*,
                M.interno
        	FROM
                medicos_horarios MH
            INNER JOIN
                medicos M
                ON MH.id_medicos = M.id_medicos
        	WHERE
                MH.id_medicos = $id_medico AND
                MH.id_especialidades = $id_especialidad AND
                MH.id_dias_semana = $id_dia AND
                MH.estado = 1
            ORDER BY
                MH.desde ASC
SQL;
		return $query;

	}

	function TurnosReservados($fecha, $id_medico, $id_especialidad, $id_dia){
		$query = "SELECT T.*, TE.*, P.*, OS.*, TE.nombre AS nombre_estado, OS.nombre AS nombre_os, U.apellidos AS uApellidos, U.usuario AS uUsuario, U.nombres AS uNombres, URec.apellidos AS recApellidos, URec.nombres AS recNombres, URec.usuario AS recUsuario
				FROM turnos T
				INNER JOIN turnos_estados TE
				ON T.id_turnos_estados = TE.id_turnos_estados
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				LEFT JOIN obras_sociales OS
				ON P.id_obras_sociales = OS.id_obras_sociales
                LEFT JOIN usuarios AS U
	            ON T.id_usuarios = U.id_usuarios
                LEFT JOIN usuarios AS URec
	            ON T.id_usuarios_recepcion = URec.id_usuarios
				WHERE T.id_medicos = $id_medico AND T.id_especialidades = $id_especialidad AND T.fecha = '".$fecha."' AND T.estado = 1 AND (T.id_turnos_estados = 1 OR T.id_turnos_estados = 2 OR T.id_turnos_estados = 4 OR T.id_turnos_estados = 7)
				ORDER BY T.desde ASC";
		return $query;
	}

    function TurnosReservadosEnMes($init, $end, $id_medico, $id_especialidad){
        $query = "SELECT T.*, TE.*, P.*, OS.*, TE.nombre AS nombre_estado, OS.nombre AS nombre_os, U.apellidos AS uApellidos, U.usuario AS uUsuario, U.nombres AS uNombres, URec.apellidos AS recApellidos, URec.nombres AS recNombres, URec.usuario AS recUsuario
				FROM turnos T
				INNER JOIN turnos_estados TE
				ON T.id_turnos_estados = TE.id_turnos_estados
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				LEFT JOIN obras_sociales OS
				ON P.id_obras_sociales = OS.id_obras_sociales
                LEFT JOIN usuarios AS U
	            ON T.id_usuarios = U.id_usuarios
                LEFT JOIN usuarios AS URec
	            ON T.id_usuarios_recepcion = URec.id_usuarios
				WHERE T.id_medicos = $id_medico AND T.id_especialidades = $id_especialidad AND T.fecha >= '".$init."' AND T.fecha <= '".$end."' AND T.estado = 1 AND (T.id_turnos_estados = 1 OR T.id_turnos_estados = 2 OR T.id_turnos_estados = 4 OR T.id_turnos_estados = 7)
				ORDER BY T.desde ASC";
        return $query;
    }

	function GrillaTurnosPasados($id_medico, $id_especialidad, $fecha){
		$query = "
            SELECT
                P.*,
                T.*,
                TE.*,
                OS.*,
                OS.nombre AS nombre_os,
                U.usuario AS uUsuario,
                U.apellidos AS uApellidos,
                U.nombres AS uNombres,
                URec.apellidos AS recApellidos, 
                URec.nombres AS recNombres, 
                URec.usuario AS recUsuario
            FROM
                turnos T
            INNER JOIN
                turnos_estados TE
                ON T.id_turnos_estados = TE.id_turnos_estados
            INNER JOIN
                pacientes P
                ON T.id_pacientes = P.id_pacientes
            LEFT JOIN
                obras_sociales OS
                ON P.id_obras_sociales = OS.id_obras_sociales
            LEFT JOIN
                usuarios AS U
                ON T.id_usuarios = U.id_usuarios
            LEFT JOIN 
                usuarios AS URec
	            ON T.id_usuarios_recepcion = URec.id_usuarios
            WHERE
                T.id_medicos = {$id_medico} AND
                T.id_especialidades = {$id_especialidad} AND
                T.fecha = '{$fecha}' AND
                T.estado = 1
            ORDER BY
                T.desde ASC
        ";

		return $query;

	}

	function GrillaTurnosImprimir($id_medico, $id_especialidad, $fecha, $horarios){
		$horariosv = explode("-",$horarios);

		$query = "
            SELECT
                DISTINCT T.desde,P.nombres, P.apellidos, T.id_turnos, OS.nombre AS nombre_os , OS.abreviacion
            FROM
                turnos T
            INNER JOIN
                turnos_estados TE
                ON T.id_turnos_estados = TE.id_turnos_estados
            INNER JOIN
                pacientes P
                ON T.id_pacientes = P.id_pacientes
            LEFT JOIN
                obras_sociales OS
                ON P.id_obras_sociales = OS.id_obras_sociales
            WHERE
                T.id_medicos = $id_medico AND
                T.id_especialidades = $id_especialidad AND
                T.fecha = '".$fecha."' AND
                T.estado = 1 AND (
                    T.id_turnos_estados = 1 OR
                    T.id_turnos_estados = 2 OR
                    T.id_turnos_estados = 3
                ) AND
                T.desde >= '".$horariosv[0]."' AND
                T.desde <= '".$horariosv[1]."'
            ORDER BY
                T.desde ASC
        ";

		return $query;

	}

	function GrillaCobrosImprimir($id_medico, $id_especialidad, $fecha, $horarios){
		$horariosv = explode("-",$horarios);

		$query = "SELECT *
				FROM turnos T
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				INNER JOIN obras_sociales OS
				ON P.id_obras_sociales = OS.id_obras_sociales
				WHERE T.id_medicos = $id_medico AND T.id_especialidades = $id_especialidad AND T.fecha = '".$fecha."' AND T.estado = 1 AND (T.id_turnos_estados = 2 OR T.id_turnos_estados = 7) AND T.desde >= '".$horariosv[0]."' AND T.desde <= '".$horariosv[1]."' ORDER BY T.desde ASC";

		return $query;

	}


	function CobrosTurnos($id_turnos){
		$query = "SELECT nombre AS nombre_cobros, importe
				FROM cobros C
				INNER JOIN cobros_conceptos CC
				ON C.id_cobros_conceptos = CC.id_cobros_conceptos
				WHERE C.id_turnos = $id_turnos";
		return $query;
	}

	function Cobros($id_medico, $fecha){
		$query = "SELECT *, CC.nombre AS nombre_cobro_concepto
				FROM cobros C
				INNER JOIN pacientes P
				ON C.id_pacientes = P.id_pacientes
				INNER JOIN turnos T
				ON C.id_turnos = T.id_turnos
				INNER JOIN cobros_conceptos CC
				ON C.id_cobros_conceptos = CC.id_cobros_conceptos
				WHERE C.id_medicos = $id_medico AND T.fecha = '".$fecha."'
				ORDER BY P.apellidos";
		return $query;

	}

	function Arancel($id_medico, $fecha){
		$query = "SELECT *
				FROM turnos T
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				WHERE T.id_medicos = $id_medico AND T.fecha = '".$fecha."' AND T.arancel_diferenciado <> 0
				ORDER BY P.apellidos";
		return $query;

	}

	function ExisteTurno($fecha, $inicio, $fin, $id_medico, $id_especialidad){
		/*$query = "SELECT *
				FROM turnos T
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				INNER JOIN turnos_estados TE
				ON T.id_turnos_estados = TE.id_turnos_estados
				WHERE T.fecha = '".$fecha."' AND T.desde = '".$inicio."' AND T.hasta = '".$fin."' AND T.id_medicos = $id_medico AND T.id_especialidades = $id_especialidad AND (T.id_turnos_estados = 1 OR T.id_turnos_estados = 2 OR T.id_turnos_estados = 3)";*/
		$query = "SELECT T.id_turnos, T.id_turnos_tipos, TE.id_turnos_estados, TE.color, TE.nombre AS nombre_estado, P.apellidos, P.nombres,  P.telefonos, OS.nombre AS nombre_os
				FROM turnos T
				LEFT JOIN turnos_estados TE
				ON T.id_turnos_estados = TE.id_turnos_estados
				LEFT JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				LEFT JOIN obras_sociales OS
				ON P.id_obras_sociales = OS.id_obras_sociales
				WHERE T.fecha = '".$fecha."' AND T.desde = '".$inicio."' AND T.id_medicos = $id_medico AND T.id_especialidades = $id_especialidad AND (T.id_turnos_estados = 1 OR T.id_turnos_estados = 2 OR T.id_turnos_estados = 3 OR T.id_turnos_estados = 4 OR T.id_turnos_estados = 7)";
		return $query;
	}

	function _HorariosInhabilitados($fecha, $inicio, $fin, $id_medico, $id_especialidad){
		$query = "SELECT *
				FROM horarios_inhabilitados
				WHERE fecha = '".$fecha."' AND (desde <= '".$inicio."' AND hasta >= '".$fin."') AND id_medicos = $id_medico AND id_especialidades = $id_especialidad";
		return $query;
	}

	function HorariosInhabilitados($fecha, $id_medico, $id_especialidad){
		$query = "
            SELECT
                `hi`.*,
                `him`.motivo_descripcion
			FROM
                horarios_inhabilitados AS `hi`
            LEFT JOIN
                horarios_inhabilitados_motivos AS `him`
                ON
                    `hi`.id_horarios_inhabilitados_motivos = `him`.id_horarios_inhabilitados_motivos
			WHERE
                `hi`.fecha = '$fecha' AND
                `hi`.id_medicos = $id_medico AND
                `hi`.id_especialidades = $id_especialidad
        ";
		return $query;
	}

	function DropHorariosInhabilitados($id_medico, $id_especialidad, $fecha){
		$query = "
            SELECT
                `hi`.*,
                `him`.motivo_descripcion
			FROM
                horarios_inhabilitados AS `hi`
            LEFT JOIN
                horarios_inhabilitados_motivos AS `him`
                ON
                    `hi`.id_horarios_inhabilitados_motivos = `him`.id_horarios_inhabilitados_motivos
			WHERE
                `hi`.fecha = '$fecha' AND
                `hi`.id_medicos = $id_medico AND
                `hi`.id_especialidades = $id_especialidad
        ";
		return $query;
	}

	function DropHorariosInhabilitadosPorFecha($id_medico, $id_especialidad){
        $fecha = date("Y-m-d");
		$query = "
            SELECT
                `hi`.*,
                `him`.motivo_descripcion,
                `him`.`bloqueo_superadmin`
			FROM
                horarios_inhabilitados AS `hi`
            LEFT JOIN
                horarios_inhabilitados_motivos AS `him`
                ON
                    `hi`.id_horarios_inhabilitados_motivos = `him`.id_horarios_inhabilitados_motivos
			WHERE
                `hi`.fecha >= '{$fecha}' AND
                `hi`.id_medicos = {$id_medico} AND
                `hi`.id_especialidades = {$id_especialidad}
            ORDER BY
                `hi`.fecha,
                `hi`.desde
        ";
		return $query;
	}

	function TipoHorario($dia, $inicio, $fin, $id_medico, $id_especialidad){
		$query = "SELECT *
				FROM medicos_horarios
				WHERE id_dias_semana = $dia AND desde <= '".$inicio."' AND hasta >= '".$fin."' AND id_medicos = $id_medico AND id_especialidades = $id_especialidad AND estado = 1";
		return $query;
	}

	function DropMedicosEspecialidades($id_medico){
		/*$query = "SELECT ME.*, M.*, E.nombre, E.id_especialidades
				FROM medicos_especialidades ME
				INNER JOIN medicos M
				ON ME.id_medicos = M.id_medicos
				INNER JOIN especialidades E
				ON ME.id_especialidades = E.id_especialidades
				WHERE ME.id_medicos = $id_medico AND ME.estado = 1 AND M.estado = 1 AND ME.id_medicos_especialidades =(SELECT MAX(id_medicos_especialidades) FROM medicos_especialidades WHERE id_medicos = $id_medico AND estado = 1 )
				ORDER BY ME.id_medicos_especialidades DESC"*/;
		$query = "SELECT  DISTINCT ME.id_especialidades, ME.id_medicos_especialidades, E.nombre, E.id_especialidades
				FROM medicos_especialidades ME
				INNER JOIN especialidades E
				ON ME.id_especialidades = E.id_especialidades
				WHERE ME.id_medicos = $id_medico AND ME.estado = 1
				GROUP BY ME.id_especialidades
				ORDER BY E.nombre ASC";
		return $query;
	}

	function EstudiosSeleccion($id_obra_social){
		$query = "SELECT E.*, E.importe as IMPORTE_PARTICULAR, OSE.importe as IMPORTE_OS
				FROM estudios E
				LEFT JOIN obras_sociales_estudios OSE
				ON E.id_estudios = OSE.id_estudios
				WHERE OSE.id_obras_sociales = $id_obra_social";
		return $query;
	}

	function EstudiosSeleccionMedicosOS($id_medico, $id_obra_social){
		/*$query = 'SELECT E.*, OSE.importe AS IMPORTE_OS, E.importe AS IMPORTE_PARTICULAR FROM estudios E
				INNER JOIN  medicos_estudios ME
				ON E.id_estudios = ME.id_estudios
				INNER JOIN obras_sociales_estudios OSE
				ON E.id_estudios = OSE.id_estudios
				WHERE ME.id_medicos = '.$id_medico.' AND OSE.id_obras_sociales = '.$id_obra_social;*/
		$query = 'SELECT E.*, OSE.importe AS IMPORTE_OS, E.importe AS IMPORTE_PARTICULAR FROM estudios E
				INNER JOIN  medicos_estudios ME
				ON E.id_estudios = ME.id_estudios
				LEFT JOIN obras_sociales_estudios OSE
				ON E.id_estudios = OSE.id_estudios
				WHERE ME.id_medicos = '.$id_medico;
		//error_log($query);
		return $query;

	}

	function EstudiosSeleccionados($id_turno, $id_medico){
		$query = "SELECT TE.id_estudios, ME.particular
				FROM turnos_estudios TE
				INNER JOIN estudios E
				ON TE.id_estudios = E.id_estudios
				LEFT JOIN medicos_estudios ME
				ON TE.id_estudios = ME.id_estudios
				WHERE TE.estado = 1 AND TE.id_turnos = $id_turno";
		return $query;
	}

	function ObrasSocialesPlanes(){
		$query = "SELECT CASE WHEN OSP.nombre is null THEN '' ELSE CONCAT('- ', OSP.nombre) END AS nombre_plan, CASE WHEN OSP.id_obras_sociales_planes is null THEN '0' ELSE OSP.id_obras_sociales_planes END AS id_obra_social_plan,  OS.*, OS.nombre AS nombre_os
				FROM obras_sociales OS
				LEFT JOIN  obras_sociales_planes OSP
				ON OS.id_obras_sociales = OSP.id_obras_sociales;";
		return $query;
	}

	function ObrasSocialesSeleccionadas($id_medico){
		$query = "SELECT * FROM medicos_obras_sociales WHERE id_medicos = $id_medico AND estado = 1";
		return $query;
	}

	function EstudiosSeleccionadosMedico($id_medico){
		$query = "SELECT * FROM medicos_estudios WHERE id_medicos = $id_medico";
		return $query;
	}

	function BajaxTurno($id_turno){
		$query = "UPDATE turnos_estudios SET estado = 0 WHERE id_turnos = ".$id_turno;
		return $query;
	}

	function BajaxMedico($id_medico){
		$query = "DELETE FROM medicos_obras_sociales WHERE id_medicos = ".$id_medico;
		return $query;
	}

	function Mensajes($id_emisor, $id_receptor){
		//$query = "SELECT * FROM mensajes WHERE (id_emisor = '".$id_emisor."' AND id_receptor = '".$id_receptor."') OR (id_emisor = '".$id_receptor."' AND id_receptor = '".$id_emisor."') ORDER BY fecha DESC, hora ASC";
		$query = "SELECT * FROM mensajes WHERE (id_emisor = '".$id_emisor."' AND id_receptor = '".$id_receptor."') OR (id_emisor = '".$id_receptor."' AND id_receptor = '".$id_emisor."') ORDER BY id_mensajes ASC";
		return $query;
	}

	function Totales($id_turno, $id_obra_social){
		$query = 'SELECT SUM(ME.particular) as PARTICULAR
					FROM medicos_estudios ME
					INNER JOIN turnos_estudios TE
					ON ME.id_estudios = TE.id_estudios
					WHERE TE.estado = 1 AND TE.id_turnos = '.$id_turno;
		/*$query = "SELECT SUM(E.importe) as PARTICULAR, SUM(OSE.importe) as OBRA_SOCIAL
				FROM turnos_estudios TE
				INNER JOIN estudios E
				ON TE.id_estudios = E.id_estudios
				INNER JOIN obras_sociales_estudios OSE
				ON E.id_estudios = OSE.id_estudios
				WHERE TE.id_turnos = $id_turno AND OSE.id_obras_sociales = $id_obra_social";*/
		return $query;
	}

	function Atiende($id_medico, $id_obra_social, $id_obra_social_plan){
		$query = "SELECT *
				FROM medicos_obras_sociales
				WHERE id_medicos = $id_medico AND id_obras_sociales = '$id_obra_social' AND estado = 1";
				//error_log($query);
		/*$query = "SELECT *
				FROM medicos_obras_sociales
				WHERE id_medicos = $id_medico AND id_obras_sociales = $id_obra_social AND id_obras_sociales_planes = $id_obra_social_plan";*/
		return $query;
	}

	function MensajesSinLeer($id_actor, $id_secundario){
			$query = "SELECT * FROM mensajes WHERE id_receptor = '".$id_actor."' AND leido = 0";
			return $query;

	}

	function ActualizarTipo($id_turno, $id_turno_tipo){
		$query = "UPDATE turnos SET id_turnos_tipos = $id_turno_tipo WHERE id_turnos = $id_turno";
		////error_log($query);
		return $query;
	}

	function ReintegroEfectuado($id_cobro){
		$query = 'UPDATE cobros SET reintegro = 1 WHERE id_cobros = '. $id_cobro;
		return $query;
	}

	function ArancelConsulta($id_medico, $id_obra_social){
		$query = 'SELECT * FROM medicos_obras_sociales WHERE id_medicos = '.$id_medico.' AND id_obras_sociales = \''.$id_obra_social.'\' AND estado = 1';
		return $query;
	}

	function EstudiosTurnos($id_turnos){
		$query = "SELECT DISTINCT E.id_estudios, E.nombre AS nombre_estudio, E.requisitos, E.codigopractica
				FROM turnos_estudios TE
				INNER JOIN estudios E
				ON TE.id_estudios = E.id_estudios
				WHERE TE.estado = 1 AND TE.id_turnos = $id_turnos";
		return $query;

	}

	function OrdenesyPedidos($id_turno, $trae_orden, $trae_pedido, $arancel_diferenciado){
		if ($trae_orden == 1){
			$query = 'UPDATE turnos SET trae_orden = 1, arancel_diferenciado = '.$arancel_diferenciado.' WHERE id_turnos = '.$id_turno;
		}
		if ($trae_pedido == 1){
			$query = 'UPDATE turnos SET trae_pedido = 1, arancel_diferenciado = '.$arancel_diferenciado.' WHERE id_turnos = '.$id_turno;
		}
		if ($trae_orden == 1 && $trae_pedido == 1){
			$query = 'UPDATE turnos SET trae_orden = 1, trae_pedido = 1, arancel_diferenciado = '.$arancel_diferenciado.' WHERE id_turnos = '.$id_turno;
		}

		if ($trae_orden == 0 && $trae_pedido == 0){
			$query = 'UPDATE turnos SET arancel_diferenciado = '.$arancel_diferenciado.' WHERE id_turnos = '.$id_turno;
		}
		return $query;

	}


	function ContadorOrdenesPedidos($id_medico, $fecha, $tipo){
		if ($tipo == 'pedidos'){
			$query = "SELECT id_turnos AS contador FROM turnos WHERE trae_pedido = 1 AND id_medicos = $id_medico AND fecha = '".$fecha."'";
		}else{
			$query = "SELECT id_turnos AS contador FROM turnos WHERE trae_orden = 1 AND id_medicos = $id_medico AND fecha = '".$fecha."'";
		}
		//echo($query);

		return $query;
	}

	function RangoTurnosDia($id_medico, $id_especialidad, $dia_semana){
		$query = "SELECT desde, hasta
				FROM medicos_horarios
				WHERE
                    id_medicos = $id_medico AND
                    id_especialidades = $id_especialidad AND
                    id_dias_semana = $dia_semana AND
                    estado = 1
                ORDER BY
                    desde
                ";
		return $query;
	}

	function DuracionTurno($id_medico, $id_especialidad){
		$query = "SELECT * FROM medicos_especialidades WHERE id_medicos = $id_medico AND id_especialidades = $id_especialidad ORDER BY id_medicos_especialidades DESC LIMIT 0,1";
		return  $query;
	}

    function DuracionTurnoDia($id_medico, $id_especialidad, $id_dias_semana){
		$query = "
        SELECT * FROM medicos_horarios AS mh
        INNER JOIN `dias_semana` AS `ds` ON `mh`.`id_dias_semana` = `ds`.`id_dias_semana`   
        WHERE mh.id_medicos = $id_medico AND 
        mh.id_especialidades = $id_especialidad AND 
        `ds`.`estado` = 1 AND
        `ds`.`id_dias_semana` = '{$id_dias_semana}' 
        ORDER BY id_medicos_horarios DESC LIMIT 0,1
        ";
		return  $query;
	}

	function RestablecerOrdenesyPedidos($id_turno){
		$query = "UPDATE turnos SET trae_pedido = 0, trae_orden = 0, arancel_diferenciado = 0 WHERE id_turnos = $id_turno";
		return  $query;
	}

	function ExisteTurnoReservado($fecha, $desde, $hasta, $id_medico, $id_especialidad){
		/*$query = "SELECT id_turnos
				FROM turnos
				WHERE id_medicos = $id_medico AND id_especialidades = $id_especialidad AND fecha = '".$fecha."' AND desde = '".$desde."' AND hasta = '".$hasta."' AND id_turnos_estados = 1 AND estado = 1";*/
		$query = "SELECT id_turnos
				FROM turnos
				WHERE id_medicos = $id_medico AND id_especialidades = $id_especialidad AND fecha = '".$fecha."' AND desde = '".$desde."' AND (id_turnos_estados = 1 OR id_turnos_estados = 2 OR id_turnos_estados = 4 OR id_turnos_estados = 7) AND estado = 1";
		//error_log($query);
		return $query;
	}

	function Duplicados($id_medico, $id_especialidad, $fecha){
		$query = "SELECT *, COUNT(desde) as TOTAL
				FROM turnos
				WHERE id_medicos = $id_medico AND id_especialidades = $id_especialidad AND fecha = '".$fecha."' AND estado = 1 AND (id_turnos_estados = 1 OR id_turnos_estados = 2 OR id_turnos_estados = 7)
				GROUP BY desde
				HAVING COUNT(desde) > 1	";
		return $query;
	}

	function MedicosSAM(){
		$query = "
            SELECT
                DISTINCT
                M.id_medicos,
                M.nombres,
                M.apellidos,
                E.nombre as especialidad,
                M.interno,
                M.matricula,
                M.id_sectores,
                M.nro_sector,
                M.telefonos,
                M.domicilio,
                M.email,
                P.nombre AS PLANTA
            FROM
                medicos M
            INNER JOIN
                medicos_especialidades ME
                ON M.id_medicos = ME.id_medicos
            INNER JOIN
                especialidades E
                ON ME.id_especialidades = E.id_especialidades
            LEFT JOIN
                plantas P
                ON M.id_plantas = P.id_plantas
            WHERE
                M.estado = 1 AND
                ME.estado = 1
        ";
		return $query;
	}

	function MedicosextSAM(){
		$query = "
            SELECT
                DISTINCT
                dx.id_medicosext,
                dx.nombres,
                dx.apellidos,
                dx.matricula
            FROM
                medicosext dx
            WHERE
                dx.estado = 1
        ";
		return $query;
	}

    function dataTurnosOtorgadosTotales($desde, $hasta, $id_usuarios){
        if ($id_usuarios > 0) {
            $where = "
                (
                    t.id_usuarios = '{$id_usuarios}' OR
                    t.id_usuarios = '1'
                ) AND
            ";
        } else {
            $where = "";
        }
		$query = "
            SELECT
                t.id_usuarios,
                u.apellidos,
                u.nombres,
                COUNT(t.id_turnos) AS `count`
            FROM
                turnos AS t
            INNER JOIN
                usuarios AS u
                ON t.id_usuarios = u.id_usuarios
            WHERE
                {$where}
                t.fecha_alta BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
                t.id_usuarios
            ORDER BY
                COUNT(t.id_turnos) DESC
        ";
		return $query;
    }

    function dataTurnosPorMedicos($desde, $hasta, $id_usuarios){
        if ($id_usuarios > 0) {
            $where = "
                (
                    t.id_usuarios = '{$id_usuarios}' OR
                    t.id_usuarios = '1'
                ) AND
            ";
        } else {
            $where = "";
        }
		$query = "
            SELECT
                t.id_medicos,
                m.apellidos,
                m.nombres,
                COUNT(t.id_turnos) AS `count`
            FROM
                turnos AS t
            INNER JOIN
                medicos AS m
                ON t.id_medicos = m.id_medicos
            WHERE
                {$where}
                m.estado = 1 AND
                t.fecha_alta BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
                t.id_medicos
            ORDER BY
                COUNT(t.id_turnos) DESC
        ";
		return $query;
    }

    function dataTurnosOtorgadosPorDia($desde, $hasta, $id_usuarios){
        if ($id_usuarios == 0) {
            $where = "";
        } else {
            $where = "
                t.id_usuarios = {$id_usuarios} AND
            ";
        }
		$query = "
            SELECT
                t.fecha_alta,
                COUNT(t.id_turnos) AS `count`
            FROM
                turnos AS t
            WHERE
                {$where}
                t.fecha_alta BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
                t.fecha_alta
        ";
		return $query;
    }

    function dataTurnosOtorgadosPorOS($desde, $hasta, $id_usuarios){
        if ($id_usuarios == 0) {
            $where = "";
        } else {
            $where = "
                t.id_usuarios = {$id_usuarios} AND
            ";
        }
		$query = "
            SELECT
                os.abreviacion,
                COUNT(t.id_turnos) AS `count`
            FROM
                turnos AS t
            INNER JOIN
                pacientes AS p
                ON t.id_pacientes = p.id_pacientes
            INNER JOIN
                obras_sociales AS os
                ON p.id_obras_sociales = os.id_obras_sociales
            WHERE
                {$where}
                t.fecha_alta BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
                p.id_obras_sociales
            ORDER BY
                COUNT(t.id_turnos) DESC
        ";
		return $query;
    }

    function dataTurnosOtorgadosPorEST($desde, $hasta, $id_usuarios){
        if ($id_usuarios == 0) {
            $where = "";
        } else {
            $where = "
                t.id_usuarios = {$id_usuarios} AND
            ";
        }
		$query = "
            SELECT
            	e.nombre,
            	COUNT(t.id_turnos) AS `count`
            FROM
            	turnos AS t
            INNER JOIN
            	turnos_estudios AS te
            	ON te.id_turnos = t.id_turnos
            INNER JOIN
            	estudios AS e
                ON te.id_estudios = e.id_estudios
            WHERE
                {$where}
                te.estado = 1 AND
                t.fecha_alta BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
            	e.nombre
            ORDER BY
            	COUNT(t.id_turnos) DESC
        ";
		return $query;
    }

    function dataTurnosOtorgadosPorENC($desde, $hasta, $id_usuarios){
		$query =    "SELECT
                        ep.pregunta,
                        COUNT(t.id_turnos) AS `count`
                    FROM
                        turnos AS t
                    INNER JOIN
                        encuestas_respuestas AS er
                            ON er.id_turnos = t.id_turnos
                    INNER JOIN
                            encuestas_preguntas AS ep
                            ON er.id_encuestas_preguntas = ep.id_encuestas_preguntas
                    WHERE
                            t.id_turnos_estados IN ('2', '7') AND
                            t.estado = 1 
                    GROUP BY
                        ep.pregunta
                    ORDER BY
                        COUNT(t.id_turnos) DESC";
		return $query;
    }

    function dataTurnosOtorgadosPorDER($desde, $hasta, $id_usuarios){
		$query = "
            SELECT
                CONCAT(d.saludo, ' ', d.apellidos, ', ', d.nombres) AS medicos_derivacion,
                CONCAT(dx.saludo, ' ', dx.apellidos, ', ', dx.nombres) AS medicosext_derivacion,
            	COUNT(ts.id_turnos_estudios) AS `count`
            FROM
            	turnos_estudios AS ts
            LEFT JOIN
                medicos AS d
                ON
                    ts.matricula_derivacion = d.matricula AND
                    d.matricula != ''
            LEFT JOIN
                medicosext AS dx
                ON
                    ts.matricula_derivacion = dx.matricula AND
                    dx.estado = 1 AND dx.matricula != ''
            INNER JOIN
                turnos AS t
                ON ts.id_turnos = t.id_turnos
            WHERE
                ts.matricula_derivacion > 0 AND
                ts.estado = 1 AND
                t.id_turnos_estados IN ('2', '7') AND
                t.estado = 1 AND
                t.fecha BETWEEN '{$desde}' AND '{$hasta}'
            GROUP BY
            	ts.matricula_derivacion
            ORDER BY
            	COUNT(ts.id_turnos_estudios) DESC
            LIMIT 50
        ";
		return $query;
    }

    function obtMotivosDeInhabilitaciones(){
        if ($_SESSION['SUPERUSER'] < 2) {
            $where = "AND bloqueo_superadmin = 0";
        } else {
            $where = "";

        }
        $query = "
            SELECT
                *
            FROM
                `horarios_inhabilitados_motivos`
            WHERE
                `id_horarios_inhabilitados_motivos` > 1
                {$where}                AND
                `id_horarios_inhabilitados_motivos` != 5
            ORDER BY
                `motivo_orden`, `motivo_descripcion`
        ";
        return $query;
    }

    function obtNotificacionesDeMantenimientos($aIn) {
        $query = "
            SELECT
                `me`.`nombre`,
                `m`.*
            FROM
                `mantenimientos` AS `m`
            INNER JOIN
                `mantenimientos_estados` AS `me`
                ON
                    `me`.`id_mantenimientos_estados` = `m`.`id_mantenimientos_estados`
            WHERE
                `m`.`id_mantenimientos_estados` NOT IN(".implode(", ", $aIn).")
            ORDER BY
                `me`.`id_mantenimientos_estados` ASC,
                `m`.`tarea`
        ";
        return $query;
    }

    function DetalleConsultorios($nro_consultorio) {
        $query = "
            SELECT
                `ds`.`nombre`,
                `m`.`saludo`,
                `m`.`apellidos`,
                `m`.`nombres`,
                `mh`.`desde`,
                `mh`.`hasta`,
                `me`.`duracion_turno`,
                `e`.`nombre` AS `especialidad`
            FROM
                `medicos_horarios` AS `mh`
            INNER JOIN
                `medicos` AS `m`
                ON `m`.`id_medicos` = `mh`.`id_medicos`
            INNER JOIN
                `dias_semana` AS `ds`
                ON `mh`.`id_dias_semana` = `ds`.`id_dias_semana`
            INNER JOIN
                `medicos_especialidades` AS `me`
                ON
                    `mh`.`id_especialidades` = `me`.`id_especialidades` AND
                    `mh`.`id_medicos` = `me`.`id_medicos`
            INNER JOIN
                `especialidades` AS `e`
                ON `me`.`id_especialidades` = `e`.`id_especialidades`
            WHERE
                `mh`.`estado` = 1 AND
                `mh`.`nro_consultorio` IS NOT NULL AND
                `m`.`estado` = 1 AND
                `ds`.`estado` = 1 AND
                `me`.`estado` = 1 AND
                `mh`.`nro_consultorio` = '{$nro_consultorio}'
            ORDER BY
                `mh`.`id_dias_semana`,
                `mh`.`desde`,
                `m`.`apellidos`
        ";
        return $query;
    }

    function DetalleMedicosConsultorios($id_medicos) {
        $query = "
            SELECT
                `ds`.`nombre`,
                `m`.`saludo`,
                `m`.`apellidos`,
                `m`.`nombres`,
                `mh`.`desde`,
                `mh`.`hasta`,
                `me`.`duracion_turno`,
                `mh`.`nro_consultorio`,
                `e`.`nombre` AS `especialidad`
            FROM
                `medicos_horarios` AS `mh`
            INNER JOIN
                `medicos` AS `m`
                ON `m`.`id_medicos` = `mh`.`id_medicos`
            INNER JOIN
                `dias_semana` AS `ds`
                ON `mh`.`id_dias_semana` = `ds`.`id_dias_semana`
            INNER JOIN
                `medicos_especialidades` AS `me`
                ON
                    `mh`.`id_especialidades` = `me`.`id_especialidades` AND
                    `mh`.`id_medicos` = `me`.`id_medicos`
            INNER JOIN
                `especialidades` AS `e`
                ON `me`.`id_especialidades` = `e`.`id_especialidades`
            WHERE
                `mh`.`estado` = 1 AND
                `m`.`estado` = 1 AND
                `ds`.`estado` = 1 AND
                `me`.`estado` = 1 AND
                `mh`.`id_medicos` = '{$id_medicos}'
            ORDER BY
                `mh`.`id_dias_semana`,
                `mh`.`desde`,
                `m`.`apellidos`
        ";
        return $query;
    }

    function DetalleDiaDisponibilidad($id_dias_semana) {
        $query = "
            SELECT
                `ds`.`nombre`,
                `m`.`saludo`,
                `m`.`apellidos`,
                `m`.`nombres`,
                `mh`.`desde`,
                `mh`.`hasta`,
                `me`.`duracion_turno`,
                `mh`.`nro_consultorio`,
                `e`.`nombre` AS `especialidad`
            FROM
                `medicos_horarios` AS `mh`
            INNER JOIN
                `medicos` AS `m`
                ON `m`.`id_medicos` = `mh`.`id_medicos`
            INNER JOIN
                `dias_semana` AS `ds`
                ON `mh`.`id_dias_semana` = `ds`.`id_dias_semana`
            INNER JOIN
                `medicos_especialidades` AS `me`
                ON
                    `mh`.`id_especialidades` = `me`.`id_especialidades` AND
                    `mh`.`id_medicos` = `me`.`id_medicos`
            INNER JOIN
                `especialidades` AS `e`
                ON `me`.`id_especialidades` = `e`.`id_especialidades`
            WHERE
                `mh`.`estado` = 1 AND
                `mh`.`nro_consultorio` IS NOT NULL AND
                `m`.`estado` = 1 AND
                `me`.`estado` = 1 AND
                `ds`.`estado` = 1 AND
                `ds`.`id_dias_semana` = '{$id_dias_semana}'
            ORDER BY
                `mh`.`id_dias_semana`,
                `mh`.`nro_consultorio`,
                `mh`.`desde`,
                `mh`.`hasta`
        ";
        return $query;
    }

    function obtAGENDAS_OPTIONS()
    {
        $query = <<<SQL
            SELECT
                m.id_medicos,
                e.id_especialidades,
                m.apellidos,
                m.nombres,
                e.nombre
            FROM medicos AS m
            INNER JOIN medicos_especialidades AS me
                ON me.id_medicos = m.id_medicos
            INNER JOIN especialidades AS e
                ON me.id_especialidades = e.id_especialidades
            WHERE
            	m.estado = 1 AND
            	me.estado = 1
            ORDER BY
            	m.apellidos,
            	m.nombres,
            	e.nombre
SQL;
        return $query;
    }

    function obtMEDICOS_OPTIONS()
    {
        $query = <<<SQL
            SELECT
                m.id_medicos,
                m.apellidos,
                m.nombres
            FROM medicos AS m
            WHERE
            	m.estado = 1 
            ORDER BY
            	m.apellidos,
            	m.nombres
SQL;
        return $query;
    }

	function ObservacionesDePacientes($id_pacientes){
		$query = <<<SQL
            SELECT
                po.*,
                u.usuario
            FROM
                pacientes_observaciones AS po
            LEFT JOIN
                usuarios AS u
                ON po.id_usuarios = u.id_usuarios
            WHERE
                po.estado = 1 AND
                po.id_pacientes = '{$id_pacientes}'
            ORDER BY
                po.fechahora DESC
            LIMIT
                3
SQL;
		return $query;

	}

}
