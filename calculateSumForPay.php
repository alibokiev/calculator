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
    CURLOPT_WRITEFUNCTION => function($ch, $chunk) {
        static $totalSize = 0;
        static $currentIndex = 0;
        static $awaitElementNumber = null;
        static $creditDays = null;
        static $creditPercent = null;

        // Получаем заголовки один раз
        if (is_null($awaitElementNumber)) {
            $info = curl_getinfo($ch);
            $awaitElementNumber = intval($info['Await-Element-Number']);
            $creditDays = intval($info['Await-Credit-Days']);
            $creditPercent = floatval($info['Await-Credit-Percent-Per-Day']);
        }

        // Обрабатываем кусок данных (например, строку JSON)
        $totalSize += strlen($chunk);

        // Разбираем JSON по частям и ищем нужный элемент
        $data = json_decode($chunk, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach ($data as $user) {
                $currentIndex++;
                if ($currentIndex === $awaitElementNumber) {
                    // Нашли нужный элемент, можем остановиться
                    processUser($user, $creditDays, $creditPercent);
                    return -1; // Остановить загрузку, так как элемент найден
                }
            }
        }

        // Если не нашли, продолжаем загрузку
        return strlen($chunk);
    }
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

curl_close($curl);

function processUser($user, $creditDays, $creditPercent): void
{
    global $postUrl, $bearerToken;

    $email = $user['email'];
    $ip = $user['ip'];
    $firstName = $user['firstName'];
    $lastName = $user['lastName'];

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
    $postResponse = curl_exec($ch);
    if ($postResponse === false) {
        echo 'Ошибка POST запроса: ' . curl_error($ch);
    } else {
        echo 'Ответ POST: ' . $postResponse;
    }

    curl_close($ch);
}
