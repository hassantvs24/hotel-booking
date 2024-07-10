<x-admin.layout title="Add New Room">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Room"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.store') }}" method="POST"  enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="text"
                        name="name"
                        id="name"
                        label="Name"
                        value="{{ old('name') }}"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="room_number"
                        id="room_number"
                        label="Room Number"
                        value="{{ old('room_number') }}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="room_size"
                        id="room_size"
                        label="Room Size"
                        value="{{ old('room_size') }}"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="guest_capacity"
                        id="guest_capacity"
                        label="Guest Capacity"
                        value="{{ old('guest_capacity') }}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_balcony"
                        id="total_balcony"
                        label="Total Balcony"
                        value="{{ old('total_balcony') }}"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_window"
                        id="total_window"
                        label="Total Window"
                        value="{{ old('total_window') }}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="base_price"
                        id="base_price"
                        label="Price"
                        value="{{ old('base_price') }}"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="notes"
                        id="notes"
                        label="Note"
                        value="{{ old('notes') }}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="bed_type_id"
                        id="bed_type_id"
                        label="Bed Type"
                        :options="$bedTypes"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="room_type_id"
                        id="room_type_id"
                        label="Room Type"
                        :options="$roomTypes"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="property_id"
                        id="property_id"
                        label="Property"
                        :options="$properties"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="file"
                        name="photo"
                        id="photo"
                        label="Primary Image"
                    />    
                    </div>
                    <div class="col-md-12">
                        <img width="200" height="200" src="" id="preview_icon" alt=""/>
                    </div>

                    <div class="col-md-6 mt-4">
                        <x-admin.input
                        type="toggle"
                        name="extra_bed"
                        id="extra_bed"
                        label="Has Extra Bed ?"
                    />    
                    </div>
                </div>

                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Add Room
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#photo').on('change', function () {
                    let reader = new FileReader();
                    reader.onload = (e) => {
                        $('#preview_icon').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(this.files[0]);
                });
            });
        </script>
    @endpush
</x-admin.layout>
