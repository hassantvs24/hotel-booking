<x-admin.layout title="Add New User">
    <x-admin.card>
        <x-admin.card.card-header title="Update User" />
        <x-admin.card.card-body>
            <form action="{{ route('admin.acl.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
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
                <x-admin.button class="mt-3" type="submit">Update User</x-admin.button>
            </form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
