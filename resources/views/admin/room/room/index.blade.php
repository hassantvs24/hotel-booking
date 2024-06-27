<x-admin.layout title="Rooms">
    <x-admin.card>
        <x-admin.card.card-header title="Rooms" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
            <x-admin.page-action>
                <a href="{{ route('admin.rooms.create')}}" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i> 
                    Add Room
                </a>
            </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2 table-responsive">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Date"/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Photo"/>
                    <x-admin.table-head value="Room Number"/>
                    <x-admin.table-head value="Room Size"/>
                    <x-admin.table-head value="Guest Capacity"/>
                    <x-admin.table-head value="Extra Bed"/>
                    <x-admin.table-head value="Total Balcony"/>
                    <x-admin.table-head value="Total Window"/>
                    <x-admin.table-head value="Base Price"/>
                    <x-admin.table-head value="Bed Type"/>
                    <x-admin.table-head value="Room Type"/>
                    <x-admin.table-head value="Property"/>
                    @if (hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body> 
                    @forelse( $rooms as $room )
                        <x-admin.table-row>
                            <x-admin.table-cell :value="format_date($room->created_at)"/>
                            <x-admin.table-cell :value="$room->name"/>
                            <x-admin.table-cell :value="$room->photo"/>
                            <x-admin.table-cell :value="$room->room_number"/>
                            <x-admin.table-cell :value="$room->room_size"/>
                            <x-admin.table-cell :value="$room->guest_capacity"/>
                            <x-admin.table-cell :value="$room->extra_bed"/>
                            <x-admin.table-cell :value="$room->total_balcony"/>
                            <x-admin.table-cell :value="$room->total_window"/>
                            <x-admin.table-cell :value="$room->base_price"/>
                            <x-admin.table-cell :value="$room->notes"/>
                            <x-admin.table-cell :value="$room->roomtype?->bed_type_id"/>
                            <x-admin.table-cell :value="$room->roomtype?->room_type_id"/>
                            <x-admin.table-cell :value="$room->property?->property_id"/>
                            @if (hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                                <x-admin.table-cell class="text-right">
                                    @hasPermission($permissions['update'])
                                    <a href="{{ route('admin.rooms.edit', $room->id) }}"
                                       class="btn btn-icon btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                    <x-admin.form
                                            action="{{ route('admin.rooms.destroy', $room->id) }}"
                                            method="delete" class="d-inline">
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
                            <x-admin.table-cell colspan="6">No records found.</x-admin.table-cell>
                        </x-admin.table-row>
                    @endforelse
                </x-admin.table-body>
            </x-admin.table>
        </x-admin.card.card-body>
        <x-admin.card.card-footer>
            {{ $rooms->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>

