@props(['json' => ''])

<div x-data="{
    data: {},
    init() {
        try {
            this.data = {{ $json }};
        } catch(e) {
            this.data = {};
        }
    },
    isObject(val) { return typeof val === 'object' && val !== null; },
    entries(obj) {
        // Sort so that non-array items come first, as in PHP
        const allEntries = Object.entries(obj).map(([k,v]) => [k.startsWith('_wrla_key_') ? k.slice(10) : k, v]);
        const nonArrays = allEntries.filter(([,v]) => !Array.isArray(v) && !this.isObject(v));
        const arrays    = allEntries.filter(([,v]) => Array.isArray(v) || this.isObject(v));
        return [...nonArrays, ...arrays];
    },
    buildHtml(obj) {
        let html = '';
        for (const [key, value] of this.entries(obj)) {
            if (this.isObject(value)) {
                let keyIsNumber = !isNaN(Number(key));

                html += `
                <div class='text-teal-900'>
                    <div class='flex flex-row items-center'>
                        <label class='text-sm font-bold'>
                            ${!keyIsNumber ? key : '#' + key} <span class='opacity-30'>➤</span>
                        </label>
                    </div>
                    <div class='border-l-2 border-dotted ml-2 pl-2'>
                        ${this.buildHtml(value)}
                    </div>
                </div>`;
            } else {
                html += `
                <div class='text-teal-800'>
                    <div class='flex flex-row gap-4 items-center py-1'>
                        <label class='text-sm font-bold'>${key}</label>
                        <input type='text'
                            class='w-72 px-2 py-0.5 border border-slate-400 text-black dark:text-black rounded-md text-sm'
                            name='${key}'
                            value='${String(value)}' />
                    </div>
                </div>`;
            }
        }
        return html;
    }
}">
    <!-- Replaces the old template x-for -->
    <div x-html="buildHtml(data)"></div>
</div>