<x-admin.layout title="Update Booking">
    <x-admin.card>
        <x-admin.card.card-header title="Update Booking"/>
        <x-admin.card.card-body>
            <x-admin.form action="" method="PUT" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-12">
                        <x-admin.input
                            type="text"
                            name="booking_number"
                            id="booking_number"
                            placeholder="Booking Number"
                            label="Booking Number"
                            value=""
                        />
                    </div>
                </div>
            <div class="row">
                <div class="col-md-4">
                    <x-admin.input
                            type="time"
                            name="check_in_time"
                            id="check_in_time"
                            placeholder="Check In"
                            label="Check In"
                            value=""
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="time"
                            name="check_out_time"
                            id="check_out_time"
                            placeholder="Check Out"
                            label="Check Out"
                            value="" 
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="number"
                        name="adult"
                        id="adult"
                        placeholder="Adult"
                        label="Adult"
                        value=""
                    />
                    </div>
            </div>
                <div class="row">             
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="room"
                        id="room"
                        placeholder="Room"
                        label="Room "
                        value=""
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="reference"
                        id="reference"
                        placeholder="Reference"
                        label="Reference"
                        value=""
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="children"
                        id="children"
                        placeholder="children"
                        label="children"
                        value=""
                     />
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                      <x-admin.input
                         type="select"
                         name="room_id"
                         id="room_id"
                         placeholder="Room ID"
                         label="room_ID"
                         value=""
                       />
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                     <x-admin.input
                        type="textarea"
                        name="notes"
                        id="notes"
                        placeholder="Notes"
                        label="Notes"
                        value=""
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-danger btn-sm">Back To Places</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Update Booking
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
