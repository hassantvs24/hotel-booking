<x-admin.layout title="Review Category">
    <x-admin.card>
        <x-admin.card.card-header title="Review Category" class="d-flex align-content-center">
            @hasPermission($permissions['create'])
            <x-admin.page-action>
                <a href="{{ route('admin.reviews.categories.create') }}" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Review Category
                </a>
            </x-admin.page-action>
            @endHasPermission
        </x-admin.card.card-header>

        <x-admin.card.card-body class="px-0 pt-0 pb-2 table-responsive">
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head />
                    <x-admin.table-head value="Name"/>
                    <x-admin.table-head value="Notes"/>
                    @if (hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                        <x-admin.table-head value="Actions"/>
                    @endif
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($reviewCategories as $reviewCategory)
                        <x-admin.table-row>
                            <x-admin.table-cell>
                                <img
                                    width="30"
                                    height="30"
                                    src="{{ $reviewCategory->icon_url }}"
                                    alt="{{ $reviewCategory->name }}"
                                >
                            </x-admin.table-cell>
                            <x-admin.table-cell :value="$reviewCategory->name"/>
                            <x-admin.table-cell
                                class="word-wrap"
                                :value="\Illuminate\Support\Str::limit($reviewCategory->notes, 50)"
                            />
                            @if (hasPermission($permissions['update']) || hasPermission($permissions['delete']))
                                <x-admin.table-cell>
                                    @hasPermission($permissions['update'])
                                    <a href="{{ route('admin.reviews.categories.edit', $reviewCategory->id) }}"
                                       class="btn btn-icon btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endHasPermission
                                    @hasPermission($permissions['delete'])
                                    <x-admin.form
                                            action="{{ route('admin.reviews.categories.destroy', $reviewCategory->id) }}"
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
            {{ $reviewCategories->links() }}
        </x-admin.card.card-footer>
    </x-admin.card>
</x-admin.layout>
