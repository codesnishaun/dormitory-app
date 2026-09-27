@props([
	'name',
	'label' => null,
	'type' => 'text',
	'value' => null,
	'options' => [],
	'rows' => 3,
	'id' => null,
	'checked' => false,
])

@php
	$fieldId = $id ?? str_replace(['.', '[', ']'], '-', $name);
	$fieldValue = old($name, $value);
	$submittedValue = old($name);
	$controlClasses = 'w-full px-3.5 py-2.5 rounded-[10px] border border-ink/[0.14] bg-white text-[14.5px] text-ink focus:outline-none focus:border-copper transition';
	$isChecked = match ($type) {
		'checkbox' => $submittedValue === null
			? $checked
			: (is_array($submittedValue)
				? in_array((string) ($value ?? '1'), array_map('strval', $submittedValue), true)
				: (bool) $submittedValue),
		'radio' => $submittedValue === null
			? $checked
			: (string) $submittedValue === (string) $value,
		default => false,
	};
	$selectedValues = array_map('strval', (array) $fieldValue);
@endphp

@if ($label)
	<x-form-label :for="$fieldId">{{ $label }}</x-form-label>
@endif

@if ($type === 'select')
	<select id="{{ $fieldId }}" name="{{ $name }}" {{ $attributes->merge(['class' => $controlClasses]) }}>
		@foreach ($options as $optionValue => $optionLabel)
			<option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>
				{{ $optionLabel }}
			</option>
		@endforeach
	</select>
@elseif ($type === 'textarea')
	<textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => $controlClasses]) }}>{{ old($name, $value ?? $slot) }}</textarea>
@else
	<input
		id="{{ $fieldId }}"
		name="{{ $name }}"
		type="{{ $type }}"
		@if ($type === 'checkbox' || $type === 'radio')
			value="{{ $value ?? '1' }}"
			@checked($isChecked)
		@elseif ($type !== 'file')
			value="{{ $fieldValue }}"
		@endif
		{{ $attributes->merge(['class' => $controlClasses]) }}
	/>
@endif

<x-form-error :name="$name" />
