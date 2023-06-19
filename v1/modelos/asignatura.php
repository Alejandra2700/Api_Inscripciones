<?php
include_once 'datos/ConexionBD.php';
class asignatura
{
    //DATOS DE LA TABLA ALUMNO
    const NOMBRE_TABLA = "asignatura";
    const ID_ASIGNATURA = "idAsignatura";
    const NOMBRE_ASIGNATURA = "nombreAsig";
    const CLAVE_API = "claveApi";
    const CREDITOS = "creditos";

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

    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function get($peticion)
    {
        //FALTAN VALIDACIONES, SI ESCRIBE HOLA, SI NO MANDAN UN NUMERO, SI MANDAN NUMERO Y LETRA,ETCCC

        if ($peticion[0] == null) {
            return self::listarTodos();
        } else if (count($peticion) == 1) {
            return self::listarPorId($peticion[0]);
        } else if (count($peticion) == 2) {
            return self::listarPorRango($peticion[0], $peticion[1]);
            }
        else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, "faltan parámetros", 400);
        }
    }

    public static function put($peticion)
    {

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $asignatura = json_decode($body);

            if (self::actualizar($asignatura, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::ESTADO_ACTUALIZACION_EXITOSA,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El contacto al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {

        if (!empty($peticion[0])) {
            if ($numRegs = self::eliminar($peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::ELIMINACION_EXITOSA,
                    "mensaje" => "Registro eliminado correctamente",
                    "registroEliminados" => $numRegs
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El contacto al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    private static function registrar()
    {
        echo 'REGISTRAR';
        $cuerpo = file_get_contents('php://input');
        $asignatura = json_decode($cuerpo);


        $resultado = self::crear($asignatura);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                echo '<br>CREACION EXITOSA';
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                echo '<br>CREACION FALLIDA';
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                echo '<br>DEFAULT';
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private static function crear($datosAsignatura)
    {
        $nombreAsig = $datosAsignatura->nombreAsig;
        $claveApi = self::generarClaveApi();
        $creditos = $datosAsignatura->creditos;


        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE_ASIGNATURA . "," .
                self::CLAVE_API . "," .
                self::CREDITOS . ")" .
                " VALUES(?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombreAsig);
            $sentencia->bindParam(2, $claveApi);
            $sentencia->bindParam(3, $creditos);

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

    }
    private static function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    private static function listarTodos()
    {
        $comando = "SELECT " .
            self::NOMBRE_ASIGNATURA . "," .
            self::CLAVE_API . "," .
            self::CREDITOS .
            " FROM " . self::NOMBRE_TABLA .
            " ORDER BY " . self::NOMBRE_ASIGNATURA;

        //$sentencia = \ConexionBD\ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);


        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }

    private static function listarPorId($idAsig)
    {
        $comando = "SELECT " .
            "*" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_ASIGNATURA . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $idAsig);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;

    }

    private static function listarPorRango($idUno, $idDos){
        $comando = "SELECT " .
            self::NOMBRE_ASIGNATURA . "," .
            self::CLAVE_API . "," .
            self::CREDITOS .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_ASIGNATURA .
            " BETWEEN ". "?" . " AND ". "?";


        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idUno);
        $sentencia->bindParam(2, $idDos);


        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }

    private static function actualizar($alumno,$idAlumno)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " .
                self::NOMBRE_ASIGNATURA . "=?," .
                self::CREDITOS . "=?" .
                " WHERE " . self::ID_ASIGNATURA . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombreAsig);
            $sentencia->bindParam(2, $creditos);
            $sentencia->bindParam(3, $idAlumno);

            $nombreAsig = $alumno->nombreAsig;
            $creditos = $alumno->creditos;


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function eliminar($idAsig)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_ASIGNATURA . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idAsig);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {

            $claveApi = $cabeceras["Authorization"];

            if (asignatura::validarClaveApi($claveApi)) {
                return asignatura::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticaci�n"));
        }
    }

    private static function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_ASIGNATURA . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    private static function obtenerIdUsuario($claveApi)
    {
        $comando = "SELECT " . self::ID_ASIGNATURA .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['idAsignatura'];
        } else
            return null;
    }

}