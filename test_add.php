<?php
$data = json_encode(['name' => 'John Doe', 'service_type' => 'other']);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/queue-management-system/api/add_customer.php', false, $context);
echo $result;
?>
