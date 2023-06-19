<?php
require 'modelos/alumnos.php';
require 'modelos/asignatura.php';
require 'modelos/inscripcion.php';
require 'vistas/VistaXML.php';
require'vistas/VistaJson.php';
include_once 'datos/ConexionBD.php';
require 'utilidades/ExcepcionApi.php';

//Dentro de index.php
// Constantes de estado
const ESTADO_URL_INCORRECTA = 2;
const ESTADO_EXISTENCIA_RECURSO = 3;
const ESTADO_METODO_NO_PERMITIDO = 4;

//Validar si se llama para devolver datos en formato xml o json
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';

switch ($formato) {
    case 'xml':
        $vista = new VistaXML();
        break;
    case 'json':
    default:
        $vista = new VistaJson();
}
set_exception_handler(function ($exception) use ($vista) {
    $cuerpo = array(
        "estado" => $exception->estado,
        "mensaje" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $vista->estado = $exception->getCode();
    } else {
        $vista->estado = 500;
    }

    $vista->imprimir($cuerpo);
}
);

$peticion= '';
//Extraer segmento de la red
if(isset($_GET['PATH_INFO'])){
    print $_GET['PATH_INFO'];
    $peticion = explode('/', $_GET['PATH_INFO']);
    //print ("<br>");
    //var_dump($peticion);
}

// Obtener recurso
$recurso = array_shift($peticion);
$recursos_existentes = array('alumnos', 'asignatura', 'inscripcion');


// Comprobar si existe el recurso
if (!in_array($recurso, $recursos_existentes)) {
    // Respuesta error: Deberia mandar un error como en usuario: threw exceptionAPI
    echo 'Ruta inexistente' . $recurso;
}
$metodo = strtolower($_SERVER['REQUEST_METHOD']);

//echo "Metodo de la petición " . $metodo;
switch ($metodo) {
    case 'get':

    case 'post':

    case 'put':

    case 'delete':

        if (method_exists($recurso, $metodo)) {
            $respuesta = call_user_func(array($recurso, $metodo), $peticion);
            $vista->imprimir($respuesta);
            break;
        }
    default:
        // Método no aceptado
        $vista->estado = 405;
        $cuerpo = [
            "estado" => ESTADO_METODO_NO_PERMITIDO,
            "mensaje" => utf8_encode("Método no permitido")
        ];
        $vista->imprimir($cuerpo);
}