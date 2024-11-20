<?php
$res = $pdo->query(qb()->select('*', false)->from('common_user')->exec())->fetchAll(PDO::FETCH_ASSOC);
var_dump($pdo);
$res = null;