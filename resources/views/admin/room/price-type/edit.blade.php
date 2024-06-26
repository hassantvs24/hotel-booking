<x-admin.layout title="Update Price Type">
    <x-admin.card>
        <x-admin.card.card-header title="Update Price Type"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.price-types.update',$priceType->id)}}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{$priceType->name}}"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.rooms.price-types.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Update Price Type
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>

