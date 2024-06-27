<x-admin.layout title="Update Room">
    <x-admin.card>
        <x-admin.card.card-header title="Update Room"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.update',$room->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="name"
                        id="name"
                        label="Name"
                        value="{{$room->name}}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="file"
                        name="photo"
                        id="photo"
                        label="Photo"
                        value="{{$room->photo}}"
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
                        value="{{$room->room_number}}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="room_size"
                        id="room_size"
                        label="Room Size"
                        value="{{$room->room_size}}"
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
                        value="{{$room->guest_capacity}}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_balcony"
                        id="total_balcony"
                        label="Total Balcony"
                        value="{{$room->total_balcony}}"
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
                        value="{{$room->total_window}}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="base_price"
                        id="base_price"
                        label="Price"
                        value="{{$room->base_price}}"
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
                        value="{{$room->notes}}"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="bed_type_id"
                        id="bed_type_id"
                        label="Bed Type"
                        :options="$bedtypes"
                        :value="$room->bed_type_id"
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
                        :options="$roomtypes"
                        :value="$room->room_type_id"
                    />    
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="property_id"
                        id="property_id"
                        label="Property"
                        :options="$properties"
                        :value="$room->property_id"
                    />    
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="toggle"
                        name="extra_bed"
                        id="extra_bed"
                        label="Extra Bed"
                        value="{{$room->extra_bed}}"
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
                           Update Room
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
