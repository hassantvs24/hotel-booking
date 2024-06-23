<x-admin.layout title="Countries">
    <x-admin.card>
        <x-admin.card.card-header title="Countries" class="d-flex align-content-center"/>
        <x-admin.card.card-body class="px-0 pt-0 pb-2">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Flag"/>
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Short Name"/>
                    <x-admin.table-head value="Country Code"/>
                    <x-admin.table-head value="Currency"/>
                    <x-admin.table-head value="Currency Code"/>
                    <x-admin.table-head value="Language"/>
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($countries as $country)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="$country->flag"/>
                            <x-admin.table-cell :value="$country->name"/>
                            <x-admin.table-cell :value="$country->short_name"/>
                            <x-admin.table-cell :value="$country->country_code"/>
                            <x-admin.table-cell :value="$country->currency"/>
                            <x-admin.table-cell>
                                <x-admin.badge :text="$country->currency_code"/>
                            </x-admin.table-cell>
                            <x-admin.table-cell class="word-wrap" :value="$country->language"/>
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
            {{ $countries->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
