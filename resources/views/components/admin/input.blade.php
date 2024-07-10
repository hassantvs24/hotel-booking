@props([
    'type' => null,
    'value' => null,
    'placeholder' => null,
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'multiple' => false,
    'additionalClasses' => null
])

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
        @if($type != 'toggle')
            <label for="{{ $id }}">{{ $label }}</label>
        @endif
    @endif

    @if($type === 'textarea')
        <textarea
                placeholder="{{ $placeholder }}"
                name="{{ $name }}"
                id="{{ $id }}"
                class="form-control @error($name) is-invalid @enderror">{{ $value }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" id="{{ $id }}"
                class="form-control is-select {{ $additionalClasses }} @error($name) is-invalid @enderror" {{ $multiple ? 'multiple' : '' }}>
            <option value="">Select {{ $label }}</option>
            @foreach($options as $key => $option)
                <option value="{{ $key }}" {{ $ifSelected($value, $key) }}>{{ $option }}</option>
            @endforeach
        </select>

    @elseif ($type === 'toggle')
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" name="{{ $name }}" id="{{ $id }}" @if ($value) checked @endif>
            <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
        </div>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $multiple ? 'multiple' : '' }}
            class="form-control @error($name) is-invalid @enderror"
        >
    @endif

    @error($name)
    <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
