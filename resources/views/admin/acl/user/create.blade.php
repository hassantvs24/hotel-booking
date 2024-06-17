<x-admin.layout title="Add New User">
    <x-admin.card>
        <x-admin.card.card-header title="Add New User"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.users.store') }}" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{ old('name') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="email"
                            name="email"
                            id="email"
                            label="Email"
                            value="{{ old('email') }}"
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
                            value="{{ old('phone') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="password"
                            name="password"
                            id="password"
                            label="Password"
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
                        />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.acl.users.index') }}" class="btn btn-danger btn-sm">Back To Users</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">Add User</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#roles').select2();
            });
        </script>
    @endpush
</x-admin.layout>
