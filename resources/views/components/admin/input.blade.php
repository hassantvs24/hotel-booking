@props(['type' => null, 'value' => null, 'placeholder' => null, 'name' => null, 'id' => null, 'label' => null, 'options' => [], 'multiple' => false])

@php
    $id = $id ?: $name;
    $type = $type ?: 'text';
    $value = old($name, $value);

    $ifSelected = function ($value, $key) {
        if (is_array($value)) {
            return in_array($key, $value) ? 'selected' : '';
        }
        return $value == $key ? 'selected' : '';
    };
@endphp

<div {{ $attributes->merge(['class' => 'form-group']) }}>

    @if($label)
        <label for="{{ $id }}">{{ $label }}</label>
    @endif

    @if($type === 'textarea')
        <textarea
            placeholder="{{ $placeholder }}"
            name="{{ $name }}"
            id="{{ $id }}"
            class="form-control">{{ $value }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" id="{{ $id }}" class="form-control" {{ $multiple ? 'multiple' : '' }}>
            @foreach($options as $key => $option)
                <option value="{{ $key }}" {{ $ifSelected($value, $key) }}>{{ $option }}</option>
            @endforeach
        </select>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            class="form-control"
        >
    @endif

    @error($name)
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>