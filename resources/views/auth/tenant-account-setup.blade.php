<form method="POST" action="{{ route('tenant.setup.store', ['token' => $token]) }}">
    @csrf

    <x-form-input>
        label = "password"
        type = "password"
        name = "password"
        required
        minlength = "12"
        autocomplete = "new-password"
    </x-form-input>
    @error('password') <p>{{ $message }}</p> @enderror

    <x-form-input>
        label = "Confirm password"
        type = "password"
        name = "password_confirmation"
        required
        autocomplete = "new-password"
    </x-form-input>

    <button type="submit">Set password</button>
</form>