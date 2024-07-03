<x-admin.layout title="Update Review Category">
    <x-admin.card>
        <x-admin.card.card-header title="Update Review Category"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.reviews.categories.update', $reviewCategory->id)}}" method="PUT">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{ $reviewCategory->name }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="icon"
                            name="icon"
                            id="icon"
                            label="Icon"
                            value="{{ $reviewCategory->icon }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="textarea"
                            name="notes"
                            id="notes"
                            label="Note"
                            value="{{ $reviewCategory->notes }}"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.reviews.categories.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Update Review Category
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
