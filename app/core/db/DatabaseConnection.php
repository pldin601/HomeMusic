<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 16.07.2015
 * Time: 9:53
 */

namespace app\core\db;


use app\core\exceptions\ApplicationException;
use app\core\injector\Injectable;
use app\core\logging\Logger;
use app\core\modular\Event;
use app\lang\MLArray;
use app\lang\option\Option;
use app\lang\singleton\Singleton;
use app\lang\singleton\SingletonInterface;
use PDO;
use PDOStatement;

class DatabaseConnection implements SingletonInterface, Injectable {

    use Singleton;

    /** @var PDO $pdo */
    private $pdo;

    /** @var DatabaseConfiguration $configuration */
    private $configuration;

    public static function class_init() {

        Event::callEventListeners("database.init");

    }

    protected function __construct() {

        $this->configuration = Event::applyFilters(
            "database.configure",
            new DatabaseConfiguration()
        );

        $this->connect();

    }


    /**
     * @return $this
     * @throws ApplicationException
     */
    private function connect() {

        $pdo_dsn        = $this->configuration->getDsnUri();
        $pdo_login      = $this->configuration->getDsnLogin();
        $pdo_password   = $this->configuration->getDsnPassword();
        $pdo_options    = Event::applyFilters("database.pdo.options", array(
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES  => false,
            PDO::ATTR_PERSISTENT        => true,
        ));

        $this->pdo = new PDO($pdo_dsn, $pdo_login, $pdo_password, $pdo_options);

        return $this;

    }

    /**
     * @param callable $callable
     * @return mixed
     */
    public static function doInConnection(callable $callable) {

        $conn = self::getInstance();

        return $conn->doInTransaction($callable);

    }

    /**
     * @param callable $callable
     * @return mixed
     */
    public function doInTransaction(callable $callable) {

        return call_user_func($callable, $this);

    }

    /**
     * @return PDO
     */
    public function getPDO() {
        return $this->pdo;
    }

    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function finishTransaction() {
        return $this->pdo->rollBack();
    }

    /**
     * @param string $query
     * @param array $params
     * @return string
     */
    public function queryQuote($query, $params = []) {

        $position = 0;

        $arguments = preg_replace_callback("/(\\?)|(\\:\\[a-z]+)/", function ($match) use ($params, &$position) {
            $array_key = $match[0] === '?' ? $position++ : $match[0];
            if (!isset($params[$array_key])) {
                return 'NULL';
            }
            return $this->pdo->quote($params[$array_key], PDO::PARAM_STR);
        }, $query);


        return $arguments;

    }

    /**
     * @param $query
     * @param $params
     * @throws ApplicationException
     * @return \PDOStatement
     */
    private function createResource($query, $params = null) {

        $queryString = $this->queryQuote($query, $params);

        Logger::printf($queryString);

        /**
         * @var PDOStatement $resource
         */
        $resource = Event::applyFilters(
            "database.pdo.statement.prepare",
            $this->pdo->prepare($queryString)
        );

        if ($resource === false) {
            throw new ApplicationException($this->pdo->errorInfo()[2]);
        }

        $resource->execute();

        Event::callEventListeners("database.pdo.statement.executed", $resource);

        if ($resource->errorCode() !== "00000") {
            throw new ApplicationException($resource->errorInfo()[2]);
        }

        return $resource;

    }

    /**
     * @param string $query
     * @param array $params
     * @param string $key
     * @param Callable $callback
     * @return MLArray
     */
    public function fetchAll($query, array $params = null, $key = null, callable $callback = null) {

        $resource = $this->createResource($query, $params);
        $db_result = $resource->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($db_result as $i => $row) {

            if (is_callable($callback)) {
                $row = call_user_func_array($callback, [$row, $i]);
            }

            if (!is_null($key)) {
                $k = $row[$key];
                unset($row[$key]);
                $result[$k] = $row;
            } else {
                $result[] = $row;
            }

        }

        return new MLArray($result);

    }

    /**
     * @param $query
     * @param array $params
     * @param callable $callback
     */
    public function eachRow($query, array $params = null, callable $callback) {

        $resource = $this->createResource($query, $params);
        $i = 0;

        while ($row = $resource->fetch(PDO::FETCH_ASSOC)) {
            call_user_func_array($callback, [ $row, $i++ ]);
            unset($row);
        }

        $resource->closeCursor();

    }

    /**
     * @param $query
     * @param array $params
     * @return \Generator
     * @throws ApplicationException
     */
    public function getGenerator($query, array $params = null) {
        $resource = $this->createResource($query, $params);
        while ($row = $resource->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function writeCSV($query, array $params = null) {

        header("Content-Type: application/json; charset=utf-8");

        $output = fopen("php://output", "w");
        $columns = array();
        $index = 0;

        $resource = $this->createResource($query, $params);

        for ($i = 0; $i < $resource->columnCount(); $i ++) {
            $columns[] = $resource->getColumnMeta($i)["name"];
        }

        fwrite($output, '{"header": '.json_encode($columns, JSON_UNESCAPED_UNICODE).', "data": [');

        while ($row = $resource->fetch(PDO::FETCH_NUM)) {
            if ($index ++ > 0) {
                fwrite($output, ',');
            }
            fwrite($output, json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        fwrite($output, ']}');

        fclose($output);

    }

    public function renderAllAsJson($query, array $params = null, $callback = null) {

        $resource = $this->createResource($query, $params);
        $columns = [];
        for ($i = 0; $i < $resource->columnCount(); $i ++) {
            $columns[] = $resource->getColumnMeta($i);
        }

        header("Content-Type: application/json; charset=utf8");

        if ($resource->rowCount() === 0) {
            echo json_encode(["columns" => [], "data" => []]);
            return;
        }

        echo '{';

        $i = 0;
        while ($row = $resource->fetch(PDO::FETCH_ASSOC)) {
            if (is_callable($callback)) {
                $row = $callback($row);
            }
            if ($i++ > 0) {
                echo ',';
            } else {
                echo '"columns":';
                echo json_encode(array_keys($row), JSON_UNESCAPED_UNICODE);
                echo ',';
                echo '"data":[';
            }
            echo json_encode(array_values($row), JSON_UNESCAPED_UNICODE);
        }

        echo ']}';

    }

    /**
     * @param string $query
     * @param array $params
     * @param Callable $callback
     * @return Option
     */
    public function fetchOneRow($query, array $params = null, $callback = null) {

        $resource = $this->createResource($query, $params);

        $row = $resource->fetch(PDO::FETCH_ASSOC);

        if ($row !== false && is_callable($callback)) {
            $row = call_user_func($callback, $row);
        }

        return Option::Some($row)->reject(false);

    }

    /**
     * @param string $query
     * @param array $params
     * @param int $column
     * @return Option
     */
    public function fetchOneColumn($query, array $params = null, $column = 0) {

        $resource = $this->createResource($query, $params);

        $row = $resource->fetchColumn($column);

        if (is_numeric($row)) {
            $row = intval($row);
        }

        return Option::Some($row)->reject(false);

    }

    /**
     * @param string $query
     * @param array $params
     * @param string $class
     * @param array|null $ctr_args
     * @throws ApplicationException
     * @return Option
     */
    public function fetchOneObject($query, array $params = null, $class, array $ctr_args = []) {

        $resource = $this->createResource($query, $params);

        $object = $resource->fetchObject($class, $ctr_args);

        return Option::Some($object)->reject(false);

    }

    /**
     * @param string $query
     * @param array|null $params
     * @param $class
     * @param array|null $ctr_args
     * @return array
     */
    public function fetchAllObjects($query, array $params = null, $class, array $ctr_args = null) {

        $resource = $this->createResource($query, $params);

        $objects = $resource->fetchAll(PDO::FETCH_CLASS, $class, $ctr_args);

        return new MLArray($objects);

    }

    /**
     * @param string $query
     * @param array|null $params
     * @return int
     */
    public function executeUpdate($query, array $params = null) {

        $resource = $this->createResource($query, $params);

        return $resource->rowCount();

    }

    /**
     * @param string $query
     * @param array|null $params
     * @return int
     */
    public function executeInsert($query, array $params = null) {

        $this->createResource($query, $params);

        return intval($this->pdo->lastInsertId(null));

    }

    /**
     * @param string $query
     * @param array $params
     */
    public function justExecute($query, array $params = null) {

        $this->createResource($query, $params)->closeCursor();

    }

    public function quote($var) {

        return $this->pdo->quote($var, PDO::PARAM_STR);

    }

    /**
     * @param $query
     * @param $params
     * @return string
     */
    public function generate($query, array $params = null) {

        return $this->queryQuote($query, $params);

    }

    /**
     * @return LightORM
     */
    public function getLightORM() {
        return new LightORM($this);
    }


} 
