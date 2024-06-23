<x-admin.layout title="Update Surrounding Place">
    <x-admin.card>
        <x-admin.card.card-header title="Update Surrounding Place"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.surroundings.surrounding-places.update',$surroundingPlace->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Name"
                            label="Name"
                            value="{{$surroundingPlace->name}}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="lat"
                            id="lat"
                            placeholder="Latitude"
                            label="Latitude"
                            value="{{$surroundingPlace->lat}}"
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
                            value="{{$surroundingPlace->long}}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="notes"
                            id="notes"
                            placeholder="Note"
                            label="Note"
                            value="{{$surroundingPlace->notes}}"
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
                            value="{{$surroundingPlace->photo}}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="place_id "
                            id="place_id "
                            label="Place"
                            :options="$places"
                            :value="$surroundingPlace->place_i"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="surrounding_id "
                            id="surrounding_id"
                            label="Surrounding"
                            :options="$surroundings"
                            :value="$surroundingPlace->surrounding_id"
                        />
                    </div>
                </div>

                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.surroundings.surrounding-places.index') }}" class="btn btn-danger btn-sm">Back To Surrounding Places</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Update Surrounding Place</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
