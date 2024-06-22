<x-admin.layout title="Add New Role">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Role"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.roles.store') }}" method="POST">
                <x-admin.input
                    type="text"
                    name="name"
                    id="name"
                    label="Name"
                    placeholder="Name"
                    value="{{ old('name') }}"
                />
                <x-admin.input
                    type="textarea"
                    id="description"
                    name="description"
                    label="Description"
                    placeholder="Write description here..."
                    value="{{ old('description') }}"
                />
                <div class="row">
                    <div class="col-12">
                        <x-admin.input
                            type="select"
                            label="Permissions"
                            id="permissions"
                            name="permissions[]"
                            multiple
                            :options="$permissions"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.acl.roles.index') }}" class="btn btn-danger btn-sm">Back To Roles</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Save Role</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#permissions').select2();
            });
        </script>
    @endpush
</x-admin.layout>
