<?php
class BaseController {
    protected function view($file, $data = array()) {
        extract($data);
        require __DIR__ . '/../views/' . $file . '.php';
    }
}
?>
