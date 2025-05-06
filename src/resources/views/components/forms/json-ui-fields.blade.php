@props([
    'json' => '',
])

<div x-data="{
    data: {},
    init() {
        try {
            this.data = { data: {{ $json }} };
        } catch(e) {
            this.data = {};
        }
    },
    isObject(val) { return typeof val === 'object' && val !== null; },
    objectAsString(obj) {
        return JSON.parse(JSON.stringify(obj)); // For debugging only
    },
    dataGet(obj, path, defaultValue = undefined) {
        return path.split('.').reduce((acc, key) => {
            if (acc && Object.prototype.hasOwnProperty.call(acc, key)) return acc[key];
            return defaultValue;
        }, obj);
    },
    dataSet(obj, path, value, type = 'string') {
        var way = path.replace(/\[/g, '.').replace(/\]/g, '').split('.'),
            last = way.pop();

        if (type == 'number') { 
            value = Number(value);
        } else if(type == 'boolean') {
            if(value == 1) value = true;
            else if(value == 0) value = false;
            else if(value == 'true') value = true;
            else if(value == 'false') value = false;
        }
    
        way.reduce(function (o, k, i, kk) {
            return o[k] = o[k] || (isFinite(i + 1 in kk ? kk[i + 1] : last) ? [] : {});
        }, obj)[last] = value;
    },
    dataDelete(obj, path) {
        var way = path.replace(/\[/g, '.').replace(/\]/g, '').split('.'),
            last = way.pop();

        let parts = path.split('.');
        let current = obj;

        for (let i = 0; i < parts.length - 1; i++) {
            if (!current.hasOwnProperty(parts[i])) return;
            current = current[parts[i]];
        }

        if (current instanceof Array && !isNaN(last)) {
            current.splice(last, 1);
        } else {
            delete current[parts[parts.length - 1]];
        }
    },
    entries(obj) {
        // Sort so that non-array items come first, as in PHP
        const allEntries = Object.entries(obj).map(([k,v]) => [k, v]);
        const nonArrays = allEntries.filter(([,v]) => !Array.isArray(v) && !this.isObject(v));
        const arrays    = allEntries.filter(([,v]) => Array.isArray(v) || this.isObject(v));
        return [...nonArrays, ...arrays];
    },
    addAction(addType, dottedPath) { // objType only used with 'group' addType
        // Get data and type at dottedPath
        let thisData = this.dataGet(this.data, dottedPath, null);
        let thisType = thisData instanceof Array ? 'array' : 'object';
        {{-- alert(`This type: ${thisType}, Dotted path: ${dottedPath}`); --}}

        // Values
        let newKey, newFullKeyPath, newValue = null;

        let validKeyFound = false;
        while(!validKeyFound) {    
            // If addType is 'group'
            if(addType == 'group') {
                newValue = {};

                if(thisType == 'object') {
                    let newKey = prompt('New key name', 'new_group');
                    if(newKey == null || newKey == '') return;
                    newFullKeyPath = `${dottedPath}.${newKey}`;
                } else {
                    newFullKeyPath = `${dottedPath}[${thisData.length}]`;
                }
            }
            // If addType is 'item'
            else if(addType == 'item') {
                
                if(thisType == 'object') {
                    let newKey = prompt('New key name', 'new_key');
                    if(newKey == null || newKey == '') return;
                    newFullKeyPath = `${dottedPath}.${newKey}`;
                    newValue = '';
                } else {
                    newFullKeyPath = `${dottedPath}[${thisData.length}]`;
                    newValue = '';
                }
            }

            // Get last element of newFullKeyPath (split by .) - trust me we need to do this rather than just using newKey
            let newKeyPathParts = newFullKeyPath.split('.');
            let newKey = newKeyPathParts[newKeyPathParts.length - 1];

            // First, check if newKey already exists in this data
            let newKeyExists = this.dataGet(this.data, newFullKeyPath, null);
            if(newKeyExists !== null) {
                let overrideKey = confirm('`'+newKey+'` key already exists, override this key?');
                if(!overrideKey) continue;
            }

            validKeyFound = true;
        }

        // Add new key => value to data
        this.dataSet(this.data, newFullKeyPath, newValue);

        // Render the data again
        this.render(this.data, null);

        // Focus new input field
        setTimeout(() => {
            let newInput = document.getElementById(`wrla-json-ui-input-${newFullKeyPath}`);
            if(newInput) {
                newInput.focus();
                newInput.select();
            }
        }, 50);
    },
    renameAction(dottedPath) {
        // Get parent dotted path so we can check if this key already exists
        let parentDottedPath = dottedPath.split('.').slice(0, -1).join('.');

        // Vars
        let validKeyFound = false;
        let newKey = null;

        while(!validKeyFound) {
            // Ask for new key name
            newKey = prompt('New key name', dottedPath.split('.').pop());
            if(newKey == null || newKey == '') return;
    
            // First, check if newKey already exists in this data
            let newKeyExists = this.dataGet(this.data, `${parentDottedPath}.${newKey}`, null);
            if(newKeyExists !== null) {
                let overrideKey = confirm('`'+newKey+'` key already exists, override this key?');
                if(!overrideKey) continue;
            }

            validKeyFound = true;
        }

        let thisData = this.dataGet(this.data, dottedPath, null);
        let thisType = thisData instanceof Array ? 'array' : 'object';

        let oldKey = dottedPath.split('.').pop();
        let newDottedPath = dottedPath.replace(oldKey, newKey);

        if(thisType == 'object') {
            this.dataSet(this.data, newDottedPath, this.dataGet(this.data, dottedPath));
            this.dataDelete(this.data, dottedPath);
        } else {
            this.dataSet(this.data, newDottedPath, this.dataGet(this.data, dottedPath));
            this.dataDelete(this.data, dottedPath);
        }
    },
    deleteAction(dottedPath) {
        this.dataDelete(this.data, dottedPath);
    },
    updateValueAction(dottedPath, value, type) {
        this.dataSet(this.data, dottedPath, value, type);
    },
    render(obj, dottedPath = null) {
        let html = '';
        let baseDottedPath = dottedPath;

        for (const [key, value] of this.entries(obj)) {
            // If obj type is array, use array[] style key within dotted path
            {{-- if(obj instanceof Array) {
                dottedPath = `${baseDottedPath}[0]`;
            }
            // Otherwise just use key
            else { --}}
                dottedPath = (baseDottedPath !== null ? `${baseDottedPath}.` : ``) + key;
            {{-- } --}}
            
            if (this.isObject(value)) {
                let keyIsInt = !isNaN(Number(key));

                html += `
                    <div class='group flex flex-row items-center `+(keyIsInt ? 'mt-2.5' : 'mt-2 mb-1')+` text-slate-900'>
                        <label class='text-sm font-bold whitespace-nowrap'>
                            <span class='`+(value instanceof Array ? 'text-teal-600' : 'text-amber-600')+`'>
                                <i class='`+(value instanceof Array ? 'fas fa-list-ul' : 'far fa-folder')+` mr-1.5'></i>
                                <span x-on:click='` + 'renameAction(`' + dottedPath + '`)' + `' title='Rename' class='cursor-text'>
                                    ${dottedPath == 'data' ? '' : (keyIsInt ? '#' + key : key)}
                                </span>
                            </span>
                            <span class='opacity-30'>➤
                                {{-- ${dottedPath}: ${value instanceof Array ? 'array' : 'object'} --}}
                            </span>
                        </label>
                        {{-- Options --}}
                        <div class='relative top-[-1px] opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center gap-4 ml-3 font-bold'>
                            <button type='button' class='text-sm text-slate-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'addAction(`group`, `' + dottedPath + '`)' + `'
                                title='Add group'
                            >+ group</button>
                            <button type='button' class='text-sm text-slate-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'addAction(`item`, `' + dottedPath + '`)' + `'
                                title='Add item'
                            >+ item</button>
                            <button type='button' class='text-sm text-slate-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'deleteAction(`' + dottedPath + '`)' + `'
                                title='Delete'
                            >x remove</button>
                        </div>
                    </div>
                    <div class='flex flex-col `+(value instanceof Array ? 'border-teal-600' : 'border-amber-600')+` border-l-2 border-dotted ml-1 pl-3'>
                        ${this.render(value, dottedPath)}
                    </div>
                `;
            } else {
                let type = 'text';
                let fieldType = typeof value;
                let selectoptions = null;

                if(fieldType == 'number') {
                    type = 'number';
                } else if(fieldType == 'boolean') {
                    type = 'checkbox';
                    checkboxValue = value ? 1 : 0;
                } else if(fieldType == 'select') {
                    type = 'select';
                    selectOptions = [
                        {text: 'true', value: 1},
                        {text: 'false', value: 0},
                    ];
                } else if(fieldType == 'non_editable') {
                    type = 'non_editable';
                }

                // If type is 'select', set selected to true for the current value
                if(type == 'select') {
                    selectOptions = selectOptions.map(option => {
                        option.selected = (option.value == value);
                        return option;
                    });
                }

                html += `
                    <div class='group flex gap-4 items-center py-1 text-slate-800'>
                        <label x-on:click='` + 'renameAction(`' + dottedPath + '`)' + `' title='Rename' class='cursor-text w-min whitespace-nowrap'>
                            <span class='text-sm font-bold !text-slate-600'>${key}</span>
                        </label>
                        <div class='flex items-center gap-3'>

                            {{-- Display HTML element based on type --}}
                            ` + (() => {
                                if (type === 'select') {
                                    return `
                                        <select
                                            x-ref='wrla-json-ui-input-` + dottedPath + `'
                                            id='wrla-json-ui-input-` + dottedPath + `'
                                            class='w-72 px-2 py-0.5 border border-slate-400 text-black dark:text-black rounded-md text-sm'
                                            name='${key}'
                                            x-on:change='updateValueAction(\`` + dottedPath + `\`, $event.target.value, \``+ fieldType +`\`)'
                                        >
                                            <template x-for='(option) in selectOptions'>
                                                <option :value='option.value' :selected='option.selected ?? false'>
                                                    <span x-text='option.text'></span>
                                                </option>
                                            </template>
                                        </select>
                                    `;
                                } else if (type === 'checkbox') {
                                    return `
                                        <input
                                            type='checkbox'
                                            id='wrla-json-ui-input-` + dottedPath + `'
                                            class='w-4 h-4 text-teal-600 border-slate-400 rounded-md cursor-pointer'
                                            name='${key}'
                                            x-on:change='updateValueAction(\`` + dottedPath + `\`, $event.target.checked ? 1 : 0, \``+ fieldType +`\`)'
                                            x-bind:checked='` + (checkboxValue ? 'true' : 'false') + `'
                                        />
                                    `;
                                } else if (type === 'non_editable') {
                                    return `
                                        <p
                                            id='wrla-json-ui-input-` + dottedPath + `'
                                            class='text-sm text-slate-800'
                                        >
                                            ${String(value)}
                                        </p>
                                    `;
                                } else {
                                    return `
                                        <input
                                            type='` + type + `'
                                            id='wrla-json-ui-input-` + dottedPath + `'
                                            class='w-72 px-2 py-0.5 border border-slate-400 text-black dark:text-black rounded-md text-sm'
                                            name='${key}'
                                            value='${String(value)}'
                                            x-on:change='updateValueAction(\`` + dottedPath + `\`, $event.target.value, \``+ fieldType +`\`)'
                                        />
                                    `;
                                }
                            })() + `
                            

                            <div class='opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center gap-3 font-bold ml-2'>
                                <button type='button' class='text-sm text-slate-300 hover:text-teal-500'
                                    x-on:click.prevent='` + 'deleteAction(`'+dottedPath+'`)' + `'
                                    title='Delete'
                                >remove</button>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        return html;
    },
    renderDisplayJson() {
        return JSON.stringify(this.data.data, null, 2);
    }
}">
    <!-- Render element -->
    <div x-html="render(data)"></div>

    {{-- Hidden input to store JSON data, may have options to show this at some point --}}
    <div class="text-sm text-slate-700 mt-7 px-6 py-4 bg-white">
        <span class="font-bold">JSON:</span>
        <textarea
            {{ $attributes->merge()->except(['class']) }}
            class="w-full h-64 px-2 py-0.5 border border-slate-400 text-black dark:text-black rounded-md text-sm"
            readonly
            x-html="renderDisplayJson()"></textarea>
    </div>

</div>