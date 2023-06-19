<?php
include_once 'datos/ConexionBD.php';
class alumnos
{
    //DATOS DE LA TABLA ALUMNO
    const NOMBRE_TABLA = "alumno";
    const ID_ALUMNO = "idAlumno";
    const NOMBRE_ALUMNO = "nombreAlumno";
    const APELLIDO_PATERNO = "apellidoPat";
    const APELLIDO_MATERNO = "apellidoMat";
    const CLAVE_API = "claveApi";
    const EMAIL = "email";
    const DIRECCION = "direccion";
    const FECHA_NACIMIENTO = "fechaNacimiento";

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

    //__________________________________________METODOS____________________________
    /**
     * peticion POST
     * /usuarios/registro
     * /usuarios/login
     * /usuarios/desencriptar  XXXXX
     */
    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    /** Peticion GET
     * Ejemplo de método que proceso peticiones GET. El método se llama get para facilitar su identificación como controlador que atiende este tipo de peticiones.
     *
     * /usuarios/1     con un param devuelve el usuario con ese valor como id
     * /usuarios/      sin params devuelve todos los usuarios
     * /usuarios/10/100    con dos params, devuelve los usuarios con su id en ese rango.
     * /usuarios/porcorreo
     */
    public static function get($peticion)
    {
        //FALTAN VALIDACIONES, SI ESCRIBE HOLA, SI NO MANDAN UN NUMERO, SI MANDAN NUMERO Y LETRA,ETCCC

        if ($peticion[0] == null) {
            echo 'nullLLLL';
            return self::listarTodos();
        } else if (count($peticion) == 1) {
            return self::listarPorId($peticion[0]);
        } else if (count($peticion) == 2) {
            if ($peticion[0] == 'usuarioPorCorreo') {
                return self::obtenerUsuarioPorCorreo($peticion[1]);
            } else {
                return self::listarPorRango($peticion[0], $peticion[1]);
            }
        }
        else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, "faltan parámetros", 400);
        }
    }

    public static function put($peticion)
    {

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $alumno = json_decode($body);

            if (self::actualizar($alumno, $peticion[0]) > 0) {
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

    /**
     * Crea un nuevo usuario en la base de datos
     */
    private static function registrar()
    {
        echo 'REGISTRAR';
        $cuerpo = file_get_contents('php://input');
        $alumno = json_decode($cuerpo);


        $resultado = self::crear($alumno);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                echo '<br>CREACION EXITOSA';
                http_response_code(200);
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

    /**
     * Crea un nuevo usuario en la tabla "usuario"
     * @param mixed $datosUsuario columnas del registro
     * @return int codigo para determinar si la inserción fue exitosa
     */
    private static function crear($datosAlumno)
    {
        $nombreAlumno = $datosAlumno->nombreAlumno;
        $apellidoPat = $datosAlumno->apellidoPat;
        $apellidoMat = $datosAlumno->apellidoMat;
        $claveApi = self::generarClaveApi();
        $email = $datosAlumno->email;
        $direccion = $datosAlumno->direccion;
        $fechaNacimiento = $datosAlumno->fechaNacimiento;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE_ALUMNO . "," .
                self::APELLIDO_PATERNO . "," .
                self::APELLIDO_MATERNO . "," .
                self::CLAVE_API . "," .
                self::EMAIL . "," .
                self::DIRECCION . "," .
                self::FECHA_NACIMIENTO . ")" .
                " VALUES(?,?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombreAlumno);
            $sentencia->bindParam(2, $apellidoPat);
            $sentencia->bindParam(3, $apellidoMat);
            $sentencia->bindParam(4, $claveApi);
            $sentencia->bindParam(5, $email);
            $sentencia->bindParam(6, $direccion);
            $sentencia->bindParam(7, $fechaNacimiento);

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
/**

    private static function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }
*/
    private static function generarClaveApi()
    {
        echo 'GenerarClaveApi';
        return md5(microtime() . rand());
    }
/**
    private static function autenticar($correo, $contrasena)
    {
        $comando = "SELECT contrasena FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        try {

            //$sentencia= \ConexionBD\ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            echo 'base de datos';
            $sentencia->bindParam(1, $correo);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasena($contrasena, $resultado['contrasena'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */

    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization1"])) {

            $claveApi = $cabeceras["Authorization1"];

            if (alumnos::validarClaveApi($claveApi)) {
                return alumnos::obtenerIdUsuario($claveApi);
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

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */

    private static function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_ALUMNO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }
/*
    private static function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }

    */
    //Metodo que obtiene un usuario de acuerdo a su id
    private static function listarPorId($id_alumno)
    {
        $comando = "SELECT " .
            "*" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_ALUMNO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $id_alumno);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;

    }

    //Metodo que obtiene los usuarios mediante su correo
    private static function obtenerUsuarioPorCorreo($email)
    {
        $comando = "SELECT " .
            self::NOMBRE_ALUMNO . "," .
            self::APELLIDO_PATERNO . "," .
            self::APELLIDO_MATERNO . "," .
            self::CLAVE_API . "," .
            self::EMAIL . "," .
            self::DIRECCION . "," .
            self::FECHA_NACIMIENTO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::EMAIL . "=?";

        //$sentencia = \ConexionBD\ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $email);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }


    private static function listarTodos()
    {
        $comando = "SELECT " .
            self::NOMBRE_ALUMNO . "," .
            self::APELLIDO_PATERNO . "," .
            self::APELLIDO_MATERNO . "," .
            self::CLAVE_API . "," .
            self::EMAIL . "," .
            self::DIRECCION . "," .
            self::FECHA_NACIMIENTO .
            " FROM " . self::NOMBRE_TABLA .
            " ORDER BY " . self::NOMBRE_ALUMNO;

        //$sentencia = \ConexionBD\ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);


        if ($sentencia->execute())
            return $sentencia->fetchAll();
        else
            return null;
    }

    private static function listarPorRango($idUno, $idDos){
        $comando = "SELECT " .
            self::NOMBRE_ALUMNO . "," .
            self::APELLIDO_PATERNO . "," .
            self::APELLIDO_MATERNO . "," .
            self::CLAVE_API . "," .
            self::EMAIL . "," .
            self::DIRECCION . "," .
            self::FECHA_NACIMIENTO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_ALUMNO .
            " BETWEEN ". "?" . " AND ". "?";


        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idUno);
        $sentencia->bindParam(2, $idDos);


        if ($sentencia->execute())
            //echo($sentencia);
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
                self::NOMBRE_ALUMNO . "=?," .
                self::APELLIDO_PATERNO . "=?," .
                self::APELLIDO_MATERNO . "=?," .
                self::EMAIL . "=?," .
                self::DIRECCION . "=?," .
                self::FECHA_NACIMIENTO . "=?" .
                " WHERE " . self::ID_ALUMNO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombreAlumno);
            $sentencia->bindParam(2, $apellidoPat);
            $sentencia->bindParam(3, $apellidoMat);
            $sentencia->bindParam(4, $email);
            $sentencia->bindParam(5, $direccion);
            $sentencia->bindParam(6, $fechaNacimiento);
            $sentencia->bindParam(7, $idAlumno);

            $nombreAlumno = $alumno->nombreAlumno;
            $apellidoPat = $alumno->apellidoPat;
            $apellidoMat = $alumno->apellidoMat;
            $email = $alumno->email;
            $direccion = $alumno->direccion;
            $fechaNacimiento = $alumno->fechaNacimiento;


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function eliminar($idAlumno)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_ALUMNO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idAlumno);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerIdUsuario($claveApi)
    {
        $comando = "SELECT " . self::ID_ALUMNO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['idAlumno'];
        } else
            return null;
    }
/**
    private static function loguear()
    {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $usuario = json_decode($body);

        $correo = $usuario->correo;
        $contrasena = $usuario->contrasena;


        if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerUsuarioPorCorreo($correo);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["claveApi"] = $usuarioBD["claveApi"];
                return ["estado" => 1, "usuario" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Correo o contraseña inválidos"));
        }
    }
 * **/
}

