<?php

namespace WebRegulate\LaravelAdministration\Classes;

class BrowsableColumn
{
    public ?string $label = null;
    public string $type = 'string';
    public null|int|string $width = null;
    public array $options = [];

    public function __construct(?string $label, string $type, null|int|string $width = null)
    {
        $this->label = $label;
        $this->type = $type;
        $this->width = $width;
    }

    public static function make(?string $label, string $type, null|int|string $width = null): self
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

    public function renderLabel(): string
    {
        return $this->label;
    }

    public function renderValue($model, $column): string
    {
        $value = $this->getOption('value') ?? $model->{$column};

        if($this->type == 'string')
        {
            return $value;
        }
        elseif($this->type == 'image')
        {
            $renderedView = view(
                WRLAHelper::getViewPath('components.forced-aspect-image', false), [
                "src" => $value,
                "class" => $this->getOption('containerClass') ?? 'border-2 border-primary-600',
                "imageClass" => 'wrla_image_preview '.$this->getOption('imageClass') ?? '',
                "aspect" => $this->getOption('aspect'),
                "rounded" => $this->getOption('rounded') ?? false,
            ])->render();

            return <<<BLADE
                <a href="{{ $value }}" target="_blank">$renderedView</a>
            BLADE;
        }

        return $value;
    }
}
