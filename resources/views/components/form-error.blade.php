@props(['name'])

@error($name)
    <p class="
        text-xs
        text-danger
        font-semibold
        mt-1
    "
    >
    {{ $message }}
    </p>
@enderror