<x-admin.layout title="Add New City">
    <x-admin.card>
        <x-admin.card.card-header title="Add New City"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.places.cities.store') }}" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="City Name"
                            label="City Name"
                            value="{{ old('name') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="zip_code"
                            id="zip_code"
                            placeholder="Zip Code"
                            label="Zip Code"
                            value="{{ old('zip_code') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="lat"
                            id="lat"
                            placeholder="Latitude"
                            label="Latitude"
                            value="{{ old('lat') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="long"
                            id="long"
                            placeholder="Longitude"
                            label="Longitude"
                            value="{{ old('long') }}"
                        />
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <x-admin.input
                            type="select"
                            name="state_id"
                            id="state_id"
                            label="State"
                            :options="$states"
                        />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="{{ route('admin.places.cities.index') }}" class="btn btn-danger btn-sm">Back To Cities</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">Add City</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
