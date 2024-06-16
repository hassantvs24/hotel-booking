<x-admin.layout title="Update Role">
    <x-admin.card>
        <x-admin.card.card-header title="Update Role" />
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.acl.roles.update',$role->id) }}" method="put">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ $role->name}}">

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
                        id="description"
                        class="form-control">{{$role->description}}</textarea>

                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label for="permissions">Roles:</label>
                    <select class="form-control" id="permissions" name="permissions[]" multiple="multiple">
                        @foreach($permissions as $key => $permission)
                            <option
                                    value="{{ $key }}"
                                    {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'selected' : '' }}>
                                {{ $permission }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
