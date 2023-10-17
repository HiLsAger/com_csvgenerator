<?php defined('_JEXEC') or die;
/**
 * @package     Joomla.Administrator
 * @subpackage  com_csvgenerator
 */

use Joomla\CMS\MVC\Controller\BaseController;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\Database\DatabaseDriver;

class CSVGeneratorController extends BaseController
{
    function display($cachable = false, $urlparams = [])
    {
        // $this->default_view = 'csvgenerator';
        // parent::display($cachable, $urlparams);

        $input = JFactory::getApplication()->input;
        $view = $input->getCmd('view', 'default');

        if ($view === 'generate') {
            $this->displayGenerate($cachable, $urlparams);
        } else {
            $this->displayBase($cachable, $urlparams);
        }
    }

    public function displayBase($cachable = false, $urlparams = [])
    {
        $this->default_view = 'csvgenerator';
        // Добавьте код для базовой страницы
        parent::display($cachable, $urlparams);
    }



    public function displayGenerate($cachable = false, $urlparams = [])
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['product_id', 'short_description_ru-RU', 'manufacturer_code', 'name_ru-RU', 'description_ru-RU', 'product_price']))
            ->from($db->quoteName('p67l9_jshopping_products'));
        $db->setQuery($query);
        $results = $db->loadAssocList();

        $csvData = [];

        if (!empty($results)) {
            foreach ($results as $row) {

                $productImages = $this->getProductImages($row['product_id']);

                $images = implode(', ', $productImages);

                $attributes = $this->getAttributes($row['product_id']);

                $desc = str_replace(['<br>', "\r\n"], [',', ' '], $row['description_ru-RU']);
                $pairStrings = explode(", ", $desc);

                $descArray = [
                    'Марка'     => '',
                    'Модель'    => '',
                    'Год'       => '',
                    'Кузов'     => '',
                    'Двигатель' => '',
                    'Лев_Прав'  => '',
                ];

                foreach ($pairStrings as $pairString) {
                    // Разделяем каждую пару "ключ-значение" на отдельные элементы
                    $pair = explode(": ", $pairString, 2);

                    if (count($pair) == 2) {
                        $key = trim($pair[0]);
                        $value = trim($pair[1]);

                        $descArray[$key] = $value;
                    }
                }

                // Создаем массив для CSV
                $csvRow = [
                    $row['manufacturer_code'],
                    $row['name_ru-RU'],
                    $row['product_price'],
                    $row['short_description_ru-RU'],
                    $attributes['Марка'],
                    $attributes['Модель'],
                    $attributes['Год'],
                    $attributes['Кузов'],
                    $attributes['Двигатель'],
                    $attributes['Цвет'],
                    $attributes['Номер'],

                    $attributes['Верх/Низ'],
                    $attributes['Перед/Зад'],
                    $attributes['Лев/Прав'],
                    $attributes['Новый/БУ'],
                    $images
                ];

                $csvData[] = $csvRow;
            }
        }

        // Создаем CSV-файл
        $csvFileName = 'products.csv';
        $csvFilePath = JPATH_SITE . '/' . $csvFileName;
        $csvContent = implode(';', ['Артикул', 'Наименование', 'Цена', 'Комментарий', 'Марка', 'Модель', 'Год', 'Кузов', 'Двигатель', 'Цвет', 'Номер', 'Верх/Низ', 'Перед/Зад', 'Лев/Прав', 'Новый/БУ', 'Фото']) . PHP_EOL;

        foreach ($csvData as $row) {
            $csvContent .= implode(';', $row) . PHP_EOL;
        }

        File::write($csvFilePath, $csvContent);

        // Отправляем CSV-файл для скачивания
        Factory::getApplication()->setHeader('Content-Type', 'text/csv');
        Factory::getApplication()->setHeader('Content-Disposition', 'attachment; filename="' . $csvFileName . '"');
        echo $csvContent;
        Factory::getApplication()->close();

        return;
    }


    function getProductImages($productId)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('image_name'))
            ->from($db->quoteName('p67l9_jshopping_products_images'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId);


        $db->setQuery($query);
        $images = $db->loadColumn();

        // Формируем полные URL для изображений
        $imageUrls = [];
        if (!empty($images)) {
            $app = JFactory::getApplication();
            $rootPath = $app->get('root');
            $imagePath = JUri::root() . 'components/com_jshopping/files/img_products/full_';
            foreach ($images as $image) {
                $imageUrls[] = $imagePath . $image;
            }
        }

        return $imageUrls;
    }

    function getAttributes($productId)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['id', 'title_admin']))
            ->from($db->quoteName('p67l9_jshopping_custom_fields'));
        $db->setQuery($query);
        $results = $db->loadAssocList();

        if (!empty($results)) {
            $columns = [];
            foreach ($results as $item) {
                $columns[] = 'ba_custom_field_' . $item['id'];
            }

            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query
                ->select($db->quoteName('id_product'))
                ->select($db->quoteName('lang'))
                ->select(implode(', ', array_map([$db, 'quoteName'], $columns)))
                ->from($db->quoteName('p67l9_jshopping_custom_fields_values'))
                ->where($db->quoteName('id_product') . ' = ' . (int) $productId)
                ->where($db->quoteName('lang') . ' = ' . $db->quote('ru-RU'));

            $db->setQuery($query);
            $columnsResult = $db->loadAssocList();

            $attributes = [];

            for ($i = 0; $i < count($results); $i++) {
                $titleAdmin = $results[$i]['title_admin'];
                $customFieldData = $columnsResult[0];

                if (isset($customFieldData['ba_custom_field_' . $results[$i]['id']])) {
                    $json = json_decode($customFieldData['ba_custom_field_' . $results[$i]['id']], true);
                    $item = is_array($json) ? $json[0] : $customFieldData['ba_custom_field_' . $results[$i]['id']];
                    $attributes[$titleAdmin] = $item;
                }
            }
            return $attributes ? $attributes : [];
        }
        return [];
    }
}
