<?

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

Loader::includeModule("highloadblock");

// Данные для создания HL-блока
$hlBlockData = [
    'NAME' => 'GeoIPData', // Системное имя
    'TABLE_NAME' => 'b_hl_geoip_data', // Имя таблицы в базе данных
];

$result = HighloadBlockTable::add($hlBlockData);

if ($result->isSuccess()) {
    $hlBlockId = $result->getId();

    // Добавление полей для HL-блока
    $userTypeEntity = new CUserTypeEntity();

    $fields = [
        [
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_IP',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'UF_IP',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => 'E',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y',
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
            'FIELD_NAME' => 'UF_DATA',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'UF_DATA',
            'SORT' => 200,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
        ],
    ];

    foreach ($fields as $field) {
        $userTypeEntity->Add($field);
    }

    echo "HL-блок с ID $hlBlockId создан и поля добавлены.";
} else {
    echo "Ошибка создания HL-блока: " . implode(', ', $result->getErrorMessages());
}
