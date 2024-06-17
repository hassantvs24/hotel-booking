<x-admin.layout title="Update Role">
    <x-admin.card>
        <x-admin.card.card-header title="Update Role" />
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.roles.update',$role->id) }}" method="put">
                <x-admin.input
                    type="text"
                    name="name"
                    id="name"
                    label="Name"
                    placeholder="Name"
                    value="{{ $role->name }}"
                />
                <x-admin.input
                    type="textarea"
                    id="description"
                    name="description"
                    label="Description"
                    placeholder="Write description here..."
                    value="{{ $role->description }}"
                />

                <x-admin.input
                    class="mt-3"
                    type="select"
                    label="Permissions"
                    id="permissions"
                    name="permissions[]"
                    multiple
                    :options="$permissions"
                    :value="$role->permissions->pluck('id')->toArray()"
                />

                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.acl.roles.index') }}" class="btn btn-danger btn-sm">Back To Roles</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">Update Role</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#permissions').select2();
            });
        </script>
    @endpush
</x-admin.layout>
