<x-admin.layout title="Update Role">
    <x-admin.card>
        <x-admin.card.card-header title="Update Role" />
        <x-admin.card.card-body>
            <form action="{{ route('admin.acl.roles.update',$role->id) }}" method="POST">
                @csrf
                @method('PUT')
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

                <x-admin.button class="mt-3" type="submit">Update Role</x-admin.button>
            </form>
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
