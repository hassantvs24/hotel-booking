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
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{ $user->name }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="email"
                            name="email"
                            id="email"
                            label="Email"
                            value="{{ $user->email }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="phone"
                            id="phone"
                            label="Phone"
                            value="{{ $user->phone }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <x-admin.input
                            type="select"
                            name="roles[]"
                            id="roles"
                            label="Roles"
                            :options="$roles"
                            multiple
                            :value="$userRoles"
                        />
                    </div>
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
