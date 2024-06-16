<x-admin.layout title="Add New Role">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Role" />
        <x-admin.card.card-body>
            <form action="{{ route('admin.acl.roles.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}">

                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="name">Description</label>
                    <textarea  cols="5" rows="5" type="text" name="description" id="description" class="form-control" value="{{ old('description') }}"></textarea>
                    @error('description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <x-admin.button class="mt-3" type="submit">Add Role</x-admin.button>
            </form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
