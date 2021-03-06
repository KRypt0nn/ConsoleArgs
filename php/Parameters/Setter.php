<?php

namespace ConsoleArgs;

/**
 * Объект сеттеров
 * Отвечает за объявление сет-параметров команд
 */
class Setter implements Parameter
{
    public array $names;
    public ?string $description = null; // Описание параметра, которое будет прикреплено к HelpCommand

    public string $separator;
    public ?string $defaultValue;
    public bool $required;

    protected Locale $locale;

    /**
     * Конструктор
     * 
     * @param string $name - имя сеттера
     * [@param string $separator = '='] - разделитель сеттера и значения
     * [@param string $defaultValue = null] - значение сеттера по умолчанию
     * [@param bool $required = false] - обязательно ли указание сеттера
     */
    public function __construct (string $name, string $separator = '=', string $defaultValue = null, bool $required = false)
    {
        $this->names        = [$name];
        $this->separator    = $separator;
        $this->defaultValue = $defaultValue;
        $this->required     = $required;

        $this->locale = new Locale;
    }

    /**
     * Установка описания
     * 
     * @param string $description - описание
     * 
     * @return Parameter
     */
    public function setDescription (string $description): Parameter
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Установка локализации
     * 
     * @param Locale $locale - объект локализации
     * 
     * @return Parameter
     */
    public function setLocale (Locale $locale): Parameter
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Добавление алиаса
     * 
     * @param string $name - алиас для добавления
     * 
     * @return Parameter
     */
    public function addAlias (string $name): Parameter
    {
        if (in_array ($name, $this->names))
            throw new \Exception (is_callable ($this->locale->alias_exists_exception) ?
                ($this->locale->alias_exists_exception) ($this, $name) : $this->locale->alias_exists_exception);

        $this->names[] = $name;

        return $this;
    }

    /**
     * Парсер параметров
     * 
     * @param array &$args - массив аргументов для парсинга
     * 
     * Возвращает найденый параметр или массив найдёных параметров, если их было указано несколько
     */
    public function parse (array &$args)
    {
        $args = array_values ($args);
        $l    = strlen ($this->separator);

        foreach ($this->names as $name)
            foreach ($args as $id => $arg)
                if (substr ($arg, 0, ($pos = strlen ($name) + $l)) == $name . $this->separator)
                {
                    $param = [substr ($arg, $pos)];

                    unset ($args[$id]);
                    $args = array_values ($args);

                    try
                    {
                        while (($altParam = $this->parse ($args)) !== $this->defaultValue)
                        {
                            if (is_array ($altParam))
                                $param = array_merge ($param, $altParam);
    
                            else $param[] = $altParam;
                        }
                    }

                    catch (\Throwable $e) {}
                    
                    return sizeof ($param) == 1 ?
                        $param[0] : $param;
                }

        if ($this->required)
            throw new \Exception (is_callable ($this->locale->undefined_param_exception) ?
                ($this->locale->undefined_param_exception) ($this) : str_replace ('%param_name%', current ($this->names), $this->locale->undefined_param_exception));

        return $this->defaultValue;
    }
}
