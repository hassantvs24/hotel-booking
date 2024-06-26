<x-admin.layout title="Add New Bed Type">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Bed Type"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.bed-types.store') }}" method="POST">
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
                            type="number"
                            name="capacity"
                            id="capacity"
                            label="Capacity"
                            value="{{ old('capacity') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="total_bed"
                            id="total_bed"
                            label="Total Bed"
                            value="{{ old('total_bed') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="bed_size"
                            id="bed_size"
                            label="Bed Size"
                            value="{{ old('bed_size') }}"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.rooms.bed-types.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Bed Type</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
