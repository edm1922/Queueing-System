<?php
$data = json_encode(['counter_id' => 1, 'services' => ['insurance', 'benefits']]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/queue-management-system/api/counter/update_services.php', false, $context);
echo $result;
?>
