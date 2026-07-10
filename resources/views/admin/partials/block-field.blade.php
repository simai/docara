<label class="larena-field larena-block-field larena-block-field--{{ $field['type'] }}">
    <span>{{ __('larena-docara::admin.'.$field['label_key']) }}@if ($field['required']) <span aria-hidden="true">*</span>@endif</span>
    @if ($field['type'] === 'text')
        <textarea name="blocks[{{ $index }}][settings][{{ $field['key'] }}]" rows="4" @if ($field['max_length']) maxlength="{{ $field['max_length'] }}" @endif @required($field['required'])>{{ $value }}</textarea>
    @elseif ($field['type'] === 'select')
        <select name="blocks[{{ $index }}][settings][{{ $field['key'] }}]" @required($field['required'])>
            @foreach ($field['options'] as $option)<option value="{{ $option }}" @selected($value === $option)>{{ __('larena-docara::admin.blocks.options.'.$option) }}</option>@endforeach
        </select>
    @elseif ($field['type'] === 'file')
        <select name="blocks[{{ $index }}][settings][{{ $field['key'] }}]" @required($field['required'])>
            <option value="">{{ __('larena-docara::admin.blocks.no_image') }}</option>
            @foreach ($availableImages as $image)<option value="{{ $image->logical_ref }}" @selected($value === $image->logical_ref)>{{ $image->display_name }} · {{ $image->mime_type }}</option>@endforeach
        </select>
    @else
        <input type="{{ $field['type'] === 'url' ? 'text' : 'text' }}" name="blocks[{{ $index }}][settings][{{ $field['key'] }}]" value="{{ $value }}" @if ($field['max_length']) maxlength="{{ $field['max_length'] }}" @endif @required($field['required']) @if ($field['type'] === 'url') inputmode="url" @endif>
    @endif
</label>
