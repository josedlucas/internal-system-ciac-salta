<?php
interface iMedicos_especialidades{

}

class Medicos_especialidades extends Estructura implements iMedicos_especialidades{
	
	function __construct($id = ""){ 
		$this->nombre_tabla = "medicos_especialidades";
		$this->titulo_tabla = "Especialidades de Médicos";
		$this->titulo_tabla_singular = "Especialidad del Médico";
		$this->tabla_padre = "medicos";
		
		$this->drop_label_elija = "Elija una Especialidad";
		
		parent::__construct($id);
		
		
	} 

	function FormAlta($id_padre){
		$htm = $this->Html($this->nombre_tabla."/form_alta");

		requerir_class("especialidades", "medicos");
		
		$obj_especialidades = new Especialidades();
		$htm->Asigna("DROP_ESPECIALIDADES",$obj_especialidades->Drop());	
		
		$obj_medico = new Medicos($id_padre);
		$htm->Asigna("MEDICO",$obj_medico->apellidos.', '.$obj_medico->nombres);	
		
		$htm->Asigna("ID_MEDICO", $id_padre);
		$htm->Asigna("TABLA",$this->nombre_tabla);
		
		CargarVariablesGrales($htm, $tipo = "");
		
		return utf8_encode($htm->Muestra());
	}

	function FormModificacion($id_padre){
		$tabla = $this->html($this->nombre_tabla."/form_modificacion");

		if ($id_padre != ""){
			$idsv = explode("-", $id_padre);
			$id_medico = $idsv[0];
			$id_especialidad = $idsv[1];

			$tabla->Asigna("ID_MEDICO", $id_medico);
			$tabla->Asigna("ID_ESPECIALIDAD", $id_especialidad);

			$tabla->Asigna("ARGS","tabla=".$this->nombre_tabla."&id_medico=".$id_medico."&id_especialidad=".$id_especialidad);

			requerir_class ("medicos");
			$obj_medico = new Medicos($id_medico);
			$tabla->Asigna("DATOS_MEDICO",$obj_medico->Detalle("corto_especialidad", $id_especialidad));
		}else{
			$tabla->Asigna("ARGS","tabla=".$this->nombre_tabla);
		}
		$tabla->Asigna("TABLA",$this->nombre_tabla);

		CargarVariablesGrales($tabla, $tipo = "");
		
		return utf8_encode($tabla->Muestra());
	}
	
	function TablaAdmin($id_padre = ""){
		$tabla = $this->html($this->nombre_tabla."/a_tabla");
		
		requerir_class("medicos");
		
		if ($id_padre != ""){
			$tabla->Asigna("ARGS","tabla=".$this->nombre_tabla."&id=".$id_padre);	
			
			$obj_medico = new Medicos($id_padre);
			$tabla->Asigna("DATOS_MEDICO",$obj_medico->Detalle("corto"));
		}else{
			$tabla->Asigna("ARGS","tabla=".$this->nombre_tabla);	
		}
		
		$tabla->Asigna("NOMBRE_TABLA",$this->nombre_tabla);
		
		
		
		$rta = utf8_encode($tabla->Muestra());				
		
		return $rta;
	}
	
	function DuracionTurno($id_medico, $id_especialidad){
		$query_string = $this->querys->DuracionTurno($id_medico, $id_especialidad);
		$query = $this->db->consulta($query_string);
		$cant = $this->db->num_rows($query);
	
		$rta = $this->db->fetch_array($query);
		
		return $rta["duracion_turno"];	
	}
	
	//Kcmnt duracion turno dia
	function DuracionTurnoDia($id_medico, $id_especialidad, $id_dias_semana){
		$query_string = $this->querys->DuracionTurnoDia($id_medico, $id_especialidad, $id_dias_semana);
		$query = $this->db->consulta($query_string);
		$cant = $this->db->num_rows($query);
		$rta = $this->db->fetch_array($query);

		if ($rta['duracion_turno']==null || $rta['duracion_turno']=="00:00:00"){
			$query_string = $this->querys->DuracionTurno($id_medico, $id_especialidad);
			$query = $this->db->consulta($query_string);
			$cant = $this->db->num_rows($query);
			$rta = $this->db->fetch_array($query);
		}

		return $rta["duracion_turno"];	
	}

	function PanelAdmin($id_padre = ""){
		$htm = $this->Html($this->nombre_tabla."/panel_admin");

		$htm->Asigna("LISTADO", $this->TablaAdmin($id_padre));
		
		$htm->Asigna("ID_MEDICO", $id_padre);
		
		$htm->Asigna("TABLA", $this->nombre_tabla);
		$htm->Asigna("TITULO_TABLA", $this->titulo_tabla_singular);
		
		CargarVariablesGrales($htm, $tipo = "");
		
		echo ($htm->Muestra());
	}
}
?>