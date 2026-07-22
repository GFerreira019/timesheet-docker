@props([
    'id' => 'select2-' . uniqid(),
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Selecione...',
    'dropdownParent' => null,
])

<div wire:ignore>
    <select id="{{ $id }}" name="{{ $name }}" class="form-select w-full" {{ $attributes }}>
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected($value == $selected)>{{ $label }}</option>
        @endforeach
    </select>
</div>

@once
    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <style>
            /* Select2 Dark Theme */
            .select2-container { width: 100% !important; }
            .select2-container--default .select2-selection--single {
                background-color: #0f172a !important;
                border-color: #334155 !important;
                color: #ffffff !important;
                height: 42px;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #ffffff !important; line-height: 42px; padding-left: 12px; font-size: 14px;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; }
            .select2-dropdown { background-color: #1e293b !important; border-color: #334155 !important; color: #e2e8f0; border-radius: 0.5rem; z-index: 1051; }
            .select2-container--default .select2-search--dropdown .select2-search__field {
                background-color: #0f172a !important; border: 1px solid #334155 !important; color: white !important; border-radius: 0.5rem;
            }
            .select2-results__option--highlighted.select2-results__option--selectable {
                background-color: #4f46e5 !important; color: white !important;
            }
            .select2-results__option { color: #e2e8f0 !important; }
        </style>
    @endpush
@endonce

@push('scripts')
    <script>
        $(document).ready(function() {
            const $select = $('#{{ $id }}');
            if (!$select.hasClass("select2-hidden-accessible")) {
                let config = {
                    placeholder: "{{ $placeholder }}",
                    width: '100%',
                    allowClear: true
                };
                
                @if($dropdownParent)
                    config.dropdownParent = $('#{{ $dropdownParent }}');
                @endif
                
                $select.select2(config);
            }
        });
    </script>
@endpush
