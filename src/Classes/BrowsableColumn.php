<?php

namespace WebRegulate\LaravelAdministration\Classes;

class BrowsableColumn
{
    public $label = null;
    public $type = 'string';
    public $width = null;
    public array $options = [];

    public function __construct($label, $type, $width)
    {
        $this->label = $label;
        $this->type = $type;
        $this->width = $width;
    }

    public static function make($label, $type, $width): self
    {
        return new self($label, $type, $width);
    }

    public function setOption($key, $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}