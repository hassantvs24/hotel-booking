<x-admin.layout title="Update Facility">
    <x-admin.card>
        <x-admin.card.card-header title="Update Facility"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.facilities.sub-facilities.update',$subFacility->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{$subFacility->name}}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="icon"
                            name="icon"
                            id="icon"
                            label="Icon"
                            value="{{$subFacility->icon}}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="select"
                        name="facility_id"
                        id="facility_id"
                        label="Facility"
                        :options="$facilities"
                        :value="$subFacility->facility_id"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.facilities.sub-facilities.index') }}" class="btn btn-danger btn-sm">Back To Sub Facilities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Update Sub Facility</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
