<?php
// includes/helpers.php

function fmt_money($v) {
    return number_format((float)$v, 2, '.', ',');
}

function validate_table_name($name) {
    return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $name);
}

function audit_log($db, $entity, $entity_id, $action, $payload, $user) {
    $stmt = $db->prepare("INSERT INTO audit_log (entity, entity_id, action, payload, user_name) VALUES (:e,:id,:act,:pl,:u)");
    $stmt->execute(array(':e'=>$entity, ':id'=>$entity_id, ':act'=>$action, ':pl'=>$payload, ':u'=>$user));
}
?>
