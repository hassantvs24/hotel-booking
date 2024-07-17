<x-admin.layout title="Settings">
    <x-admin.card>
        <x-admin.card.card-header title="Settings" class="d-flex align-content-center">
            <x-admin.page-action>
                <a href="{{ route('admin.settings.create') }}" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Setting
                </a>
            </x-admin.page-action>
        </x-admin.card.card-header>

        <x-admin.card.card-body>
            <x-admin.table>
                <x-admin.table-header>
                    <x-admin.table-head value="Key"/>
                    <x-admin.table-head value="Value"/>
                    <x-admin.table-head value="Group"/>
                    <x-admin.table-head value="Actions"/>
                </x-admin.table-header>
                <x-admin.table-body>
                    @forelse($settings as $setting)
                        <x-admin.table-row>
                            <x-admin.table-cell :value="$setting->key"/>
                            <x-admin.table-cell :value="$setting->value"/>
                            <x-admin.table-cell :value="\Illuminate\Support\Str::ucfirst($setting->group)"/>
                            <x-admin.table-cell>
                                <a href="{{ route('admin.settings.edit', $setting->id) }}"
                                   class="btn btn-icon btn-xs btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
{{--                                <x-admin.form--}}
{{--                                    action="{{ route('admin.settings.destroy', $setting->id) }}"--}}
{{--                                    method="delete"--}}
{{--                                    class="d-inline"--}}
{{--                                >--}}
{{--                                    <button onclick="return confirm('Want to delete?')" type="submit"--}}
{{--                                            class="btn btn-icon btn-xs btn-outline-danger">--}}
{{--                                        <i class="fas fa-trash"></i>--}}
{{--                                    </button>--}}
{{--                                </x-admin.form>--}}
                            </x-admin.table-cell>
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
            {{ $settings->links() }}
        </x-admin.card.card-footer>

    </x-admin.card>
</x-admin.layout>