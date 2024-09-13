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
                    <x-admin.table-head value="Booking Number"/>
                    <x-admin.table-head value="Check In"/>
                    <x-admin.table-head value="Check Out"/>
                    <x-admin.table-head value="Adult"/>
                    <x-admin.table-head value="Children"/>
                    <x-admin.table-head value="Room"/>
                    <x-admin.table-head value="Reference"/>
                    <x-admin.table-head value="Notes"/>
                    <x-admin.table-head value="Room Type"/>
                    <x-admin.table-head value="User"/>
                    @if(hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                        <x-admin.table-head class="text-right" value="Actions"/>
                    @endif
                </x-admin.table-header>

                <x-admin.table-body>
                        
                        <x-admin.table-row>
                            <x-admin.table-cell colspan="11">No records found.</x-admin.table-cell>
                        </x-admin.table-row>
                </x-admin.table-body>
            </x-admin.table>
        </x-admin.card.card-body>

        <x-admin.card.card-footer>
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
