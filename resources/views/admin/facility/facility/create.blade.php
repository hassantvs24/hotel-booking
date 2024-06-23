<x-admin.layout title="Add New Facility">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Facility"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.facilities.store') }}" method="POST">
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
                    <div class="col-md-6">
                        <x-admin.input
                            type="icon"
                            name="icon"
                            id="icon"
                            label="Icon"
                            value="{{ old('icon') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="textarea"
                            name="notes"
                            id="notes"
                            label="Note"
                            value="{{ old('notes') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="facility_type"
                            id="facility_type"
                            label="facility_type"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.facilities.index') }}" class="btn btn-danger btn-sm">Back To Facilities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Facility</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
