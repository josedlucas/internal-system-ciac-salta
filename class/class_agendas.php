<?php
interface iAgendas{

}

class Agendas extends Estructura implements iAgendas{

	function __construct($id = ""){
		$this->nombre_tabla = "agendas";
		$this->titulo_tabla = "Agendas";
		$this->tabla_padre = "";

		$this->drop_label_elija = "Elija una Agenda";

		parent::__construct($id);
	}

	function FormAlta(){
		$htm = $this->Html($this->nombre_tabla."/form_alta");

		requerir_class("agendas_tipos");

		$obj_agendas_tipos= new Agendas_Tipos();
		$htm->Asigna("DROP_AGENDAS_TIPOS",$obj_agendas_tipos->Drop("", 1));

		$htm->Asigna("TABLA",$this->nombre_tabla);

		CargarVariablesGrales($htm, $tipo = "");

		return ($htm->Muestra());
	}

	function FormModificacion(){
		$htm = $this->Html($this->nombre_tabla."/form_modificacion");
		$row = $this->registro;

		requerir_class("agendas_tipos");

		$obj_agendas_tipos= new Agendas_Tipos();
		$htm->Asigna("DROP_AGENDAS_TIPOS",$obj_agendas_tipos->Drop("", $row["id_agendas_tipos"]));

		$htm->Asigna("TABLA",$this->nombre_tabla);

		$htm->AsignaBloque('block_registros',$row);

		CargarVariablesGrales($htm, $tipo = "");

		return  utf8_encode($htm->Muestra());
	}

	function TablaAdmin(){
		$tabla = $this->html($this->nombre_tabla."/a_tabla");

		$tabla->Asigna("NOMBRE_TABLA",$this->nombre_tabla);

		$rta = utf8_encode($tabla->Muestra());

		return $rta;
	}

	function PanelAdmin(){
		$htm = $this->Html($this->nombre_tabla."/panel_admin");

		$htm->Asigna("LISTADO", $this->TablaAdmin());

		$htm->Asigna("TABLA", $this->nombre_tabla);
		$htm->Asigna("TITULO_TABLA", $this->titulo_tabla_singular);

		CargarVariablesGrales($htm, $tipo = "");

		echo ($htm->Muestra());

	}
}