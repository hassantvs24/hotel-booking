@php
    /**
     * @var \App\Models\User $user
     */
        $userRoles = $user->roles->pluck('id')->toArray();
@endphp

<x-admin.layout title="Update User">
    <x-admin.card>
        <x-admin.card.card-header title="Update User" />
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.users.update', $user->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    value="{{ $user->name }}"
                            >

                            @error('name')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email">Email</label>
                        <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="{{ $user->email }}">

                        @error('email')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label for="phone">Phone</label>
                        <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="form-control"
                                value="{{ $user->phone }}">
                        @error('phone')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label for="roles">Roles:</label>
                    <select class="form-control" id="roles" name="roles[]" multiple="multiple">
                        @foreach($roles as $key => $role)
                            <option value="{{ $key }}" {{ in_array($key, $userRoles) ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.acl.users.index') }}" class="btn btn-danger btn-sm">Back To Users</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">Update User</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#roles').select2();
            });
        </script>
    @endpush
</x-admin.layout>
