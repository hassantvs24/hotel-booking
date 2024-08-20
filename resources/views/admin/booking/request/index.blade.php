<x-admin.layout title="Booking">
    <x-admin.card>
        <x-admin.card.card-header title="Booking" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
                <x-admin.page-action>
                </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2 table-responsive">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Date"/>
                    <x-admin.table-head value="Check In"/>
                    <x-admin.table-head value="Check Out"/>
                    <x-admin.table-head value="Adult"/>
                    <x-admin.table-head value="Children"/>
                    <x-admin.table-head value="Room"/>
                    <x-admin.table-head value="Status"/>
                    <x-admin.table-head value="User"/>
                    @if(hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>

                <x-admin.table-body>
                    @forelse($bookingRequests as $bookingRequest)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($bookingRequest->created_at)"/>
                            <x-admin.table-cell :value="$bookingRequest->checkin"/>
                            <x-admin.table-cell :value="$bookingRequest->checkout"/>
                            <x-admin.table-cell :value="$bookingRequest->adult"/>
                            <x-admin.table-cell :value="$bookingRequest->children ?? 0"/>
                            <x-admin.table-cell :value="$bookingRequest->room"/>
                            <x-admin.table-cell class="word-wrap mt-2">
                                <x-admin.form 
                                    action="{{ route('admin.bookings.request.update', $bookingRequest->id) }}" 
                                    method="POST"
                                    class="update-form"
                                    data-booking-id="{{ $bookingRequest->id }}"
                                >
                                    @csrf
                                    @method('PUT') <!-- Hidden field to override method to PUT -->
                                    <x-admin.input
                                        type="select"
                                        name="status"
                                        id="status-{{ $bookingRequest->id }}"
                                        :options="$status"
                                        :value="in_array($bookingRequest->id, $acceptedBookingIds) ? 'Accepted' : $bookingRequest->status"
                                    />
                                </x-admin.form>
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$bookingRequest->user?->name"/>
                            @if(hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                                <x-admin.table-cell class="text-right">
                                    @hasPermission($permissions['update'])
                                        <a href="{{ route('admin.bookings.request.edit', $bookingRequest->id) }}"
                                           class="btn btn-icon btn-xs btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                        <x-admin.form
                                            action="{{ route('admin.bookings.request.destroy', $bookingRequest->id) }}"
                                            method="delete"
                                            class="d-inline"
                                        >
                                            <button onclick="return confirm('Want to delete?')" type="submit"
                                                    class="btn btn-icon btn-xs btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </x-admin.form>
                                    @endHasPermission
                                </x-admin.table-cell>
                            @endif
                        </x-admin.table-row>
                    @empty
                        <x-admin.table-row>
                            <x-admin.table-cell colspan="9">No records found.</x-admin.table-cell>
                        </x-admin.table-row>
                    @endforelse
                </x-admin.table-body>
            </x-admin.table>
        </x-admin.card.card-body>

        <x-admin.card.card-footer>
        </x-admin.card.card-footer>
    </x-admin.card>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Handle status change in all forms
            $(document).on('change', '[id^=status-]', function() {
                var form = $(this).closest('form');
                var formData = new FormData(form[0]);

                $.ajax({
                    url: form.attr('action'),
                    method: "POST", 
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    },
                    data: formData,
                    processData: false, 
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Update successful!');
                        } else {
                            alert('Update failed: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', xhr.responseText);
                        alert('An error occurred while updating.');
                    }
                });
            });
        });
    </script>
    @endpush
</x-admin.layout>
