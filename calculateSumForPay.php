<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Устанавливаем URL и токен
$getUrl = "https://php-test.dev.kviku.space/api/v1/task";
$postUrl = "https://php-test.dev.kviku.space/api/v1/task";
$bearerToken = "bearer-token";

// Инициализируем cURL для GET запроса
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://php-test.dev.kviku.space/api/v1/task',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $bearerToken,
    ],
]);

// решение только для текущей задачи, так как сервер не доверенный
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

// Выполняем GET запрос
$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo "Ошибка HTTP: код $httpCode";
    var_dump($response); // Для отладки
    exit(1);
}

$headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
curl_close($curl);

// Получаем заголовки
$creditDays = intval($headers['Await-Credit-Days']);
$creditPercent = floatval($headers['Await-Credit-Percent-Per-Day']);
$elementNumber = intval($headers['Await-Element-Number']);

// Декодируем JSON ответ
$data = json_decode($response, true);

if (is_null($data)) {
    echo 'Incorrect data!';
    exit(1);
}

// Извлекаем данные из нужного элемента
$userData = $data[$elementNumber - 1];
$email = $userData['email'];
$ip = $userData['ip'];
$firstName = $userData['firstName'];
$lastName = $userData['lastName'];

// Рассчитываем сумму кредита
$principal = 1;
$totalCredit = $principal * pow((1 + $creditPercent / 100), $creditDays);
$totalCredit = round($totalCredit, 2);

// Формируем данные для POST запроса
$postData = json_encode([
    "email" => $email,
    "ip" => $ip,
    "firstName" => $firstName,
    "lastName" => $lastName,
    "credit" => [
        "total" => $totalCredit
    ]
]);

// Инициализируем cURL для POST запроса
$ch = curl_init($postUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $bearerToken
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

// Выполняем POST запрос
$response = curl_exec($ch);
curl_close($ch);

// Выводим результат
echo $response;

