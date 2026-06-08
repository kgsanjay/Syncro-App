<?php
require 'core/init.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_VERIFY'] = 'c1cd4f93832b268429cfa9af26eb2b04e2c20a09d7e8daf655351a767c438767###1';
$payload = '{"response":"eyJzdWNjZXNzIjp0cnVlLCJjb2RlIjoiUEFZTUVOVF9TVUNDRVNTIiwiZGF0YSI6eyJtZXJjaGFudElkIjoiUEdURVNUUEFZVUFUODYiLCJtZXJjaGFudFRyYW5zYWN0aW9uSWQiOiJNVF81XzE3ODA3NTQxMTUiLCJ0cmFuc2FjdGlvbklkIjoiVDEyMzQ1Njc4OTAiLCJhbW91bnQiOjEwMDAwMCwic3RhdGUiOiJDT01QTEVURUQifX0="}';
file_put_contents('php://memory', $payload); // Can't easily override php://input
// We will just directly call the controller but we need to mock file_get_contents('php://input')
