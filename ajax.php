<?php
class ajax {

    var $db;

    function __construct() {
        include_once './db.php';
        $this->db = new MyPDO();
    }

    function getColumns() {
        $statement = $this->db->query("show columns from " . $_POST['table']);
        $columns = $statement->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($columns);
    }

    function doSearch() {
        $bind = array();
        foreach ($_POST['params'] as $param) {
            if (strlen($param['value']) > 0) {
                $bind[$param['name']] = $param['value'];
            }
        }
        if (!empty($bind)) {
            $where = array_map('prePrepare', array_keys($bind));

            $where = ' WHERE ' . implode(' AND ', $where);
        }
        if (!isset($where)) {
            $where = '';
        }
        $query = 'SELECT * FROM ' . $_POST['table'] . ' ' . $where;
        $statement = $this->db->prepare($query);
        $success = $statement->execute($bind);
        if (!$success) {
            print_r($this->db->errorInfo());
        }
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($results);
    }

}

function prePrepare($columnName) {
    return $columnName . ' LIKE (:' . $columnName . ')';
}

$a = new ajax();
header('Content-Type: application/json');
if (isset($_POST['action'])) {
    $a->$_POST['action']();
}