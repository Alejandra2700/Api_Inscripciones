<?php
include_once 'datos/ConexionBD.php';
class inscripcion
{
    //DATOS DE LA TABLA ALUMNO
    const NOMBRE_TABLA = "inscripcion";
    const ID_INSCRIPCION = "idInscripcion";
    const ID_ALUMNO = "idAlumno";
    const ID_ASIGNATURA = "idAsignatura";
    const FECHA = "fecha";

    //Constantes para estados de excepciones
    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;
    const ESTADO_ACTUALIZACION_EXITOSA = 9;
    const ESTADO_NO_ENCONTRADO = 10;
    const ESTADO_ERROR_PARAMETROS = 11;
    const ELIMINACION_EXITOSA = 12;

    public static function get($peticion)
    {
       // $idAlumno = alumnos::autorizar();
        //$idAsig = asignatura::autorizar();

        if (empty($peticion[0])) {
            return self::obtenerInscripciones();
        } else if ($peticion[0] == 'asignaturaPorAlumno') {
            //Obtener las materias a las que un alumno esta inscrito
                return self::getAsignaturasPorAlumn($peticion[1]);
            }else if ($peticion[0] == 'alumnoPorAsig') {
                //Obtiene los alumnos inscritos en una materia
                return self::getAlumPorAsig($peticion[1]);
            } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }

    }

    public static function post($peticion)
    {
        $idAlumno = alumnos::autorizar();
        $idAsignatura = asignatura::autorizar();

        $body = file_get_contents('php://input');
        $inscripcion = json_decode($body);

        $idInscripcion = inscripcion::crear($idAlumno, $idAsignatura, $inscripcion);

        http_response_code(201);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => "INSCRIPCION REALIZADA",
            "id" => $idInscripcion
        ];

    }

    private static function crear($idAlumno, $idAsignatura, $inscripcion)
    {
        if ($inscripcion) {
            //if(inscripcion::comprobarExistencia($idAlumno, $idAsignatura)){
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();


                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::ID_ALUMNO . "," .
                    self::ID_ASIGNATURA . "," .
                    self::FECHA . ")" .
                    " VALUES(?,?,?)";

                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $idAlumno);
                $sentencia->bindParam(2, $idAsignatura);
                $sentencia->bindParam(3, $fecha);

                $fecha = $inscripcion->fecha;

                $resultado = $sentencia->execute();

                if ($resultado) {
                    return self::ESTADO_CREACION_EXITOSA;
                } else {
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } catch (PDOException $e) {
                //return $e->getMessage();
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            echo("Ya esta inscrito a la materia");
        }
    }

    private static function obtenerInscripciones()
    {

        $comando = "SELECT " . alumnos::NOMBRE_TABLA . ".". alumnos::NOMBRE_ALUMNO . ",".
            asignatura::NOMBRE_TABLA . "." . asignatura::NOMBRE_ASIGNATURA . ",".
            self::NOMBRE_TABLA . "." . self::FECHA .
            " FROM ((" . self::NOMBRE_TABLA .
            " INNER JOIN " . alumnos::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ALUMNO . " = " . alumnos::NOMBRE_TABLA . "." . alumnos::ID_ALUMNO . ")" .
            " INNER JOIN " . asignatura::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ASIGNATURA . " = " .asignatura::NOMBRE_TABLA . "." . asignatura::ID_ASIGNATURA . " )";

        echo ($comando);
        //$sentencia = \ConexionBD\ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);


        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }

    private static function getAsignaturasPorAlumn($idAlumno)
    {

        $comando = "SELECT " . alumnos::NOMBRE_TABLA . ".". alumnos::NOMBRE_ALUMNO . ",".
            asignatura::NOMBRE_TABLA . "." . asignatura::NOMBRE_ASIGNATURA . ",".
            self::NOMBRE_TABLA . "." . self::FECHA .
            " FROM ((" . self::NOMBRE_TABLA .
            " INNER JOIN " . alumnos::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ALUMNO . " = " . alumnos::NOMBRE_TABLA . "." . alumnos::ID_ALUMNO . ")" .
            " INNER JOIN " . asignatura::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ASIGNATURA . " = " .asignatura::NOMBRE_TABLA . "." . asignatura::ID_ASIGNATURA . " )" .
            "WHERE " . alumnos::NOMBRE_TABLA . "." . alumnos::ID_ALUMNO . "=?" ;

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idAlumno);

        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }

    private static function getAlumPorAsig($idAsignatura)
    {

        $comando = "SELECT " . alumnos::NOMBRE_TABLA . ".". alumnos::NOMBRE_ALUMNO . ",".
            asignatura::NOMBRE_TABLA . "." . asignatura::NOMBRE_ASIGNATURA . ",".
            self::NOMBRE_TABLA . "." . self::FECHA .
            " FROM ((" . self::NOMBRE_TABLA .
            " INNER JOIN " . alumnos::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ALUMNO . " = " . alumnos::NOMBRE_TABLA . "." . alumnos::ID_ALUMNO . ")" .
            " INNER JOIN " . asignatura::NOMBRE_TABLA . " ON " .
            self::NOMBRE_TABLA . "." . self::ID_ASIGNATURA . " = " .asignatura::NOMBRE_TABLA . "." . asignatura::ID_ASIGNATURA . " )" .
            "WHERE " . asignatura::NOMBRE_TABLA . "." . asignatura::ID_ASIGNATURA . "=?" ;


        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idAsignatura);

        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }




       /*
    public static function comprobarExistencia($idAlumno,$idAsignatura){
        $comando = "SELECT " . self::ID_INSCRIPCION .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_ALUMNO . "=?". " AND " . self::ID_ASIGNATURA. "=?" ;

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idAlumno);
        $sentencia->bindParam(1, $idAsignatura);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return true;
        } else
            return null;
    }
       */

}