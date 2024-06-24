<x-admin.layout title="Add New Surrounding Place">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Surrounding Place"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.surroundings.surrounding-places.store') }}" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Name"
                            label="Name"
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
                            name="notes"
                            id="notes"
                            placeholder="Note"
                            label="Note"
                            value="{{ old('notes') }}"
                        />
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="place_id"
                            id="place_id "
                            label="Place"
                            :options="$places"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="surrounding_id"
                            id="surrounding_id"
                            label="Surrounding"
                            :options="$surroundings"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="file"
                            name="photo"
                            id="photo"
                            label="photo"
                            value="{{ old('photo') }}"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.surroundings.surrounding-places.index') }}" class="btn btn-danger btn-sm">Back To List</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Surrounding Place</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
