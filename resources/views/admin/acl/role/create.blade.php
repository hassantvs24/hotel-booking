<x-admin.layout title="Add New Role">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Role" />
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.roles.store') }}" method="POST">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input
                        type="text"
                        name="name"
                        placeholder="Name"
                        id="name"
                        class="form-control"
                        value="{{ old('name') }}"
                    >
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="name">Description</label>
                    <textarea
                            cols="5"
                            rows="5"
                            type="text"
                            name="description"
                            placeholder="Write description here..."
                            id="description"
                            class="form-control"></textarea>
                    @error('description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mt-3">
                    <label for="permissions">Permissions</label>
                    <select class="form-control" id="permissions" name="permissions[]" multiple="multiple">
                        @foreach($permissions as $key => $permission)
                            <option value="{{ $key }}">{{ $permission }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.acl.roles.index') }}" class="btn btn-danger btn-sm">Back To Roles</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">Save  Role</x-admin.button>
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
