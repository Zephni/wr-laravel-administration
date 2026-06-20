@props([
    'options' => [],
    'label' => null,
    'fieldName' => 'multi_field',
    'items' => [],
    'maxFormGroups' => 0,
    'addItemLabel' => 'Add row',
    'emptyText' => 'No rows yet.',
    'layout' => 'row',
    'columns' => 4,
    'formGroups' => [],
    'existingImages' => []
])

<div class="{{ $options['containerClass'] ?? 'w-full' }}">

    @if(!empty($label))
        @themeComponent('forms.label', [
            'label' => $label,
            'attributes' => Arr::toAttributeBag([
                'class' => $options['labelClass'] ?? ''
            ])
        ])
    @endif

    @livewire('wrla.multi-upload-fields.multi-form-groups', [
        'fieldName'         => $fieldName,
        'items'             => $items,
        'maxFormGroups'     => $maxFormGroups,
        'addItemLabel'      => $addItemLabel,
        'emptyText'         => $emptyText,
        'layout'            => $layout,
        'columns'           => $columns,
        'formGroups'        => $formGroups,
        'existingImages'    => $existingImages,
    ])

</div>
