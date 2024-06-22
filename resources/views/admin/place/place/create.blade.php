<x-admin.layout title="Add New Place">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Place"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.places.store') }}" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Place Name"
                            label="Place Name"
                            value="{{ old('name') }}"
                        />
                    </div>
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
                </div>
                <div class="row">
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
                        type="textarea"
                        name="description"
                        id="description"
                        placeholder="Description"
                        label="Description"
                        value="{{ old('description') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="nearest_police"
                        id="nearest_police"
                        placeholder="Nearest Police "
                        label="Nearest Police "
                        value="{{ old('nearest_police') }}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="nearest_hospital"
                        id="nearest_hospital"
                        placeholder="Nearest Hospital"
                        label="Nearest Hospital"
                        value="{{ old('nearest_hospital') }}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="nearest_fire"
                        id="nearest_fire"
                        placeholder="Nearest Fire "
                        label="Nearest Fire "
                        value="{{ old('nearest_fire') }}"
                    />
                    </div>

                </div>

                <div class="row">
                    <div class="col-6">
                        <x-admin.input
                            type="file"
                            name="photo"
                            id="photo"
                            placeholder="Photo"
                            label="Photo"
                            value="{{ old('photo') }}"
                        />
                    </div>
                    <div class="col-6">
                        <x-admin.input
                            type="select"
                            name="city_id"
                            id="city_id"
                            label="City"
                            :options="$cities"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.places.index') }}" class="btn btn-danger btn-sm">Back To Cities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add City</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
