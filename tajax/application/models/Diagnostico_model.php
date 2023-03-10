<?php
class Diagnostico_model extends CI_Model
{

    // CONFIGURACIONES

    function validar()
    {
        $this->form_validation
            ->set_rules(
                'fecha',
                'Fecha',
                'trim|required'
            )
            ->set_rules(
                'apellido',
                'Apellido',
                'trim|required|min_length[5]|max_length[50]'
            )
            ->set_rules(
                'sexoid',
                'Sexo',
                'trim|required'
            )
            ->set_rules(
                'fechanac',
                'Fecha de Nacimiento',
                'trim|required'
            )
            ->set_rules(
                'dni',
                'DNI',
                'trim|required|min_length[5]|max_length[15]|alpha_numeric'
            )
            ->set_rules(
                'estado',
                'Estado',
                'trim|required'
            )
        ;
        return ($this->form_validation->run() != FALSE);
    }

    function obtenerDropDownsRel()
    {
        #$this->load->model('Sexo_model', 'Sexo');
        #$ddRel['sexo_listado'] = $this->Sexo->obtenerListado();
        #return $ddRel;
    }

    // CONSULTA DE LECTURA

    function obtenerItem($id)
    {
        $query = $this->db
            ->from('turnos')
            ->limit(1)
            ->get()
            ->result_array()
        ;
        if (count($query) > 0) {
            #$query[0]['fechanac'] = date_view($query[0]['fechanac']);
            return $query[0];
        } else {
            return null;
        }
    }

    protected function _filtroListado($date1, $date2, $post, $cualFecha, $id_usuario = null, $isMedico = false)
    {
        if (!isset($cualFecha)) {
            $cualFecha = 't.fecha';
        }
        $this->db
            #->where('tt.tipo', 'ESTUDIOS')
            ->where_in('t.id_turnos_estados', array(2, 7))
            ->where('t.estado', 1)
            ->where("CONCAT({$cualFecha}, ' ', t.desde) BETWEEN '{$date1} 00:00:00' AND '{$date2} 23:59:00'")
            ->where("t.desde BETWEEN '{$post['hour3']}:00' AND '{$post['hour4']}:00'")
        ;
        if (
            $isMedico and
            ($id_usuario - 1000000) > 0
        ) {
            $this->db->where('t.id_medicos', ($id_usuario - 1000000));
        }

        $aPost = array(
            'spac' => "CONCAT(TRIM(p.apellidos), ', ', TRIM(p.nombres))",
            'susu' => "u.usuario",
            'sces' => "e.codigopractica",
            'sest' => "e.nombre",
            'calt' => "ts.codigoalternat",
            'srea' => "m.id_medicos",
            'soso' => "os.abreviacion",
            'snor' => "ts.nro_orden",
            'snaf' => "ts.nro_afiliado",
            'scan' => "ts.cantidad",
            'sder' => "ts.matricula_derivacion",
            'sden' => "ts.matricula_derivacion",
            'sche' => "ts.checked",
            'fact' => "ts.facturado"
        );
        $cnct = "";
        $where = "";
        $filterPass = 0;
        foreach ($aPost AS $kP => $rP) {
            if (isset($post[$kP]) and (is_array($post[$kP]) or trim($post[$kP]))) {
                switch ($kP) {
                    case "srea":
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        $in = implode(', ', $post[$kP]);
                        $where.= "
                            {$cnct} {$rP} IN ({$in})
                        ";
                        break;
                    case "sest":
                        ($filterPass>0) ?$cnct = "AND(": $cnct = "(";
                        foreach($post[$kP] AS $val){
                            $where.= "
                                {$cnct} {$rP} LIKE '%$val%'
                            ";
                            $cnct = "OR";
                        }
                        $where.= ")";
                        /*$in = implode(', ', $post[$kP]);
                        $where.= "
                            {$cnct} {$rP} IN ({$in})
                        ";
                        $cnct = "AND";*/
                        break;
                    case "sden":
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        $where.= "
                            {$cnct} {$rP} = '{$post[$kP]}'
                        ";
                        $cnct = "AND";
                        break;
                    case "spac":
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        $Palabras = explode(' ', $post[$kP]);
                        foreach ($Palabras AS $pal) {
                            $where.= "
                                {$cnct} {$rP} LIKE '%{$pal}%'
                            ";
                            $cnct = "AND";
                        }
                        break;
                    case "sche":
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        if (in_array($post[$kP], array('1', '2'))) {
                            if ($post[$kP] == '1') {
                                $where.= "
                                    {$cnct} {$rP} IS NULL
                                ";
                            } else {
                                $where.= "
                                    {$cnct} {$rP} = '1'
                                ";
                            }
                            $cnct = "AND";
                        }
                        break;
                    case "fact":
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        if (in_array($post[$kP], array('1', '2'))) {
                            if ($post[$kP] == '1') {
                                $where.= "
                                    {$cnct} {$rP} IS NULL
                                ";
                            } else {
                                $where.= "
                                    {$cnct} {$rP} = '1'
                                ";
                            }
                            $cnct = "AND";
                        }
                        break;
                    default:
                        ($filterPass>0) ?$cnct = "AND": $cnct = "";
                        $where.= "
                            {$cnct} {$rP} LIKE '%{$post[$kP]}%'
                        ";
                        $cnct = "AND";
                        break;
                }
                $filterPass++;
            }
        }
        if (trim($where)) {
            $this->db->where("($where)");
        }
        /*
        if (trim($post) != '' and $post != '0') {
            $post = str_replace('%20', ' ', $post);
            $aFiltros = explode(' ', $post);
            $where = "";
            $cnct = "";
            foreach ($aFiltros AS $f) {
                $where.= "
                    {$cnct} (
                        CONCAT(TRIM(p.apellidos), ' ', TRIM(p.nombres)) LIKE '%{$f}%' OR
                        CONCAT(TRIM(m.apellidos), ' ', TRIM(m.nombres)) LIKE '%{$f}%' OR
                        TRIM(os.abreviacion) LIKE '%{$f}%' OR
                        TRIM(e.nombre) LIKE '%{$f}%'
                    )
                ";
                $cnct = "AND";
            }
            $this->db->where(
                "($where)"
            );
            #print "<pre>{$where}</pre>";
        }
        */
    }

    function obtDejaDepositoSuma($date1, $date2, $post, $id_usuario = null, $isMedico = false)
    {
        $suma1 = 0;
        $this->db
            ->select('SUM(ts.trajo_arancel) AS Suma')
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query = $this->db
            ->where('ts.estado', 1)
            ->get()
            ->result_array()
        ;
        #print "<pre>".$this->db->last_query()."</pre>";
        if (count($query) > 0) {
            $suma1+= $query[0]['Suma'];
        }

        $suma2 = 0;
        $this->db
            ->select('SUM(ts.deja_deposito) AS Suma')
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query = $this->db
            ->where('ts.estado', 1)
            ->get()
            ->result_array()
        ;
        if (count($query) > 0) {
            $suma2+= $query[0]['Suma'];
        }

        $suma3 = 0;
        $this->db
            ->select('SUM(ts.trajo_arancel_coseguro) AS Suma')
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query = $this->db
            ->where('ts.estado', 1)
            ->get()
            ->result_array()
        ;
        if (count($query) > 0) {
            $suma3+= $query[0]['Suma'];
        }

        return array($suma1, $suma2, $suma3);
    }

    function obtenerListadoCount($date1, $date2, $post, $id_usuario = null, $isMedico = false)
    {
        $this->db
            ->select('COUNT(t.id_turnos) AS Count')
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query = $this->db
            ->where('ts.estado', 1)
            ->get()
            ->result_array()
        ;
        return $query[0]['Count'];
    }

    function obtCantidadDeOrdenes($date1, $date2, $post, $id_usuario = null, $isMedico = false)
    {
        $this->db
            ->select("
                COUNT(ts.trajo_orden) AS Count
            ")
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query1 = $this->db
            ->where('ts.trajo_orden', 1)
            ->where('ts.estado', 1)
            ->order_by('ts.estado DESC, t.fecha, t.desde, t.hasta, t.id_turnos')
            ->get()
            ->result_array()
        ;

        $this->db
            ->select("
                COUNT(ts.trajo_pedido) AS Count
            ")
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $query2 = $this->db
            ->where('ts.trajo_pedido', 1)
            ->where('ts.estado', 1)
            ->order_by('ts.estado DESC, t.fecha, t.desde, t.hasta, t.id_turnos')
            ->get()
            ->result_array()
        ;

        return array($query1[0]['Count'], $query2[0]['Count']);
    }

    function obtenerListado(
        $date1,
        $date2,
        $post,
        $id_usuario = null,
        $isMedico = false
    ) {
        $this->db
            ->distinct('t.id_turnos')
            ->select("
                
                t.*,
                LEFT(t.desde, 5) AS hora,
                CONCAT(TRIM(p.apellidos), ', ', TRIM(p.nombres)) AS pacientes,
                p.nro_documento,
                CONCAT(TRIM(m.saludo), ' ', TRIM(m.apellidos), ', ', TRIM(m.nombres)) AS medicos,
                os.abreviacion AS obras_sociales,
                e.nombre AS estudios,
                e.codigopractica,
                ts.*,
                CONCAT(TRIM(d.saludo), ' ', TRIM(d.apellidos), ', ', TRIM(d.nombres)) AS medicos_derivacion,
                CONCAT(TRIM(dx.saludo), ' ', TRIM(dx.apellidos), ', ', TRIM(dx.nombres), ' (Externo)') AS medicosext_derivacion,
                te.nombre AS turnos_estados,
                u.usuario
            ")
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('medicos AS d', "ts.matricula_derivacion = d.matricula AND d.matricula != ''", 'left')
            ->join('medicosext AS dx', "ts.matricula_derivacion = dx.matricula AND dx.estado = 1 AND dx.matricula != ''", 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, null, $id_usuario, $isMedico);
        $post['orderby_field'] = isset($post['orderby_field']) ? $post['orderby_field'] : '1';
        $post['orderby_order'] = isset($post['orderby_order']) ? $post['orderby_order'] : 'ASC';
        $ord = $post['orderby_order'];
        switch ($post['orderby_field']) {
            case "1": $orderby_field = "CONCAT(t.fecha, t.desde, t.hasta) {$ord}, t.id_turnos {$ord}"; break;
            case "2": $orderby_field = "CONCAT(TRIM(p.apellidos), TRIM(p.nombres)) {$ord}, t.id_turnos {$ord}"; break;
            case "3": $orderby_field = "TRIM(e.codigopractica) {$ord}, t.id_turnos {$ord}"; break;
            case "4": $orderby_field = "e.nombre {$ord}, t.id_turnos {$ord}"; break;
            case "5": $orderby_field = "CONCAT(TRIM(m.apellidos), TRIM(m.nombres)) {$ord}, t.id_turnos {$ord}"; break;
            case "6": $orderby_field = "os.abreviacion {$ord}, t.id_turnos {$ord}"; break;
            case "7": $orderby_field = "ts.fecha_presentacion {$ord}, t.id_turnos {$ord}"; break;
            case "8": $orderby_field = "(ts.nro_orden * 1) {$ord}, t.id_turnos {$ord}"; break;
            case "9": $orderby_field = "(ts.nro_afiliado * 1) {$ord}, t.id_turnos {$ord}"; break;
            case "10": $orderby_field = "ts.cantidad {$ord}, t.id_turnos {$ord}"; break;
            case "11": $orderby_field = "ts.tipo {$ord}, t.id_turnos {$ord}"; break;
            case "12": $orderby_field = "ts.trajo_pedido {$ord}, t.id_turnos {$ord}"; break;
            case "13": $orderby_field = "ts.trajo_orden {$ord}, t.id_turnos {$ord}"; break;
            case "14": $orderby_field = "ts.trajo_arancel {$ord}, t.id_turnos {$ord}"; break;
            case "15": $orderby_field = "ts.deja_deposito {$ord}, t.id_turnos {$ord}"; break;
            case "16": $orderby_field = "ts.matricula_derivacion {$ord}, t.id_turnos {$ord}"; break;
            case "17": $orderby_field = "ts.trajo_arancel_coseguro {$ord}, t.id_turnos {$ord}"; break;
        }
        $query = $this->db
            ->where('ts.estado', 1)
            ->order_by($orderby_field)
            ->get()
            ->result_array()
        ;
        return $query;
    }

    public function obtMedicos()
    {
        $query = <<<SQL
            SELECT
                m.*
            FROM
                medicos AS m
            INNER JOIN
                medicos_horarios AS mh
                ON mh.id_medicos = m.id_medicos
            INNER JOIN
                turnos_tipos AS tt
                ON mh.id_turnos_tipos = tt.id_turnos_tipos
            WHERE
                mh.estado = 1 AND
                tt.estado = 1 AND
                tt.tipo = 'ESTUDIOS'
            GROUP BY
                m.id_medicos
            ORDER BY
                m.apellidos,
                m.nombres
SQL;
        return $this->db->query($query)->result_array();
    }

    public function obtMedicosConEstudios()
    {
        $aData[] = date("Y-m-d", strtotime("-5 months"));
        $aData[] = date("Y-m-d", strtotime("+1 months"));
        $query = <<<SQL
            (
                SELECT
                    m.*
                FROM
                    medicos AS m
                INNER JOIN
                    turnos AS t
                    ON t.id_medicos = m.id_medicos
    			INNER JOIN
    				turnos_estudios AS te
    				ON te.id_turnos = t.id_turnos
                WHERE
                    te.estado = 1 AND
                    t.estado = 1 AND
                    t.id_turnos_estados IN (2, 7) AND
                    t.fecha BETWEEN ? AND ? AND
                    m.id_medicos != 205
                GROUP BY
                    m.id_medicos
            ) UNION (
                SELECT
                    m.*
                FROM
                    medicos AS m
                INNER JOIN
                    medicos_estudios AS me
                    ON me.id_medicos = m.id_medicos
                INNER JOIN
                    estudios AS e
                    ON me.id_estudios = e.id_estudios
                WHERE
                    m.estado = 1 AND
                    me.estado = 1 AND
                    e.estado = 1 AND
                    m.id_medicos != 205
                GROUP BY
                    m.id_medicos
            )
            ORDER BY
                apellidos,
                nombres
SQL;
        return $this->db->query($query, $aData)->result_array();
    }

    public function buscarPacientes($nro_documento)
    {
        $query = <<<SQL
            SELECT
                p.*
            FROM
                pacientes AS p
            WHERE
                p.estado = 1 AND
                p.nro_documento = '{$nro_documento}'
            ORDER BY
                p.apellidos,
                p.nombres
SQL;
        return $this->db->query($query)->result_array();
    }

    public function obtEstudios()
    {
        $query = <<<SQL
            SELECT
                e.*
            FROM
                estudios AS e
            WHERE
                e.estado = 1
            ORDER BY
                e.nombre
SQL;
        return $this->db->query($query)->result_array();
    }

    public function obtEspecialidadDeMedico($id_medico)
    {
        $query = <<<SQL
            SELECT
                e.*
            FROM
                medicos_especialidades AS m
            INNER JOIN
                especialidades AS e
                ON m.id_especialidades = e.id_especialidades
            INNER JOIN
                medicos_horarios AS mh
                ON mh.id_medicos = m.id_medicos
            INNER JOIN
                turnos_tipos AS tt
                ON mh.id_turnos_tipos = tt.id_turnos_tipos
            WHERE
                m.id_medicos = '{$id_medico}' AND
                mh.estado = 1 AND
                tt.estado = 1 AND
                tt.tipo = 'ESTUDIOS' AND
                e.estado = 1
            GROUP BY
                m.id_medicos,
                m.id_especialidades
SQL;

        return $this->db->query($query)->result_array();
    }

    public function obtMedicosConMatriculas()
    {
        $query = <<<SQL
            SELECT
                m.saludo,
                m.apellidos,
                m.nombres,
                m.matricula,
                '0' AS externo
            FROM
                medicos AS m
            WHERE
                m.matricula > 0
            GROUP BY
                m.id_medicos
            UNION
                SELECT
                    dx.saludo,
                    dx.apellidos,
                    dx.nombres,
                    dx.matricula,
                    '1' AS externo
                FROM
                    medicosext AS dx
                WHERE
                    dx.estado = 1
            ORDER BY
                apellidos,
                nombres
SQL;
        return $this->db->query($query)->result_array();
    }

    public function obtObrasSociales()
    {
        $query = <<<SQL
            SELECT
                *
            FROM
                obras_sociales AS os
            WHERE
                os.estado = 1
            ORDER BY
                os.abreviacion
SQL;
        return $this->db->query($query)->result_array();
    }

    public function obtDiagnosticosExport($date1, $date2, $post, $id_usuario = null, $isMedico = false)
    {
        $this->db
            ->distinct('t.id_turnos')
            ->select("
                '0' AS orden,
                CONCAT(TRIM(p.apellidos), ', ', TRIM(p.nombres)) AS pacientes,
                'CIAC' AS presentador,
                CONCAT(m.saludo, ' ', m.apellidos, ', ', m.nombres) AS medicos,
                os.abreviacion AS obras_sociales,
                ts.fecha_presentacion,
                ts.nro_orden,
                ts.nro_afiliado,
                e.nombre,
                e.codigopractica,
                ts.codigoalternat,
                ts.cantidad,
                ts.tipo,
                ts.trajo_pedido,
                ts.trajo_orden,
                ts.trajo_arancel,
                ts.trajo_arancel_coseguro,
                ts.deja_deposito,
                ts.matricula_derivacion,
                CONCAT(d.saludo, ' ', d.apellidos, ', ', d.nombres) AS medicos_derivacion,
                CONCAT(dx.saludo, ' ', dx.apellidos, ', ', dx.nombres, ' (Externo)') AS medicosext_derivacion,
                ts.observaciones
            ")
            ->from('turnos AS t')
            ->join('turnos_tipos AS tt', 't.id_turnos_tipos = tt.id_turnos_tipos')
            ->join('pacientes AS p', 't.id_pacientes = p.id_pacientes', 'left')
            ->join('turnos_estados AS te', 't.id_turnos_estados= te.id_turnos_estados', 'left')
            ->join('turnos_estudios AS ts', 'ts.id_turnos = t.id_turnos', 'left')
            ->join('medicos AS m', 'ts.id_medicos = m.id_medicos', 'left')
            ->join('medicos AS d', "ts.matricula_derivacion = d.matricula AND d.matricula != ''", 'left')
            ->join('medicosext AS dx', "ts.matricula_derivacion = dx.matricula AND dx.estado = 1 AND dx.matricula != ''", 'left')
            ->join('obras_sociales AS os', 'ts.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('estudios AS e', 'ts.id_estudios = e.id_estudios', 'left')
            ->join('usuarios AS u', 't.id_usuarios = u.id_usuarios', 'left')
        ;
        $this->_filtroListado($date1, $date2, $post, 'ts.fecha_presentacion', $id_usuario, $isMedico);
        
        $post['orderby_field'] = isset($post['orderby_field']) ? $post['orderby_field'] : '0';
        $post['orderby_order'] = isset($post['orderby_order']) ? $post['orderby_order'] : 'ASC';
        $ord = $post['orderby_order'];
        switch ($post['orderby_field']) {
            case "0": $orderby_field = ""; break;
            case "1": $orderby_field = "CONCAT(t.fecha, t.desde, t.hasta) {$ord}, t.id_turnos {$ord}"; break;
            case "2": $orderby_field = "CONCAT(TRIM(p.apellidos), TRIM(p.nombres)) {$ord}, t.id_turnos {$ord}"; break;
            case "3": $orderby_field = "TRIM(e.codigopractica) {$ord}, t.id_turnos {$ord}"; break;
            case "4": $orderby_field = "e.nombre {$ord}, t.id_turnos {$ord}"; break;
            case "5": $orderby_field = "CONCAT(TRIM(m.apellidos), TRIM(m.nombres)) {$ord}, t.id_turnos {$ord}"; break;
            case "6": $orderby_field = "os.abreviacion {$ord}, t.id_turnos {$ord}"; break;
            case "7": $orderby_field = "ts.fecha_presentacion {$ord}, t.id_turnos {$ord}"; break;
            case "8": $orderby_field = "(ts.nro_orden * 1) {$ord}, t.id_turnos {$ord}"; break;
            case "9": $orderby_field = "(ts.nro_afiliado * 1) {$ord}, t.id_turnos {$ord}"; break;
            case "10": $orderby_field = "ts.cantidad {$ord}, t.id_turnos {$ord}"; break;
            case "11": $orderby_field = "ts.tipo {$ord}, t.id_turnos {$ord}"; break;
            case "12": $orderby_field = "ts.trajo_pedido {$ord}, t.id_turnos {$ord}"; break;
            case "13": $orderby_field = "ts.trajo_orden {$ord}, t.id_turnos {$ord}"; break;
            case "14": $orderby_field = "ts.trajo_arancel {$ord}, t.id_turnos {$ord}"; break;
            case "15": $orderby_field = "ts.deja_deposito {$ord}, t.id_turnos {$ord}"; break;
            case "16": $orderby_field = "ts.matricula_derivacion {$ord}, t.id_turnos {$ord}"; break;
            case "17": $orderby_field = "ts.trajo_arancel_coseguro {$ord}, t.id_turnos {$ord}"; break;
        }

        if($orderby_field == ""){
            $this->db->where('ts.estado', 1)->order_by('ts.estado DESC, ts.fecha_presentacion, t.desde, t.hasta, t.id_turnos');
        }
        else{
            $this->db->where('ts.estado', 1)->order_by($orderby_field);
        }

        $query = $this->db
            ->get()
            ->result_array()
        ;


        for ($i = 0; $i < count($query); $i++) {
            $query[$i]['orden'] = $i + 1;
            switch ($query[$i]['tipo']) {
                case '1': $query[$i]['tipo'] = 'A'; break;
                case '2': $query[$i]['tipo'] = 'I'; break;
                default: $query[$i]['tipo'] = ''; break;
            }
            switch ($query[$i]['trajo_pedido']) {
                case '1': $query[$i]['trajo_pedido'] = 'Si'; break;
                case '2': $query[$i]['trajo_pedido'] = 'No'; break;
                case '3': $query[$i]['trajo_pedido'] = 'Debe'; break;
                default: $query[$i]['trajo_pedido'] = ''; break;
            }
            switch ($query[$i]['trajo_orden']) {
                case '1': $query[$i]['trajo_orden'] = 'Si'; break;
                case '2': $query[$i]['trajo_orden'] = 'No'; break;
                case '3': $query[$i]['trajo_orden'] = 'Debe'; break;
                default: $query[$i]['trajo_orden'] = ''; break;
            }
            if ($query[$i]['trajo_arancel'] > 0) {
                $query[$i]['trajo_arancel'] = "{$query[$i]['trajo_arancel']}";
            }
            if ($query[$i]['trajo_arancel_coseguro'] > 0) {
                $query[$i]['trajo_arancel_coseguro'] = "{$query[$i]['trajo_arancel_coseguro']}";
            }
            if ($query[$i]['deja_deposito'] > 0) {
                $query[$i]['deja_deposito'] = "{$query[$i]['deja_deposito']}";
            }
        }
        return $query;
    }

    // IMPACTOS EN LA BASE DE DATOS

    function guardar_agregar($post)
    {
        #$post['fechanac'] = date_db($post['fechanac']);
        $this->db->insert('turnos', $post);
        return $this->db->insert_id();
    }

    function guardar_modificar($post, $id = null)
    {
        #$post['fechanac'] = date_db($post['fechanac']);
        $this->db
            ->where('id_turnos', $id)
            ->update('turnos', $post)
        ;
    }

    function guardar_eliminar($id)
    {
        $this->db
             ->where('id_turnos', $id)
             ->delete('turnos')
        ;
    }

    function guardarDiagnostico($post, $id_usuario = null)
    {
        $id_turnos_estudios = $post['id_turnos_estudios'];
        unset($post['id_turnos_estudios']);

        $query = $this->db
            ->from('turnos_estudios')
            ->where('id_turnos_estudios', $id_turnos_estudios)
            ->limit(1)
            ->get()
            ->result_array()
        ;

        if ($post['codigoalternat'] == 0) {
            $post['codigoalternat'] = null;
        }
        if (count($query) == 1) {
            $query_est = $this->db
                ->from('estudios')
                ->where('id_estudios', $query[0]['id_estudios'])
                ->get()
                ->result_array()
            ;
            if ($post['codigoalternat'] == $query_est[0]['codigopractica']) {
                $post['codigoalternat'] = null;
            }
        }

        $post['fecha_presentacion'] = implode("-", array_reverse(explode("/", $post['fecha_presentacion'])));

        if ($post['fecha_presentacion'] == '') {
            $post['fecha_presentacion'] = null;
        }

        $deja_deposito = $this->db
            ->select('deja_deposito')
            ->from('turnos_estudios')
            ->where('id_turnos_estudios', $id_turnos_estudios)
            ->limit(1)
            ->get()
            ->result_array()
        ;

        $this->db
            ->where('id_turnos_estudios', $id_turnos_estudios)
            ->update('turnos_estudios', $post)
        ;

        $post['deja_deposito_fecha'] = date("Y-m-d");
        if (count($deja_deposito) > 0) {
            $post['deja_deposito_diferencia'] =
                $post['deja_deposito'] -
                $deja_deposito[0]['deja_deposito']
            ;
        } else {
            $post['deja_deposito_diferencia'] = 0;
        }

        unset($post['id_estudios']);
        $this->db
            ->insert(
                'turnos_estudios_historicos',
                array_merge(
                    $post,
                    array(
                        'id_turnos_estudios' => $id_turnos_estudios,
                        'id_usuarios' => $id_usuario,
                        'fechahora' => date("Y-m-d H:i:s")
                    )
                )
            )
        ;

        $query = $this->db
            ->from('turnos_estudios')
            ->where('id_turnos_estudios', $id_turnos_estudios)
            ->limit(1)
            ->get()
            ->result_array()
        ;
        if (count($query) == 1) {
            $query_est = $this->db
                ->from('estudios')
                ->where('id_estudios', $query[0]['id_estudios'])
                ->get()
                ->result_array()
            ;
            if (count($query_est) > 0) {
                $query[0]['id_estudios'] = utf8_encode(ucwords(lower(trim(utf8_decode(
                    $query_est[0]['nombre']
                )))));
                $query[0]['codigoalternat'] =
                    $query[0]['codigoalternat'] > 0
                        ? $query[0]['codigoalternat']
                        : $query_est[0]['codigopractica']
                ;
            } else {
                $query[0]['id_estudios'] = '---';
                $query[0]['codigopractica'] =
                    $query[0]['codigoalternat'] > 0
                        ? $query[0]['codigoalternat']
                        : ''
                ;
            }

            $query_med = $this->db
                ->from('medicos')
                ->where('id_medicos', $query[0]['id_medicos'])
                ->get()
                ->result_array()
            ;
            if (count($query_med) > 0) {
                $query[0]['id_medicos'] = utf8_encode(ucwords(upper(trim(utf8_decode(
                    $query_med[0]['saludo'].' '.$query_med[0]['apellidos'].', '.$query_med[0]['nombres']
                )))));
            } else {
                $query[0]['id_medicos'] = '---';
            }

            $query_os = $this->db
                ->from('obras_sociales')
                ->where('id_obras_sociales', $query[0]['id_obras_sociales'])
                ->get()
                ->result_array()
            ;
            if (count($query_os) > 0) {
                $query[0]['id_obras_sociales'] = $query_os[0]['abreviacion'];
            } else {
                $query[0]['id_obras_sociales'] = '---';
            }

            if (
                isset($query[0]['fecha_presentacion']) and
                $query[0]['fecha_presentacion'] > '0000-00-00'
            ) {
                $query[0]['fecha_presentacion'] = date("d/m/Y", strtotime($query[0]['fecha_presentacion']));
            } else {
                $query[0]['fecha_presentacion'] = '';
            }

            if (trim($query[0]['nro_orden']) == '') {
                $query[0]['nro_orden'] = '---';
            }

            if (trim($query[0]['nro_afiliado']) == '') {
                $query[0]['nro_afiliado'] = '---';
            }

            if (trim($query[0]['cantidad']) == '') {
                $query[0]['cantidad'] = '---';
            }

            if ($query[0]['tipo'] == '1') {
                $query[0]['tipo'] = 'A';
            } elseif ($query[0]['tipo'] == '2') {
                $query[0]['tipo'] = 'I';
            } else {
                $query[0]['tipo'] = '---';
            }

            if ($query[0]['trajo_pedido'] == '1') {
                $query[0]['trajo_pedido'] = 'TP';
            } elseif ($query[0]['trajo_pedido'] == '2') {
                $query[0]['trajo_pedido'] = 'No';
            } elseif ($query[0]['trajo_pedido'] == '3') {
                $query[0]['trajo_pedido'] = '<strong style="color:red">Debe</strong>';
            } else {
                $query[0]['trajo_pedido'] = '---';
            }

            if ($query[0]['trajo_orden'] == '1') {
                $query[0]['trajo_orden'] = 'TO';
            } elseif ($query[0]['trajo_orden'] == '2') {
                $query[0]['trajo_orden'] = 'No';
            } elseif ($query[0]['trajo_orden'] == '3') {
                $query[0]['trajo_orden'] = '<strong style="color:red">Debe</strong>';
            } else {
                $query[0]['trajo_orden'] = '---';
            }

            if ($query[0]['trajo_arancel'] > 0) {
                $query[0]['trajo_arancel'] = "\$&nbsp;{$query[0]['trajo_arancel']}";
            } else {
                $query[0]['trajo_arancel'] = '---';
            }

            if ($query[0]['deja_deposito'] > 0) {
                $query[0]['deja_deposito'] = "\$&nbsp;{$query[0]['deja_deposito']}";
            } else {
                $query[0]['deja_deposito'] = '---';
            }

            if ($query[0]['trajo_arancel_coseguro'] > 0) {
                $query[0]['trajo_arancel_coseguro'] = "\$&nbsp;{$query[0]['trajo_arancel_coseguro']}";
            } else {
                $query[0]['trajo_arancel_coseguro'] = '---';
            }

            if (trim($query[0]['matricula_derivacion']) == '') {
                $query[0]['matricula_derivacion'] = '---';
                $query[0]['medicos_derivacion'] = '---';
            } else {
                $query_md = $this->db
                    ->select("CONCAT(saludo, ' ', apellidos, ', ', nombres) AS medicos_derivacion")
                    ->from('medicos')
                    ->where('estado', 1)
                    ->where('matricula', $query[0]['matricula_derivacion'])
                    ->limit(1)
                    ->get()
                    ->result_array()
                ;
                if (count($query_md) == 1) {
                    $query[0]['medicos_derivacion'] = $query_md[0]['medicos_derivacion'];
                } else {
                    $query_md = $this->db
                        ->select("CONCAT(saludo, ' ', apellidos, ', ', nombres, ' (Externo)') AS medicos_derivacion")
                        ->from('medicosext')
                        ->where('estado', 1)
                        ->where('matricula', $query[0]['matricula_derivacion'])
                        ->limit(1)
                        ->get()
                        ->result_array()
                    ;
                    if (count($query_md) == 1) {
                        $query[0]['medicos_derivacion'] = $query_md[0]['medicos_derivacion'];
                    }
                }
            }

            #$item['medicos_derivacion'] ? $item['medicos_derivacion'] : $item['medicosext_derivacion']
            return $query[0];
        } else {
            return array();
        }
    }

    function borrarDiagnostico($post, $id_usuario = null)
    {
        $query = $this->db
            ->select('id_turnos')
            ->from('turnos_estudios')
            ->where('id_turnos_estudios', $post['id_turnos_estudios'])
            ->get()
            ->result_array()
        ;
        if (count($query) == 1) {
            $id_turnos = $query[0]['id_turnos'];
            $query = $this->db
                ->where('id_turnos', $id_turnos)
                ->update('turnos', array('id_turnos_estados' => '6'))
            ;
            return $id_turnos;
        }
        return false;
    }

    function checkDiagnostico($post, $id_usuario = null)
    {
        $query = $this->db
            ->where('id_turnos_estudios', $post['id_turnos_estudios'])
            ->update('turnos_estudios', array('checked' => '1'))
        ;
        return $post['id_turnos_estudios'];
    }

    function checkFacturado($post, $id_usuario = null)
    {
        $query = $this->db
            ->where('id_turnos_estudios', $post['id_turnos_estudios'])
            ->update('turnos_estudios', array('facturado' => '1'))
        ;
        return $post['id_turnos_estudios'];
    }

    function uncheckDiagnostico($post, $id_usuario = null)
    {
        $query = $this->db
            ->where('id_turnos_estudios', $post['id_turnos_estudios'])
            ->update('turnos_estudios', array('checked' => null))
        ;
        return $post['id_turnos_estudios'];
    }
    function unfacturadoDiagnostico($post, $id_usuario = null)
    {
        $query = $this->db
            ->where('id_turnos_estudios', $post['id_turnos_estudios'])
            ->update('turnos_estudios', array('facturado' => null))
        ;
        return $post['id_turnos_estudios'];
    }

    public function agregarTurno($vcDuracionTurno, $post)
    {
        if ($post['fecha'] != '') {
            $post['fecha'] = implode("-", array_reverse(explode("/", $post['fecha'])));
        }
        if ($post['fecha_presentacion'] != '') {
            $post['fecha_presentacion'] = implode("-", array_reverse(explode("/", $post['fecha_presentacion'])));
        }
        if ($post['desde'] != '') {
            $post['hasta'] = horaMM($post['desde'], $vcDuracionTurno);
        }
        if ($this->session->userdata('ID_USUARIO') != '') {
            $post['id_usuarios'] = $this->session->userdata('ID_USUARIO');
        } else {
            $post['id_usuarios'] = '0';
        }
        $post['id_pacientes'] = $this->buscarPacientes($post['id_pacientes']);
        if (count($post['id_pacientes']) > 0) {
            $post['id_pacientes'] = $post['id_pacientes'][0]['id_pacientes'];
        } else {
            $post['id_pacientes'] = '0';
        }
        $dataInsertTurno['id_medicos'] = $post['id_medicos'];
        $dataInsertTurno['id_especialidades'] = $post['id_especialidades'];
        $dataInsertTurno['id_pacientes'] = $post['id_pacientes'];
        $dataInsertTurno['id_turnos_estados'] = '7';
        $dataInsertTurno['fecha'] = $post['fecha'];
        $dataInsertTurno['desde'] = $post['desde'];
        $dataInsertTurno['hasta'] = $post['hasta'];
        $dataInsertTurno['id_turnos_tipos'] = '2';
        $dataInsertTurno['estado'] = '1';
        $dataInsertTurno['tipo_usuario'] = 'U';
        $dataInsertTurno['id_usuarios'] = $post['id_usuarios'];
        $dataInsertTurno['fecha_alta'] = date("Y-m-d");
        $dataInsertTurno['hora_alta'] = date("H:m:s");
        $this->db->insert('turnos', $dataInsertTurno);
        $id_turnos = $this->db->insert_id();

        $dataInsertTurnosEstudios['id_turnos'] = $id_turnos;
        $dataInsertTurnosEstudios['id_estudios'] = $post['id_estudios'];
        $dataInsertTurnosEstudios['estado'] = '1';
        $dataInsertTurnosEstudios['id_medicos'] = $post['id_medicos'];
        $dataInsertTurnosEstudios['id_obras_sociales'] = $post['id_obras_sociales'];
        $dataInsertTurnosEstudios['fecha_presentacion'] = $post['fecha_presentacion'];
        $dataInsertTurnosEstudios['nro_orden'] = $post['nro_orden'];
        $dataInsertTurnosEstudios['nro_afiliado'] = $post['nro_afiliado'];
        $dataInsertTurnosEstudios['cantidad'] = $post['cantidad'];
        $dataInsertTurnosEstudios['tipo'] = $post['tipo'];
        $dataInsertTurnosEstudios['trajo_pedido'] = $post['trajo_pedido'];
        $dataInsertTurnosEstudios['trajo_orden'] = $post['trajo_orden'];
        $dataInsertTurnosEstudios['trajo_arancel'] = $post['trajo_arancel'];
        $dataInsertTurnosEstudios['deja_deposito'] = $post['deja_deposito'];
        $dataInsertTurnosEstudios['matricula_derivacion'] = $post['matricula_derivacion'];
        $dataInsertTurnosEstudios['observaciones'] = isset($post['observaciones']) ? $post['observaciones'] : '';
        $this->db->insert('turnos_estudios', $dataInsertTurnosEstudios);
    }

    public function getUsuario($id_usuarios)
    {
        $query = $this->db
            ->from('usuarios')
            ->where('id_usuarios', $id_usuarios)
            ->limit(1)
            ->get()
            ->result_array()
        ;
        return $query[0];

    }

    public function obtUltimoHorReal(
        $fecha,
        $id_medicos,
        $id_especialidades
    ) {
        $id_dias_semana = date("N", strtotime($fecha)) + 1;
        if ($id_dias_semana > 7) {
            $id_dias_semana = 1;
        }
        $ultimo_horreal = '';

    	//VERIFICO LOS HORARIOS QUE TIENE CARGADO EL MEDICO PARA UN DIA DETERMINADO
    	$query = $this->db
            ->from('medicos_horarios')
            ->where('id_medicos', $id_medicos)
            ->where('id_especialidades', $id_especialidades)
            ->where('id_dias_semana', $id_dias_semana)
            ->where('estado', 1)
            ->order_by('desde', 'ASC')
            ->get()
            ->result_array()
        ;
    	foreach ($query AS $row){
            $ultimo_horreal = max($ultimo_horreal, $row['hasta']);
        }

    	//TRAIGO TODOS LOS TURNOS RESERVADOS
		$query = "SELECT T.desde
				FROM turnos T
				INNER JOIN turnos_estados TE
				ON T.id_turnos_estados = TE.id_turnos_estados
				INNER JOIN pacientes P
				ON T.id_pacientes = P.id_pacientes
				LEFT JOIN obras_sociales OS
				ON P.id_obras_sociales = OS.id_obras_sociales
				WHERE T.id_medicos = '{$id_medicos}' AND T.id_especialidades = '{$id_especialidades}' AND T.fecha = '{$fecha}' AND T.estado = 1 AND (T.id_turnos_estados = 1 OR T.id_turnos_estados = 2 OR T.id_turnos_estados = 4 OR T.id_turnos_estados = 7)
				ORDER BY T.desde ASC";
        $query = $this->db->query($query)->result_array();
    	foreach ($query AS $row){
            $ultimo_horreal = max($ultimo_horreal, $row['desde']);
        }

        //OBTENER DURACION DEL TURNO
		$query = "SELECT * FROM medicos_especialidades WHERE id_medicos = '{$id_medicos}' AND id_especialidades = '{$id_especialidades}' ORDER BY id_medicos_especialidades DESC LIMIT 0,1";
        $query = $this->db->query($query)->result_array();
        if (count($query) > 0) {
        	$vcDuracionTurno = $query[0]['duracion_turno'];
            $ultimo_horreal = horaMM($ultimo_horreal, $vcDuracionTurno);
        }

        return $ultimo_horreal;
    }

    public function getTurnosEstudiosHistoricos($id_turnos_estudios)
    {
        $query = $this->db
            ->select("
                teh.fechahora,
                teh.codigoalternat,
                CONCAT(
                    m.saludo,
                    ' ',
                    m.apellidos,
                    ', ',
                    m.nombres
                ) AS medicos,
                os.abreviacion AS obras_sociales,
                teh.fecha_presentacion,
                teh.nro_orden,
                teh.nro_afiliado,
                teh.cantidad,
                teh.tipo,
                teh.trajo_pedido,
                teh.trajo_orden,
                teh.trajo_arancel,
                teh.trajo_arancel_coseguro,
                teh.deja_deposito,
                teh.deja_deposito_diferencia,
                teh.deja_deposito_fecha,
                teh.matricula_derivacion,
                teh.observaciones,
                CONCAT(
                    u.apellidos,
                    ', ',
                    u.nombres
                ) AS usuarios
            ")
            ->from('turnos_estudios_historicos teh')
            ->join('obras_sociales os', 'teh.id_obras_sociales = os.id_obras_sociales', 'left')
            ->join('medicos m', 'teh.id_medicos = m.id_medicos', 'left')
            ->join('usuarios u', 'teh.id_usuarios = u.id_usuarios', 'left')
            ->where('teh.id_turnos_estudios', $id_turnos_estudios)
            ->order_by('teh.fechahora')
            ->get()
            ->result_array()
        ;
        for ($i = 0; $i < count($query); $i++) {
            $query[$i]['fecha_presentacion'] = date("d/m/Y", strtotime($query[$i]['fecha_presentacion']));
            switch ($query[$i]['tipo']) {
                case "1": $query[$i]['tipo'] = 'A'; break;
                case "2": $query[$i]['tipo'] = 'I'; break;
            }
            switch ($query[$i]['trajo_pedido']) {
                case '1': $query[$i]['trajo_pedido'] = 'TP'; break;
                case '2': $query[$i]['trajo_pedido'] = 'No'; break;
                case '3': $query[$i]['trajo_pedido'] = '<strong style="color:red">Debe</strong>'; break;
                default: $query[$i]['trajo_pedido'] = '---'; break;
            }
            switch ($query[$i]['trajo_orden']) {
                case '1': $query[$i]['trajo_orden'] = 'TO'; break;
                case '2': $query[$i]['trajo_orden'] = 'No'; break;
                case '3': $query[$i]['trajo_orden'] = '<strong style="color:red">Debe</strong>'; break;
                default: $query[$i]['trajo_orden'] = '---'; break;
            }
            if ($query[$i]['trajo_arancel'] > 0) {
                $query[$i]['trajo_arancel'] = '$&nbsp;'.$query[$i]['trajo_arancel'];
            } else {
                $query[$i]['trajo_arancel'] = '';
            }
            if ($query[$i]['trajo_arancel_coseguro'] > 0) {
                $query[$i]['trajo_arancel_coseguro'] = '$&nbsp;'.$query[$i]['trajo_arancel_coseguro'];
            } else {
                $query[$i]['trajo_arancel_coseguro'] = '';
            }
            if ($query[$i]['deja_deposito'] > 0) {
                $query[$i]['deja_deposito'] = '$&nbsp;'.$query[$i]['deja_deposito'];
            } else {
                $query[$i]['deja_deposito'] = '';
            }
            if ($query[$i]['deja_deposito_diferencia'] > 0) {
                $query[$i]['deja_deposito_diferencia'] = '$&nbsp;'.$query[$i]['deja_deposito_diferencia'];
            } elseif ($query[$i]['deja_deposito_diferencia'] < 0) {
                $query[$i]['deja_deposito_diferencia'] = '-&nbsp;$&nbsp;'.abs($query[$i]['deja_deposito_diferencia']);
            } else {
                $query[$i]['deja_deposito_diferencia'] = '';
            }
            $query[$i]['deja_deposito_fecha'] = date("d/m/Y", strtotime($query[$i]['deja_deposito_fecha']));
            $query[$i]['fechahora'] = date("d/m/Y H:i", strtotime($query[$i]['fechahora']))."hs";
        }
        return $query;

    }

}

//EOF Diagnostico_model.php
