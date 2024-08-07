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
                    <x-admin.table-head/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Room Number"/>
                    <x-admin.table-head value="Room Info"/>
                    <x-admin.table-head value="Base Price"/>
                    <x-admin.table-head value="Property"/>
                    @if (hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body> 
                    @forelse( $rooms as $room )
                        <x-admin.table-row>
                            <x-admin.table-cell>
                                <img
                                    width="50"
                                    height="50"
                                    src="{{ $room->primary_image_url }}"
                                    alt="{{ $room->name }}"
                                >
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$room->name"/>
                            <x-admin.table-cell :value="$room->room_number"/>
                            <x-admin.table-cell>
                                <ul>
                                    <li><b>Room Type: </b> {{ $room->roomType?->name }}</li>
                                    <li><b>Capacity: </b> {{ $room->guest_capacity }}</li>
                                    <li><b>Balcony: </b> {{ $room->total_balcony }}</li>
                                    <li><b>Window: </b> {{ $room->total_window }}</li>
                                    <li><b>Bed Type: </b> {{ $room->bedType?->name }}</li>
                                    <li><b>Extra Bed: </b> {{ $room->extra_bed == 1 ? 'Yes' : 'No' }}</li>
                                </ul>
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$room->base_price"/>
                            <x-admin.table-cell :value="$room->property?->name"/>
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

