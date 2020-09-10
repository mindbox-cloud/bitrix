<?php


namespace Mindbox\DTO;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Mindbox\Exceptions\MindboxException;
use Mindbox\XMLHelper\MindboxXMLSerializer;

/**
 * Class DTO
 *
 * @package Mindbox\DTO
 */
class DTO implements Countable, ArrayAccess, IteratorAggregate
{
    /**
     * Мета-данные, необходимы для корректной генерации XML из массива данных.
     */
    const XML_ITEM_NAME_INDEX = '@itemName';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'dto';

    /**
     * @var array Массив всех полей объекта.
     */
    protected $items = [];

    /**
     * Конструктор DTO.
     *
     * @param array $data Массив данных.
     */
    public function __construct(array $data = [])
    {
        $items = [];
        foreach ($data as $key => $value) {
            $DTOMap = static::getDTOMap();
            if (is_array($value) && isset($DTOMap[$key])) {
                $value[static::XML_ITEM_NAME_INDEX] = $DTOMap[$key]::getXmlName();
                $items[$key]                        = static::makeDTO($DTOMap[$key], $value);
            } else {
                $items[$key] = $value;
            }
        }

        $this->items = $items;
    }

    /**
     * Геттер для $DTOMap.
     *
     * @return array
     */
    public static function getDTOMap()
    {
        return static::$DTOMap;
    }

    /**
     * Инициализация объекта DTO по его имени.
     *
     * @param string $name Имя класса DTO.
     * @param mixed  $data Данные.
     *
     * @return mixed
     */
    protected static function makeDTO($name, $data)
    {
        if (!is_array($data)) {
            return $data;
        }

        return new $name($data);
    }

    /**
     * Возвращает значение поля DTO по его имени.
     *
     * @param string $name    Имя поля DTO.
     * @param mixed  $default Значение по умолчанию, будет возвращено в случае, если такое поле отсутствует.
     *
     * @return mixed
     */
    public function getField($name, $default = null)
    {
        if (isset($this->items[$name])) {
            $field = $this->items[$name];

            return $this->unsetMetaInfo($field);
        }

        return $default;
    }

    /**
     * Устанавливает в DTO поле с переданным названием.
     *
     * @param string $name  Название.
     * @param mixed  $value Значение.
     *
     * @return void
     */
    public function setField($name, $value)
    {
        $DTOMap = static::getDTOMap();
        if (is_array($value) && isset($DTOMap[$name])) {
            // Could be a DTO
            $value = new $DTOMap[$name]($value);
        }
        $this->items[$name] = $value;
    }

    /**
     * Возвращает список всех ключей массив полей DTO.
     *
     * @return array
     */
    public function getFieldNames()
    {
        $fields = $this->items;
        return array_keys($this->unsetMetaInfo($fields));
    }

    /**
     * Возвращает все поля DTO.
     *
     * @return array
     */
    public function all()
    {
        $fields = $this->items;

        return $this->unsetMetaInfo($fields);
    }

    /**
     * Возвращает все поля DTO в формате JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->getFieldsAsArray(), $options) ?: '';
    }

    /**
     * Рекурсивно убирает из переданного массив мета-информацию.
     *
     * @param mixed $value Массив данных.
     *
     * @return array
     */
    private function unsetMetaInfo($value)
    {
        if (is_array($value) || is_subclass_of($value, DTO::class)) {
            unset($value[static::XML_ITEM_NAME_INDEX]);
            foreach ($value as &$item) {
                $item = $this->unsetMetaInfo($item);
            }
        }

        return $value;
    }

    /**
     * Возвращает все поля DTO в формате XML.
     *
     * @return string
     */
    public function toXML()
    {
        return MindboxXMLSerializer::fromArrayToXML(static::getXmlName(), $this->getFieldsAsArray(false));
    }

    /**
     * Геттер для $xmlName.
     *
     * @return string
     */
    public static function getXmlName()
    {
        return static::$xmlName;
    }

    /**
     * Возвращает все поля DTO в виде массива.
     *
     * @param bool $unsetXmlMetaInfo Флаг, сообщающий о том нужно ли очищать мета-информацию.
     *
     * @return array
     */
    public function getFieldsAsArray($unsetXmlMetaInfo = true)
    {
        $fields = $this->items;
        if ($unsetXmlMetaInfo) {
            unset($fields[static::XML_ITEM_NAME_INDEX]);
        }

        return array_map(
            function ($value) use ($unsetXmlMetaInfo) {
                return $value instanceof DTO ?
                    $value->getFieldsAsArray($unsetXmlMetaInfo) :
                    ($unsetXmlMetaInfo ? $this->unsetMetaInfo($value) : $value);
            },
            $fields
        );
    }

    /**
     * Возвращает количество элементов, модержащихся в DTO.
     *
     * @return int
     */
    public function count()
    {
        $fields = $this->items;
        return count($this->unsetMetaInfo($fields));
    }

    /**
     * Возвращает ArrayIterator.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Возвращает элемент DTO по заданному ключу.
     *
     * @param mixed $key Ключ.
     *
     * @return mixed
     * @throws MindboxException
     */
    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            throw new MindboxException('Undefined index: ' . $key);
        }

        return $this->items[$key];
    }

    /**
     * Проверяет, существует ли заданный ключ в элементах DTO.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Устанавливает заданное значение по переданному ключу в элементы DTO.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Удаляет заданное значение из элементов DTO по ключу.
     *
     * @param string $key Ключ.
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }
}
