@props(['notes' => 'No notes provided to field-notes.blade.php.'])

<div class="w-full rounded-md mt-3 px-3 py-2 text-sm bg-notes-200 dark:bg-notes-800 border border-notes-300 dark:border-notes-900 text-slate-700 dark:text-slate-400">
    @php
        // Replace the <b> with stylised version.
        $formattedNotes = str_replace('<b>', '<b class="font-semibold text-notes-600 dark:text-notes-500">', $notes);
        echo $formattedNotes;
    @endphp
</div>