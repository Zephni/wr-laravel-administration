@themeComponent('forms.button', [
    'href' => route('wrla.special.login-as', [
        'id' => $modelInstance->id
    ]),
    'size' => 'small',
    'color' => 'primary',
    'type' => 'button',
    'text' => 'Login',
    'icon' => 'fa fa-lock relative top-[-1px] !mr-[3px] text-[10px]',
    'attr' => [
        'onclick' => "confirm('Login as ' + '{$modelInstance->email}' + '?')",
        'title' => 'Login as ' . $modelInstance->name
    ]
])
