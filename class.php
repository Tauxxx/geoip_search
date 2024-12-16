<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
// use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Application;
// use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Mail\Event;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * GeoIPSearchComponent
 * 
 * Выводит информацию о введенном пользователем ip адресе
 * - сохраняет в hl, 
 * - ищет по hl или через внешний api
 */
class GeoIPSearchComponent extends CBitrixComponent implements Controllerable
{
    const HL_TABLE_ID = 37;
    const ADMIN_EMAIL = 'admin@example.com';

    /**
     * Реализация метода configureActions для интерфейса Controllerable
     */
    public function configureActions()
    {
        return [
            'getGeoData' => [
                'prefilters' => [
                    // new ActionFilter\Authentication, // проверяет, авторизован ли пользователь
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * Обработчик действия для AJAX
     *
     * @param string $ip
     * @return array
     */
    public function getGeoDataAction(string $ip): array
    {
        if (!$this->validateIP($ip)) {
            throw new SystemException("Неверный IP-адрес");
        }

        $data = $this->getIPFromHL($ip);

        if (!$data) {
            $data = $this->fetchGeoData($ip);
            $this->saveIPToHL($ip, $data);
        } else {
            $data = json_decode($data["UF_DATA"], true);
        }

        return $data;
    }

    /**
     * Основной метод выполнения компонента
     */
    public function executeComponent()
    {
        try {
            $this->checkModules();
            $this->arResult = [];
            $this->includeComponentTemplate();
        } catch (SystemException $e) {
            $this->arResult = ["error" => $e->getMessage()];
            $this->includeComponentTemplate();
        }
    }

    /**
     * Проверка подключения модулей
     */
    protected function checkModules()
    {
        if (!Loader::includeModule("highloadblock")) {
            throw new SystemException(Loc::getMessage("HIGHLOADBLOCK_MODULE_NOT_INSTALLED"));
        }
    }

    /**
     * Валидация IP-адреса
     *
     * @param string $ip
     * @return bool
     */
    protected function validateIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Получение данных из HL блока по IP
     *
     * @param string $ip
     * @return array|null
     */
    protected function getIPFromHL(string $ip): ?array
    {
        $dataClass = $this->getHlData();
        $result = $dataClass::getList([
            'filter' => ['UF_IP' => $ip],
            'limit' => 1
        ])->fetch();

        return $result ?: null;
    }

    /**
     * Запись данных в HL блок
     *
     * @param string $ip
     * @param array $geoData
     */
    protected function saveIPToHL(string $ip, array $geoData)
    {
        $dataClass = $this->getHlData();
        $dataClass::add([
            'UF_IP' => $ip,
            'UF_DATA' => json_encode($geoData, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Запрос к GeoIP-сервису
     *
     * @param string $ip
     * @return array
     */
    protected function fetchGeoData(string $ip): array
    {
        $httpClient = new HttpClient();

        $apiUrl = 'https://api.sypexgeo.net/json/' . $ip;
        $response = $httpClient->get($apiUrl);

        if ($httpClient->getStatus() !== 200) {
            throw new SystemException(json_encode($httpClient->getStatus()) . "Ошибка запроса к GeoIP-сервису");
        }

        $geoData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SystemException("Ошибка обработки данных от GeoIP-сервиса");
        }

        return $geoData;
    }

    /**
     * Отправка ошибки на email
     * TODO: вызывать по необходимости
     * 
     * @param string $errorMessage
     */
    protected function sendErrorEmail(string $errorMessage)
    {
        Event::send([
            "EVENT_NAME" => "GEOIP_ERROR_REPORT",
            "LID" => Application::getInstance()->getContext()->getSite(),
            "C_FIELDS" => [
                "EMAIL_TO" => self::ADMIN_EMAIL,
                "ERROR_MESSAGE" => $errorMessage,
            ],
        ]);
    }

    protected function getHlData()
    {
        $hlBlock = \Bitrix\Highloadblock\HighloadBlockTable::getById(self::HL_TABLE_ID)->fetch();
        if (!$hlBlock) {
            throw new SystemException("HL блок не найден");
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlBlock);
        $dataClass = $entity->getDataClass();

        return $dataClass;
    }
}
