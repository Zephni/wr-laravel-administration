@props(['json' => ''])

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
    dataSet(obj, path, value) {
        var way = path.replace(/\[/g, '.').replace(/\]/g, '').split('.'),
            last = way.pop();
    
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

        // If addType is 'group'
        if(addType == 'group') {
            if(thisType == 'object') {
                let newKey = prompt('New key name', 'newKey');
                if(newKey == null || newKey == '') return;
                this.dataSet(this.data, `${dottedPath}.${newKey}`, {});
            } else {
                this.dataSet(this.data, `${dottedPath}[${thisData.length}]`, {});
            }
        }

        // If addType is 'item'
        if(addType == 'item') {
            // If type is 'obj', ask for new key name before appending
            let newKey = prompt('New key name', 'newKey');
            if(newKey == null || newKey == '') return;
            
            if(thisType == 'object') {
                this.dataSet(this.data, `${dottedPath}.${newKey}`, '');
            } else {
                this.dataSet(this.data, `${dottedPath}[${thisData.length}]`, '');
            }
        }

        this.render(this.data, null);
    },
    renameAction(dottedPath) {
        let newKey = prompt('New key name', dottedPath.split('.').pop());
        if(newKey == null || newKey == '') return;
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
                dottedPath = baseDottedPath !== null ? `${baseDottedPath}.${key}` : `${key}`;
            {{-- } --}}
            
            if (this.isObject(value)) {
                let keyIsInt = !isNaN(Number(key));

                html += `
                <div class='text-slate-900'>
                    <div class='group flex flex-row items-center `+(keyIsInt ? 'mt-1.5' : 'mt-1.5')+`'>
                        <label class='text-sm font-bold'>
                            <i class='`+(value instanceof Array ? 'fas fa-list-ul' : 'far fa-folder')+` text-slate-500 mr-1.5'></i>
                            <span x-on:click='` + 'renameAction(`'+dottedPath+'`)' + `' title='Rename' class='cursor-text'>
                                ${dottedPath == 'data' ? '' : (keyIsInt ? '#' + key : key)}
                            </span>
                            <span class='opacity-30'>➤
                                {{-- ${dottedPath}: ${value instanceof Array ? 'array' : 'object'} --}}
                            </span>
                        </label>
                        {{-- Options --}}
                        <div class='relative top-[-1px] opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center gap-3 ml-3 font-bold'>
                            <button type='button' class='text-sm text-teal-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'addAction(`group`, `'+dottedPath+'`)' + `'
                                title='Add group'
                            >+ group</button>
                            <button type='button' class='text-sm text-teal-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'addAction(`item`, `'+dottedPath+'`)' + `'
                                title='Add item'
                            >+ item</button>
                            <button type='button' class='text-sm text-teal-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'deleteAction(`'+dottedPath+'`)' + `'
                                title='Delete'
                            >x delete</button>
                        </div>
                    </div>
                    <div class='border-l-2 border-dotted ml-2 pl-2'>
                        ${this.render(value, dottedPath)}
                    </div>
                </div>`;
            } else {
                html += `
                <div class='group text-slate-800'>
                    <div class='flex flex-row gap-4 items-center py-1'>
                        <label x-on:click='` + 'renameAction(`'+dottedPath+'`)' + `' title='Rename' class='cursor-text'>
                            <span class='text-sm font-bold'>${key}</span>
                        </label>
                        <input type='text'
                            class='w-72 px-2 py-0.5 border border-slate-400 text-black dark:text-black rounded-md text-sm'
                            name='${key}'
                            value='${String(value)}' />
                        <div class='opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center gap-3 font-bold'>
                            <button type='button' class='text-sm text-teal-600 hover:text-teal-500'
                                x-on:click.prevent='` + 'deleteAction(`'+dottedPath+'`)' + `'
                                title='Delete'
                            >x delete</button>
                        </div>
                    </div>
                </div>`;
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

    {{-- Debug, display this.data as pure prettified json --}}
    <pre x-html="renderDisplayJson()"></pre>

</div>