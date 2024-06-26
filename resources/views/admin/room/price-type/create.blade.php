<x-admin.layout title="Add Price Type">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Price Type"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.price-types.store')}}" method="POST">
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
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Room type</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
