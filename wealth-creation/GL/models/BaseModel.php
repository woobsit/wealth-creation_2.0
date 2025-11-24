<?php
require_once __DIR__ . '/../helpers/DB.php';

class BaseModel {
    protected $db;

    public function __construct() {
        $this->db = DB::conn();
    }
}
?>
