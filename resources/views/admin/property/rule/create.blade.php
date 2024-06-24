<x-admin.layout title="Add New Property Rule">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Property Rule"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.properties.rules.store') }}" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="rule_title"
                            id="rule_title"
                            label="Rule Title"
                            value="{{ old('rule_title') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="rule_note"
                            id="rule_note"
                            label="Rule Note"
                            value="{{ old('rule_note') }}"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.properties.rules.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Property Rule</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
