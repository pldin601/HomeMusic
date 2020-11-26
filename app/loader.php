<?php

use app\core\exceptions\ApplicationException;
use app\core\http\HttpStatusCodes;
use app\core\injector\Injector;
use app\core\view\JsonResponse;
use app\core\view\TinyView;
use app\lang\option\Consumer;
use app\lang\option\Mapper;

require_once "constants.php";
require_once "lang/extend.php";
require_once "core/shortcuts.php";
require_once "lang/functional/functions.php";

const STATIC_CLASS_INIT_METHOD = "class_init";

// Registering base class loader
spl_autoload_register(function ($class_name) {
    $filename = APP_ROOT_DIR . "/" . str_replace("\\", "/", $class_name) . '.php';
    if (file_exists($filename)) {
        require_once $filename;
        static_class_init($class_name);
    }
});

// Registering additional class loader for libraries
spl_autoload_register(function ($class_name) {
    $filename = APP_LIB_DIR . str_replace("\\", "/", $class_name) . '.php';
    if (file_exists($filename)) {
        require_once $filename;
        static_class_init($class_name);
    }
});

set_error_handler(function ($error_number , $error_string) {

    $response_data = array("message" => $error_string, "code" => 500);

    JsonResponse::ifInstance()
        ->call("write", $response_data)
        ->otherwise(Consumer::call([TinyView::class, "show"], "error.tmpl", $response_data));

    return true;

}, E_ERROR);

// Set global exception handler
set_exception_handler(function ($exception) {

    if ($exception instanceof ApplicationException) {

        $message = $exception->getMessage();
        $http_code = $exception->getHttpCode();


    } else {

        $message = $exception->getMessage();
        $http_code = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR;

    }

    error_log("Exception: " . $exception->getMessage());
    error_log("Stacktrace: " . $exception->getTraceAsString());

    http_response_code($http_code);

    $response_data = array(
        "message" => $message,
        "code" => $http_code
    );

    JsonResponse::ifInstance()
        ->call("write", $response_data)
        ->otherwise(Consumer::call([TinyView::class, "show"], "error.tmpl", $response_data));

});

// Scan autorun directory for executable scripts
foreach (scandir(AUTORUN_SCRIPTS_PATH) as $file) {
    if ($file === "." || $file === "..")
        continue;
    require_once AUTORUN_SCRIPTS_PATH . $file;
}


function static_class_init($class_name) {
    if (class_exists($class_name) && method_exists($class_name, STATIC_CLASS_INIT_METHOD)) {
        $ref = new ReflectionMethod($class_name, STATIC_CLASS_INIT_METHOD);
        if ($ref->isStatic()) {
            Injector::run([$class_name, STATIC_CLASS_INIT_METHOD]);
        }
    }
}

function resource($class_name) {
    return Injector::getInstance()->injectByClassName($class_name);
}


function expect_string($arg) {
    assert(is_string($arg), "Expected \"String\" but \"" . gettype($arg) . "\" given");
}

function expect_number($arg) {
    assert(is_numeric($arg), "Expected \"Number\" but \"" . gettype($arg) . "\" given");
}
