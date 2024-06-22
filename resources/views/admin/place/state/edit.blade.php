<x-admin.layout title="Update State">
    <x-admin.card>
        <x-admin.card.card-header title="Update State"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.places.states.update', $state->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="State Name"
                            label="State Name"
                            value="{{ $state->name }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="short_name"
                            id="short_name"
                            placeholder="Short Name"
                            label="Short Name"
                            value="{{ $state->short_name }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <x-admin.input
                            type="select"
                            name="country_id"
                            id="country_id"
                            label="Country"
                            :options="$countries"
                            :value="$state->country_id"
                        />
                    </div>
                </div>

                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.places.cities.index') }}" class="btn btn-danger btn-sm">Back To Cities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Add State
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
